#!/usr/bin/env python3
"""Validator for the output of the generate-docs skill.

Checks the deterministically verifiable properties of `ai-docs/`: required front-matter
fields, agreement between tags and content, the `Subject: section` heading pattern,
existence of the paths in `covers`, [VERIFY: ...] anchors and internal links (all of them
confined to the repository / the published root), values that look like secrets or internal
addresses, plus the machine-readable `manifest.yaml` (Orbis core schema, schema 0.3.0) and its
1:1 agreement with the prose — the drift check, in both directions: every named manifest entry
(`publishes`, `consumes`, `exposes`, `calls`, `owns_data`, `reads_external_data`, `aliases`) must
be mentioned as text in some page, and every id a dependency line of the prose names
(`calls → X`, `called from → X`, `references → X`, `hosts → X`) must be one the manifest knows
(a module id, a called service, an owner of external data, an alias, the repo id, or `unknown`).

Two anchor forms are accepted:
    [VERIFY: path/file.cs#Symbol]  preferred — the symbol must be findable in the file
    [VERIFY: path/file.cs:123]     fallback — only where the evidence has no name

Usage:
    python3 validate.py <path>/ai-docs [--repo-root <path>] [--strict] [--require-yaml]
    python3 validate.py <path>/ai-docs --partial reference/<module>.md \
        [--manifest-part <path>/ai-docs/manifest.part.<module>.yaml]

Exit code 0 = no errors. 1 = errors found. 2 = the validator could not run.
With `--strict` a warning counts as an error.

CI always runs the full mode. `--partial` is for local per-module work: it validates one
document (and, with `--manifest-part`, the module's manifest fragment against that document:
the drift check runs against the part, and the module's own id counts as known)
and skips the requirements only the whole of ai-docs/ can meet — `index.md`, `manifest.yaml`
and the agreement of `repo` across documents.

Stdlib only, so it runs in CI without installation. PyYAML is used for the manifest and
drift checks when it happens to be installed; without it those checks are reported as skipped
and everything else still runs. With `--require-yaml` (CI) a missing PyYAML is an error.

Note: the string constants below (front-matter keys, `type-*` values, forbidden phrases,
the metadata line pattern, manifest keys) are the document schema decided in schema 0.3.0 —
they are data, not identifiers, and the handbook's identifier table is their contract.
"""

from __future__ import annotations

import argparse
import functools
import re
import sys
from pathlib import Path

REQUIRED_FIELDS = [
    "title",
    "repo",
    "module",
    "generated-by",
    "source-commit",
    "last-generated",
    "covers",
    "confidence",
    "tags",
]

# Pre-0.3.0 front-matter keys, rejected with the rename.
OLD_FRONT_MATTER_KEYS = {"modul": "module"}
CONFIDENCE_VALUES = {"draft", "reviewed", "verified"}
TYPES = {"overview", "architecture", "reference", "dependencies", "index"}
# Pre-0.3.0 type names, so the error can say what to rename to.
OLD_TYPES = {"prehled": "overview", "architektura": "architecture", "vazby": "dependencies"}

# Pre-0.3.0 file names, rejected with the rename.
OLD_FILENAMES = {"prehled.md": "overview.md", "architektura.md": "architecture.md", "vazby.md": "dependencies.md"}

# File name -> expected type. reference/* is resolved by directory instead.
TYPE_BY_FILENAME = {
    "index.md": "index",
    "overview.md": "overview",
    "architecture.md": "architecture",
    "dependencies.md": "dependencies",
}

FORBIDDEN_PHRASES = [
    "see above",
    "see below",
    "as mentioned above",
    "as described below",
    "viz výše",
    "viz níže",
    "viz vyse",
    "viz nize",
]

RE_VERIFY = re.compile(r"\[VERIFY:\s*([^\]]+?)\s*\]")
RE_ANCHOR_SYMBOL = re.compile(r"^(?P<path>[^#]+)#(?P<symbol>.+)$")
RE_ANCHOR_LINE = re.compile(r"^(?P<path>.+):(?P<line>\d+)$")
RE_LINK = re.compile(r"\[[^\]]*\]\(([^)]+)\)")
RE_CODE_SPAN = re.compile(r"`[^`\n]*`")
# `<Name>` outside a code span: the Confluence converter parses it as an HTML tag and the page
# fails to convert (a generic argument in an anchor, a placeholder in prose).
RE_ANGLE_TOKEN = re.compile(r"<[A-Za-z][A-Za-z0-9_.]{0,80}(?:\s*,\s*[A-Za-z][A-Za-z0-9_.]{0,80}){0,8}>")
RE_H2_HEADING = re.compile(r"^##\s+(?P<subject>[^:#]+):\s+(?P<section>.+?)\s*$")
RE_META_LINE = re.compile(r"^Repo:\s*.+·\s*Module:\s*.+·\s*Type:\s*.+$", re.MULTILINE)
RE_ISO_DATE = re.compile(r"^\d{4}-\d{2}-\d{2}")

MIN_H2 = 3
MAX_H2 = 6
MAX_TABLE_ROWS = 10
# A symbol anchor that matches this many places in one file no longer points at anything
# in particular — the document should anchor on something narrower.
MAX_SYMBOL_HITS = 8

MANIFEST_NAME = "manifest.yaml"
# Per-module fragments (`--partial` work); the full run only scans them for secrets.
MANIFEST_PART_GLOB = "manifest.part.*.yaml"
MANIFEST_REQUIRED_FIELDS = ["service", "description", "generated-by", "source_commit", "last-generated"]
# Pre-0.3.0 manifest keys -> Orbis keys. `repo:` stays legal when its value is the source
# git URL (optional, needed only when ai-docs/ lives outside the code repo).
OLD_MANIFEST_KEYS = {"repo": "service", "source-commit": "source_commit"}
RE_GIT_URL = re.compile(r"^(git@|ssh://|http://|https://)")
# section -> key carrying the name; every value must be mentioned in the prose (drift, manifest -> prose)
MANIFEST_SECTIONS = {
    "publishes": "name",
    "consumes": "name",
    "exposes": "name",
    "calls": "service",
    "owns_data": "entity",
    "reads_external_data": "entity",
}
OLD_ENTRY_KEYS = {"calls": {"target": "service"}}
# A dependency line of the prose (`templates/writing-sections.md`): the id after the arrow, up to the
# first space, comma or parenthesis, must be one the manifest knows (drift, prose -> manifest).
RE_DEPENDENCY_LINE = re.compile(r"^\s*(?:[-*]\s+)?(?:calls|called from|references|hosts)\s*(?:→|->)\s*(?P<id>[^\s,(]+)")
# `unknown` is the contract's own notation for "not determinable from the code" (SKILL.md, rule 4).
DEPENDENCY_ID_ALWAYS_KNOWN = frozenset({"unknown"})
# `service`, `modules[].name`, `calls[].service`, `reads_external_data[].owner`: lowercase, digits,
# `-` and `.` — the same string in the manifest, in dependency lines and in other repos' manifests.
RE_CANONICAL_ID = re.compile(r"^[a-z0-9]+(?:[.-][a-z0-9]+)*$")

# Rough estimate: an English word ≈ 1.3 tokens. A section should target 250–600 tokens;
# we only warn on obvious outliers, not on slight overshoots. The four shared pages hold
# sections that are short by nature on a small repository (module list, assumptions), so their
# minimum is lower than a reference page's.
TOKENS_PER_WORD = 1.3
SECTION_TOKENS_MIN = 120
SECTION_TOKENS_MIN_SHARED = 60
SECTION_TOKENS_MAX = 1000
SHARED_TYPES = frozenset(TYPE_BY_FILENAME.values())

# --- secret detection -------------------------------------------------------------------------
# Every pattern carries its own flags; a global `(?i)` inside a pattern does not compile on
# Python 3.11+. Named so that a report says which class fired and tests can probe one at a time.

# No leading word boundary: `POSTGRES_PASSWORD=` and `DbPassword=` are keywords too; the
# `=`/`:` that must follow keeps `tokens:` and `passwords:` out.
_CREDENTIAL_KEYWORD = r"(?:password|passwd|pwd|secret|token|api[_-]?key|client[_-]?secret)"
# The value must be data, not an anchor, a placeholder or a prose word.
_CREDENTIAL_NOT_A_VALUE = (
    r"(?![\[<{(—-])"
    r"(?!(?:string|int|bool|null|none|unknown|required|optional|see|stored|issued|hashed|encrypted"
    r"|redacted|from|via|in|the)\b)"
)
# A PascalCase type name such as `JwtSecurityToken` (case-sensitive inside the case-insensitive pattern).
_CREDENTIAL_NOT_A_TYPE = r"(?!(?-i:[A-Z][a-z]+(?:[A-Z][a-z0-9]+)+)\b)"
_CREDENTIAL_VALUE = r"[^\s\"'|,;]{7,}"
# A DNS label is at most 63 characters. The bounds here and below (labels, e-mail local part, URL
# scheme and userinfo) keep the scan linear on a long line of separators: unbounded, `a-a-a-…` × 10 000
# costs seconds, because every start position re-scans the rest of the line. A host with more than
# six labels still matches — from a later label.
_DNS_LABEL = r"[a-z0-9-]{1,63}"


def _credential_pattern(prose: bool) -> re.Pattern:
    """The `credential` pattern; `prose=False` is the manifest variant without the type-name exception.

    Three forms. A quoted key (`"Password": "…"`, JSON) and an assignment (`Password=…`,
    config, connection string) are data: any value counts. A bare `password: …` is prose or
    YAML: `password: changeme` counts; `token: JwtSecurityToken` (a PascalCase type name) does
    not in prose, but a manifest is data, so there it counts as well. Prose that trips it
    (`password: expires after 90 days`) opts out with `secret-ok`.
    """
    not_a_type = _CREDENTIAL_NOT_A_TYPE if prose else ""
    return re.compile(
        _CREDENTIAL_KEYWORD
        + r"(?:[\"']\s*[=:]\s*[\"']?" + _CREDENTIAL_NOT_A_VALUE + _CREDENTIAL_VALUE
        + r"|\s*=\s*[\"']?" + _CREDENTIAL_NOT_A_VALUE + _CREDENTIAL_VALUE
        + r"|\s*:\s*[\"']?" + _CREDENTIAL_NOT_A_VALUE + not_a_type + _CREDENTIAL_VALUE
        + r")",
        re.I,
    )


SECRET_PATTERNS = {
    "credential": _credential_pattern(prose=True),
    "connection-string": re.compile(
        r"\b(server|host|data source|accountkey|sharedaccesskey|sharedaccesssignature)\s*=\s*[^;\s]+;", re.I
    ),
    # A hyphenated word after `Basic`/`Bearer` (`Basic authentication-only`) is prose, not a token.
    "bearer-or-basic": re.compile(r"\b(Bearer|Basic)\s+(?![a-z-]+\b)[A-Za-z0-9+/=_.-]{16,}"),
    "jwt": re.compile(r"\beyJ[A-Za-z0-9_-]{10,}\.[A-Za-z0-9_-]{10,}\.[A-Za-z0-9_-]{10,}"),
    "vendor-token": re.compile(
        r"\b(AKIA[0-9A-Z]{16}|glpat-[A-Za-z0-9_-]{20,}|gl(rt|dt|ptt)-[A-Za-z0-9_-]{16,}|gh[pous]_[A-Za-z0-9]{36}"
        r"|github_pat_[A-Za-z0-9_]{22,}|xox[abprs]-[A-Za-z0-9-]{10,}|sig=[A-Za-z0-9%+/=]{20,})"
    ),
    "private-key": re.compile(r"-----BEGIN [A-Z ]*PRIVATE KEY-----"),
    "creds-in-url": re.compile(r"\b[a-z][a-z0-9+.-]{0,31}://[^\s:@/]{1,128}:[^\s@/]{1,128}@", re.I),
    "slack-webhook": re.compile(r"hooks\.slack\.com/services/"),
    "internal-host": re.compile(
        r"\b" + _DNS_LABEL + r"(?:\." + _DNS_LABEL + r"){0,5}"
        r"\.(local|internal|intra|svc|cluster\.local|packeta\.(com|cz|local))\b",
        re.I,
    ),
}
# A manifest or a fragment is data, not prose: `password: SuperSecret123` there is a value, not a type name.
MANIFEST_SECRET_PATTERNS = {**SECRET_PATTERNS, "credential": _credential_pattern(prose=False)}
# Warnings, not errors: legitimate in some documents, and --strict still fails CI on them.
WARN_PATTERNS = {
    # Four octets, so that a version such as `.NET 10.0.100` is not an address.
    "private-ip": re.compile(
        r"(?<![\d.])(?:10\.\d{1,3}\.\d{1,3}\.\d{1,3}|127\.\d{1,3}\.\d{1,3}\.\d{1,3}"
        r"|172\.(?:1[6-9]|2\d|3[01])\.\d{1,3}\.\d{1,3}|192\.168\.\d{1,3}\.\d{1,3})(?!\d|\.\d)"
    ),
    # A letter-only top-level label, so that `skill:generate-docs@0.3.0` is not an address. The
    # local part is bounded at the RFC 5321 limit of 64 characters, the labels as in `_DNS_LABEL`.
    "email": re.compile(
        r"\b(?!noreply@|no-reply@)[\w.+-]{1,64}@[\w-]{1,63}(?:\.[\w-]{1,63}){0,5}\.[a-z]{2,63}\b", re.I
    ),
}
# Documentation hosts the `internal-host` pattern may name. `localhost` needs no entry: the pattern
# fires only on the listed top-level labels, and `localhost` has none of them.
HOST_ALLOWLIST = ("example.com",)
# Inline exception with a mandatory reason; the reason is printed so the exception is auditable.
SECRET_OK = re.compile(r"<!--\s*secret-ok:\s*(\S.*?)\s*-->")
# Removed from a line before matching (never the whole line): anchors, inline-code anchors
# and git SSH URLs, which the e-mail pattern would otherwise report.
RE_CODE_ANCHOR = re.compile(r"`[^`]*#[^`]*`")
RE_GIT_SSH_URL = re.compile(r"git@[^\s:]+:\S+\.git")
RE_HOST_TOKEN = re.compile(r"[A-Za-z0-9.-]+")


class Findings:
    def __init__(self) -> None:
        self.errors: list[str] = []
        self.warnings: list[str] = []
        self.infos: list[str] = []

    def error(self, file: Path, line: int | None, message: str) -> None:
        self.errors.append(_location(file, line) + " " + message)

    def warning(self, file: Path, line: int | None, message: str) -> None:
        self.warnings.append(_location(file, line) + " " + message)

    def info(self, file: Path, line: int | None, message: str) -> None:
        self.infos.append(_location(file, line) + " " + message)


def _location(file: Path, line: int | None) -> str:
    return f"{file}:{line}" if line else f"{file}:"


def inside(root: Path, target: str) -> Path | None:
    """Resolve `target` against `root`; None when the result leaves `root` (`..`, absolute path, symlink)."""
    candidate = (root / target).resolve()
    return candidate if candidate.is_relative_to(root.resolve()) else None


# --- front matter -----------------------------------------------------------------------------


def strip_comment(value: str) -> str:
    """Remove a YAML comment from an unquoted scalar value."""
    if value.startswith(('"', "'")):
        return value
    return re.split(r"\s+#", value, maxsplit=1)[0].strip()


def parse_value(raw: str):
    raw = raw.strip()
    if raw.startswith("[") and raw.endswith("]"):
        inner = raw[1:-1].strip()
        if not inner:
            return []
        return [p.strip().strip("\"'") for p in inner.split(",") if p.strip()]
    raw = raw.strip("\"'")
    if raw in ("null", "~", ""):
        return None
    return raw


def parse_front_matter(text: str, file: Path, findings: Findings):
    """Mini-parser for the YAML subset the skill emits. Returns (dict, body, body_offset)."""
    lines = text.split("\n")
    if not lines or lines[0].strip() != "---":
        findings.error(file, 1, "missing YAML front-matter (file does not start with `---`)")
        return None, text, 0

    end = None
    for i in range(1, len(lines)):
        if lines[i].strip() == "---":
            end = i
            break
    if end is None:
        findings.error(file, 1, "front-matter is not closed with `---`")
        return None, text, 0

    data: dict = {}
    list_key = None
    for i in range(1, end):
        line = lines[i]
        if not line.strip() or line.strip().startswith("#"):
            continue
        if line.lstrip().startswith("- ") and list_key:
            data.setdefault(list_key, [])
            if isinstance(data[list_key], list):
                data[list_key].append(line.lstrip()[2:].strip().strip("\"'"))
            continue
        if ":" not in line:
            continue
        key, _, value = line.partition(":")
        key = key.strip()
        value = strip_comment(value.strip())
        if value == "":
            data[key] = []
            list_key = key
        else:
            data[key] = parse_value(value)
            list_key = None

    body = "\n".join(lines[end + 1 :])
    return data, body, end + 1


def check_front_matter(fm: dict, file: Path, rel: Path, repo_root: Path, findings: Findings) -> None:
    for old, new in OLD_FRONT_MATTER_KEYS.items():
        if old in fm:
            findings.error(file, 1, f"old front-matter key `{old}` — rename it to `{new}` (schema 0.3.0)")
            fm.setdefault(new, fm[old])  # one error for the rename, not three for a value the checks cannot see

    for field in REQUIRED_FIELDS:
        if field not in fm:
            findings.error(file, 1, f"missing required front-matter field `{field}`")

    repo = fm.get("repo")
    module = fm.get("module")
    tags = fm.get("tags") or []
    if not isinstance(tags, list):
        findings.error(file, 1, "`tags` must be a list")
        tags = []

    confidence = fm.get("confidence")
    if confidence is not None and confidence not in CONFIDENCE_VALUES:
        findings.error(file, 1, f"`confidence: {confidence}` — allowed values are only {sorted(CONFIDENCE_VALUES)}")

    expected_type = expected_type_for(rel)

    type_tags = [t[len("type-") :] for t in tags if isinstance(t, str) and t.startswith("type-")]
    if len(type_tags) != 1:
        findings.error(file, 1, f"expected exactly one `type-*` tag, found {len(type_tags)}")
    else:
        doc_type = type_tags[0]
        if doc_type not in TYPES:
            hint = f" — rename to `type-{OLD_TYPES[doc_type]}` (schema 0.3.0)" if doc_type in OLD_TYPES else ""
            findings.error(file, 1, f"`type-{doc_type}` is not an allowed type ({sorted(TYPES)}){hint}")
        if expected_type and doc_type != expected_type:
            findings.error(file, 1, f"`type-{doc_type}` does not match the file location (expected `type-{expected_type}`)")

    if "ai-generated" not in tags:
        findings.error(file, 1, "`tags` must contain `ai-generated`")

    if repo:
        if f"repo-{repo}" not in tags:
            findings.error(file, 1, f"`tags` must contain `repo-{repo}` (per the `repo` field)")
        title = fm.get("title") or ""
        if not title.startswith(f"{repo} — "):
            findings.error(file, 1, f'`title` must start with "{repo} — ", found "{title}"')

    module_tags = [t for t in tags if isinstance(t, str) and t.startswith("module-")]
    if module:
        if f"module-{module}" not in tags:
            findings.error(file, 1, f"`tags` must contain `module-{module}` (per the `module` field)")
    else:
        if module_tags:
            findings.error(file, 1, f"`module: null`, but `tags` contains {module_tags}")
        if expected_type == "reference":
            findings.error(file, 1, "a reference document must have the `module` field filled in")

    date = fm.get("last-generated")
    if date and not RE_ISO_DATE.match(str(date)):
        findings.error(file, 1, f"`last-generated: {date}` is not an ISO date (YYYY-MM-DD)")

    covers = fm.get("covers")
    if covers is None:
        findings.error(file, 1, "`covers` must not be empty — list the paths the document describes")
    elif isinstance(covers, list):
        for path in covers:
            target = inside(repo_root, path)
            if target is None:
                findings.error(file, 1, f"`covers` path `{path}` escapes the repository root")
            elif not target.exists():
                findings.error(file, 1, f"`covers` points to a non-existent path `{path}`")


def expected_type_for(rel: Path) -> str | None:
    """Document type from its location — not from the tag (a broken tag must not disable the check)."""
    if rel.parent.name == "reference":
        return "reference"
    return TYPE_BY_FILENAME.get(rel.name)


# --- headings and body ------------------------------------------------------------------------


def outside_code_blocks(body: str) -> list[tuple[int, str]]:
    """Body lines outside fenced code blocks, with their original index (0-based in body)."""
    out = []
    in_block = False
    for i, line in enumerate(body.split("\n")):
        if line.lstrip().startswith("```"):
            in_block = not in_block
            continue
        if not in_block:
            out.append((i, line))
    return out


def check_headings(body: str, fm: dict, file: Path, rel: Path, offset: int, findings: Findings) -> None:
    module = fm.get("module")
    is_reference = expected_type_for(rel) == "reference"

    headings = []
    for i, line in outside_code_blocks(body):
        if not line.startswith("## "):
            continue
        line_no = offset + i + 1
        m = RE_H2_HEADING.match(line)
        if not m:
            findings.error(file, line_no, f"H2 heading does not match the pattern `## <Subject>: <section>`: {line.strip()!r}")
            continue
        subject = m.group("subject").strip()
        headings.append((line_no, subject, m.group("section").strip(), i))
        if is_reference and module and subject != module:
            findings.error(
                file,
                line_no,
                f"heading subject `{subject}` does not match `module: {module}` — the subject must be the full module name",
            )

    if len(headings) < MIN_H2 or len(headings) > MAX_H2:
        findings.warning(file, 1, f"page has {len(headings)} H2 sections, recommended {MIN_H2}–{MAX_H2}")

    # Section size (rough token estimate) + the first sentence must carry the subject.
    minimum = SECTION_TOKENS_MIN_SHARED if expected_type_for(rel) in SHARED_TYPES else SECTION_TOKENS_MIN
    body_lines = body.split("\n")
    for idx, (line_no, subject, _section, i) in enumerate(headings):
        end = headings[idx + 1][3] if idx + 1 < len(headings) else len(body_lines)
        content = body_lines[i + 1 : end]
        words = len(" ".join(content).split())
        tokens = int(words * TOKENS_PER_WORD)
        if tokens > SECTION_TOKENS_MAX:
            findings.warning(file, line_no, f"section `{subject}` has ~{tokens} tokens (target 250–600)")
        elif tokens < minimum:
            findings.warning(file, line_no, f"section `{subject}` has ~{tokens} tokens — too few for standalone retrieval")

        first = next((r.strip() for r in content if r.strip() and not r.strip().startswith(">")), "")
        if first and subject.lower() not in first.lower():
            findings.warning(
                file,
                line_no,
                f"the first sentence of section `{subject}` does not repeat the subject by full name: {first[:60]!r}",
            )


def check_body(body: str, file: Path, rel: Path, offset: int, docs_root: Path, repo_root: Path, findings: Findings) -> None:
    if not RE_META_LINE.search(body):
        findings.error(file, offset + 1, "missing plain-text metadata line `Repo: … · Module: … · Type: …`")

    for i, line in outside_code_blocks(body):
        line_no = offset + i + 1
        lowered = line.lower()
        for phrase in FORBIDDEN_PHRASES:
            if phrase in lowered:
                findings.error(file, line_no, f"forbidden phrase {phrase!r} — a section must make sense without its surroundings")

        for m in RE_ANGLE_TOKEN.finditer(RE_CODE_SPAN.sub("", line)):
            findings.error(
                file,
                line_no,
                f"angle-bracket token `{m.group(0)}` outside a code span — the Confluence converter reads it as HTML;"
                " put it in backticks or drop the generic argument from the anchor",
            )

        # [VERIFY: file#Symbol] / [VERIFY: file:line] anchors
        for m in RE_VERIFY.finditer(line):
            check_anchor(m.group(1).strip(), file, line_no, repo_root, findings)

        # Internal links: relative to the document (as the publisher resolves them) or to ai-docs/.
        for m in RE_LINK.finditer(line):
            target = m.group(1).split("#")[0].strip()
            if not target or target.startswith(("http://", "https://", "mailto:")):
                continue
            from_file = inside(docs_root, str(file.parent / target))
            from_root = inside(docs_root, target)
            if (from_file and from_file.exists()) or (from_root and from_root.exists()):
                continue
            if from_file is None:
                findings.error(
                    file,
                    line_no,
                    f"link `{target}` must stay inside ai-docs/ (the publisher rejects references outside the published root)",
                )
            else:
                findings.error(file, line_no, f"internal link `{target}` does not resolve to an existing file")

    _check_tables(body, file, offset, findings)


def _check_tables(body: str, file: Path, offset: int, findings: Findings) -> None:
    start = None
    rows = 0
    for i, line in outside_code_blocks(body) + [(len(body.split("\n")), "")]:
        is_table = line.strip().startswith("|")
        if is_table:
            if start is None:
                start = i
                rows = 0
            elif not set(line.strip()) <= set("|-: "):
                rows += 1
        else:
            if start is not None and rows > MAX_TABLE_ROWS:
                findings.warning(file, offset + start + 1, f"table has {rows} data rows, recommended max {MAX_TABLE_ROWS}")
            start = None


# --- anchors and links ------------------------------------------------------------------------


@functools.lru_cache(maxsize=256)
def _read(path: str) -> str:
    """Anchor targets are read once per run — a module document anchors the same file hundreds of times.

    The cache lives for the process: the CLI is one-shot, so nothing ever needs invalidation.
    """
    return Path(path).read_text(encoding="utf-8", errors="replace")


def read_source(target: str, repo_root: Path, file: Path, line_no: int | None, findings: Findings) -> str | None:
    """Read a file an anchor points to. Reports and returns None when that is not possible."""
    target_path = inside(repo_root, target)
    if target_path is None:
        findings.error(file, line_no, f"[VERIFY:] `{target}` escapes the repository root")
        return None
    if not target_path.is_file():
        findings.error(file, line_no, f"[VERIFY:] points to a non-existent file `{target}`")
        return None
    try:
        return _read(str(target_path))
    except OSError as e:
        findings.error(file, line_no, f"[VERIFY:] file `{target}` cannot be read: {e}")
        return None


def check_anchor(anchor: str, file: Path, line_no: int | None, repo_root: Path, findings: Findings) -> None:
    """Validate one [VERIFY:] anchor in either the symbol or the line form.

    The symbol form is the contract's default: a line number is invalidated by any commit
    above it, a symbol survives and doubles as the "names must be greppable" check.
    """
    target_path = re.split(r"[#:]", anchor, maxsplit=1)[0].strip().replace("\\", "/")
    if target_path.endswith("ai-docs/ast/map.json"):
        findings.error(
            file,
            line_no,
            "[VERIFY:] must not point into `ai-docs/ast/map.json` (flat JSON, keys repeat) — cite"
            " `ai-docs/ast/map.md` with `path:line` or state the number without an anchor",
        )
        return
    symbol_match = RE_ANCHOR_SYMBOL.match(anchor)
    if symbol_match:
        target = symbol_match.group("path").strip()
        symbol = symbol_match.group("symbol").strip()
        if not symbol:
            findings.error(file, line_no, f"[VERIFY:] `{anchor}` has an empty symbol after `#`")
            return
        text = read_source(target, repo_root, file, line_no, findings)
        if text is None:
            return
        hits = text.count(symbol)
        if hits == 0:
            findings.error(file, line_no, f"[VERIFY:] symbol `{symbol}` is not present in `{target}`")
        elif hits > MAX_SYMBOL_HITS:
            findings.warning(
                file,
                line_no,
                f"[VERIFY:] symbol `{symbol}` occurs {hits}× in `{target}` — anchor on something narrower",
            )
        return

    line_match = RE_ANCHOR_LINE.match(anchor)
    if line_match:
        target = line_match.group("path").strip()
        target_line = int(line_match.group("line"))
        text = read_source(target, repo_root, file, line_no, findings)
        if text is None:
            return
        total = len(text.split("\n"))
        if target_line < 1 or target_line > total:
            findings.error(file, line_no, f"[VERIFY:] `{target}:{target_line}` is out of file range (1–{total})")
        return

    findings.error(
        file,
        line_no,
        f"[VERIFY: {anchor}] is neither `path#Symbol` nor `path:line`",
    )


# --- secrets ----------------------------------------------------------------------------------


def _allowlisted_host(line: str, start: int) -> bool:
    """Whether the host token beginning at `start` ends with an allow-listed domain."""
    host = RE_HOST_TOKEN.match(line, start).group(0).lower().rstrip(".")
    return any(host == entry or host.endswith("." + entry) for entry in HOST_ALLOWLIST)


def check_secrets(text: str, file: Path, findings: Findings, manifest: bool = False) -> None:
    """Report values that must never be published — the whole text, fenced code blocks included.

    Anchors, inline-code anchors and git SSH URLs are removed from a line before matching,
    never the whole line: `Password=hunter2 [VERIFY: src/x.cs#Y]` is still a leaked password.
    A line opts out with `<!-- secret-ok: <reason> -->`; the reason is printed as info.
    With `manifest=True` the text is YAML data, not prose: `password: SuperSecret123` is a value
    (no type-name exception) and the `repo:` line is the source URL, not an address to review.
    """
    for line_no, raw in enumerate(text.split("\n"), start=1):
        exception = SECRET_OK.search(raw)
        if exception:
            findings.info(file, line_no, f"secret check skipped, secret-ok: {exception.group(1)}")
            continue
        line = RE_GIT_SSH_URL.sub("", RE_CODE_ANCHOR.sub("", RE_VERIFY.sub("", raw)))

        for name, pattern in (MANIFEST_SECRET_PATTERNS if manifest else SECRET_PATTERNS).items():
            for m in pattern.finditer(line):
                if name == "internal-host" and _allowlisted_host(line, m.start()):
                    continue
                findings.error(
                    file,
                    line_no,
                    f"looks like a secret or an internal value ({name}) — write the key or where the value lives, never the value",
                )
                break

        if manifest and line.lstrip().startswith("repo:"):
            continue  # the source URL of the manifest, not an address to review
        for name, pattern in WARN_PATTERNS.items():
            if pattern.search(line):
                findings.warning(file, line_no, f"may be a private address or personal data ({name}) — check before publishing")


# --- manifest ---------------------------------------------------------------------------------


def expand_cases(name: str) -> list[str]:
    """Expand `Envelope.{CaseA,CaseB}` into the individual case names.

    Grouped entries are one manifest row but several names in the prose, and the
    cross-repo aggregator matches on the expanded names too.
    """
    # Only `Envelope.{A,B}` is a group: a route such as `GET /todos/{id}` keeps its braces.
    if ".{" in name and name.endswith("}"):
        envelope, _, group = name.partition(".{")
        return [f"{envelope}.{case.strip()}" for case in group[:-1].split(",") if case.strip()]
    return [name]


def _import_yaml(path: Path, findings: Findings, require_yaml: bool):
    """PyYAML when it is installed; otherwise None after a warning (an error with --require-yaml)."""
    try:
        import yaml  # noqa: PLC0415 — optional: the rest of the validator stays dependency-free
    except ImportError:
        if require_yaml:
            findings.error(path, None, "PyYAML is required in CI: pip install pyyaml")
        else:
            findings.warning(
                path,
                None,
                "PyYAML is not installed — manifest checks skipped, drift between manifest and prose not checked"
                " (`pip install pyyaml`)",
            )
        return None
    return yaml


def _load_manifest(path: Path, findings: Findings, require_yaml: bool) -> tuple[dict | None, bool]:
    """Parse a manifest or a fragment. Returns (mapping or None, whether the checks could run at all)."""
    yaml = _import_yaml(path, findings, require_yaml)
    if yaml is None:
        return None, False
    try:
        data = yaml.safe_load(path.read_text(encoding="utf-8"))
    except yaml.YAMLError as e:
        findings.error(path, None, f"manifest is not valid YAML: {e}")
        return None, True
    if not isinstance(data, dict):
        findings.error(path, None, "manifest must be a mapping at the top level")
        return None, True
    return data, True


def _check_old_manifest_keys(data: dict, path: Path, findings: Findings) -> set[str]:
    """Reject pre-0.3.0 top-level keys with the rename, not silently. Returns the new names they stand for.

    `repo:` has two meanings: the old repository id (error) and the optional source git URL (fine, schema 0.3.0).
    """
    renamed = set()
    for old, new in OLD_MANIFEST_KEYS.items():
        if old not in data:
            continue
        if old == "repo" and isinstance(data[old], str) and RE_GIT_URL.match(data[old]):
            continue
        renamed.add(new)
        hint = "; `repo:` is only for the source git URL" if old == "repo" else ""
        findings.error(path, None, f"manifest uses the old key `{old}` — rename it to `{new}` (schema 0.3.0){hint}")
    return renamed


def _mappings(data: dict, section: str) -> list[dict]:
    return [entry for entry in data.get(section) or [] if isinstance(entry, dict)]


def _entry_value(section: str, entry: dict, key: str):
    """The value under `key`, or under the pre-0.3.0 key that stands for it — reported once, as a rename."""
    if entry.get(key) is not None:
        return entry[key]
    for old, new in OLD_ENTRY_KEYS.get(section, {}).items():
        if new == key and entry.get(old) is not None:
            return entry[old]
    return None


def known_dependency_ids(data: dict, service, extra=()) -> set[str]:
    """Ids a dependency line may name: module ids, called services, owners of external data, aliases,
    the repo id, whatever the caller adds (the module's own id under --partial), and `unknown`."""
    ids = set(DEPENDENCY_ID_ALWAYS_KNOWN) | {str(value).strip() for value in extra}
    if service:
        ids.add(str(service).strip())
    values = [
        *(module.get("name") for module in _mappings(data, "modules")),
        *(_entry_value("calls", call, "service") for call in _mappings(data, "calls")),
        *(entry.get("owner") for entry in _mappings(data, "reads_external_data")),
        *(data.get("aliases") or []),
        # A data store is a legitimate `calls →` target; it is named by its engine (`sqlite/Todos`).
        *(str(entry["store"]).split("/")[0] for entry in _mappings(data, "owns_data") if entry.get("store")),
    ]
    ids.update(str(value).strip() for value in values if value is not None)
    return ids


def check_dependency_lines(
    data: dict, service, lines: list[tuple[Path, int, str]], findings: Findings, extra=(), partial: bool = False
) -> None:
    """Drift, prose -> manifest: every id a dependency line names must be one the manifest knows.

    Under --partial a fragment cannot know its sibling modules yet, so an unknown id is a warning
    there; the full run after assembly reports it as an error."""
    known = known_dependency_ids(data, service, extra)
    for file, line_no, target in lines:
        if target in known:
            continue
        if partial:
            findings.warning(file, line_no, f"dependency line names `{target}`, which this fragment does not know; checked again after assembly")
        else:
            findings.error(file, line_no, f"dependency line names `{target}`, which the manifest does not know")


def _check_manifest_entries(
    data: dict, path: Path, docs_root: Path, repo_root: Path, prose: str, findings: Findings
) -> None:
    """The entry checks shared by the manifest and its fragments: modules, the six sections, aliases, unknowns.

    `prose` is the text every named entry must be mentioned in (drift, manifest -> prose): the bodies
    of every page in the full run, of the one document under --partial.
    """
    for module in data.get("modules") or []:
        if not isinstance(module, dict):
            findings.error(path, None, f"`modules` entry is not a mapping: {module!r}")
            continue
        name, mod_path, doc = module.get("name"), module.get("path"), module.get("doc")
        if not name or not mod_path or not doc:
            findings.error(path, None, f"`modules` entry needs `name`, `path` and `doc`: {module!r}")
            continue
        _check_canonical_id(name, "modules", path, findings)
        if not (repo_root / mod_path).exists():
            findings.error(path, None, f"module `{name}` points to a non-existent path `{mod_path}`")
        if not (docs_root / doc.split("#")[0]).exists():
            findings.error(path, None, f"module `{name}` points to a non-existent document `{doc}`")

    for section, key in MANIFEST_SECTIONS.items():
        for entry in data.get(section) or []:
            if not isinstance(entry, dict):
                findings.error(path, None, f"`{section}` entry is not a mapping: {entry!r}")
                continue
            old_keys = {old: new for old, new in OLD_ENTRY_KEYS.get(section, {}).items() if old in entry}
            for old, new in old_keys.items():
                findings.error(path, None, f"`{section}` entry uses the old key `{old}` — rename it to `{new}` (schema 0.3.0): {entry[old]!r}")
            name = entry.get(key)
            if not name:
                if key not in old_keys.values():
                    findings.error(path, None, f"`{section}` entry is missing `{key}`: {entry!r}")
                continue
            if section == "calls":
                _check_canonical_id(name, section, path, findings)
            if section == "reads_external_data" and entry.get("owner"):
                _check_canonical_id(entry["owner"], "reads_external_data owner", path, findings)
            evidence = entry.get("evidence")
            if not evidence:
                findings.error(path, None, f"`{section}: {name}` has no `evidence:` anchor")
            else:
                check_anchor(str(evidence).strip(), path, None, repo_root, findings)
            _check_mentioned(str(name), section, prose, path, findings)

    for alias in data.get("aliases") or []:
        _check_mentioned(str(alias), "aliases", prose, path, findings)

    for unknown in data.get("unknowns") or []:
        if not isinstance(unknown, dict):
            findings.error(path, None, f"`unknowns` entry is not a mapping: {unknown!r}")
            continue
        for field in ("question", "checked", "resolved_by"):
            if not unknown.get(field):
                findings.error(path, None, f"`unknowns` entry is missing `{field}`: {unknown.get('question', unknown)!r}")


def _check_canonical_id(value, section: str, path: Path, findings: Findings) -> None:
    if not RE_CANONICAL_ID.match(str(value).strip()):
        findings.error(
            path,
            None,
            f"`{value}` ({section}) is not a canonical id — lowercase letters, digits, `-` and `.` only"
            " (an external system is `smtp`, not `SMTP (MAILER_DSN)`; its variable belongs in the prose)",
        )


def _check_mentioned(value: str, section: str, prose: str, path: Path, findings: Findings) -> None:
    """Drift, manifest -> prose: a grouped `Envelope.{A,B}` counts as mentioned when any of its cases is."""
    if not any(case in prose for case in expand_cases(value)):
        findings.error(path, None, f"manifest entry `{value}` ({section}) is not mentioned in any page")


def check_manifest(
    docs_root: Path,
    repo_root: Path,
    prose: str,
    doc_repos: set,
    findings: Findings,
    require_yaml: bool = False,
    dependency_lines: list[tuple[Path, int, str]] = (),
) -> bool:
    """Validate `ai-docs/manifest.yaml`. Returns False when the checks had to be skipped.

    `dependency_lines` are the (file, line, id) triples of every `calls → X` line and its kin in the
    prose; each id must be one the manifest knows.
    """
    path = docs_root / MANIFEST_NAME
    if not path.is_file():
        findings.errors.append(
            f"{docs_root}: missing `{MANIFEST_NAME}` — the machine-readable inventory is part of the output"
        )
        return True

    data, checked = _load_manifest(path, findings, require_yaml)
    if data is None:
        return checked

    renamed = _check_old_manifest_keys(data, path, findings)
    for field in MANIFEST_REQUIRED_FIELDS:
        if not data.get(field) and field not in renamed:
            findings.error(path, None, f"missing required field `{field}`")

    service = data.get("service")
    if service and doc_repos and service not in doc_repos:
        findings.error(path, None, f"`service: {service}` does not match the `repo` of the documents ({sorted(doc_repos)})")

    _check_manifest_entries(data, path, docs_root, repo_root, prose, findings)
    check_dependency_lines(data, service, list(dependency_lines), findings)
    return True


def check_manifest_part(
    path: Path,
    docs_root: Path,
    repo_root: Path,
    prose: str,
    findings: Findings,
    require_yaml: bool = False,
    dependency_lines: list[tuple[Path, int, str]] = (),
    module: str | None = None,
) -> bool:
    """Validate one `manifest.part.<module>.yaml` against the prose of its document (`--partial`).

    A fragment carries the entries of one module and no header, so the required header fields
    are not demanded; old keys and the entries are checked exactly as in the manifest. The drift
    check runs against the part, and the document's own `module` counts as a known id.
    Returns False when the checks had to be skipped.
    """
    data, checked = _load_manifest(path, findings, require_yaml)
    if data is None:
        return checked
    _check_old_manifest_keys(data, path, findings)
    _check_manifest_entries(data, path, docs_root, repo_root, prose, findings)
    check_dependency_lines(data, data.get("service"), list(dependency_lines), findings, extra=[module] if module else (), partial=True)
    return True


# --- CLI --------------------------------------------------------------------------------------


def main() -> int:
    ap = argparse.ArgumentParser(
        description="ai-docs validator (skill generate-docs)",
        epilog=(
            "CI always runs the full mode (every document, index.md, manifest.yaml, drift between manifest and prose). "
            "--partial is for local per-module work: it validates one document — with --manifest-part "
            "also the module's manifest fragment and the drift against it — and skips the index.md and "
            "manifest.yaml requirements."
        ),
    )
    ap.add_argument("docs", help="path to the ai-docs directory")
    ap.add_argument("--repo-root", help="repository root (default: parent of ai-docs)")
    ap.add_argument("--strict", action="store_true", help="treat warnings as errors")
    ap.add_argument(
        "--partial",
        metavar="DOC",
        help="validate only this document (path relative to ai-docs); local per-module work, not CI",
    )
    ap.add_argument(
        "--manifest-part",
        metavar="PATH",
        help="with --partial: the module's manifest.part.<module>.yaml, validated against that document",
    )
    ap.add_argument(
        "--require-yaml",
        action="store_true",
        help="fail when PyYAML is missing instead of skipping the manifest checks (CI)",
    )
    args = ap.parse_args()
    if args.manifest_part and not args.partial:
        ap.error("--manifest-part needs --partial")

    docs_root = Path(args.docs).resolve()
    if not docs_root.is_dir():
        print(f"ERROR: `{docs_root}` is not a directory", file=sys.stderr)
        return 2

    repo_root = Path(args.repo_root).resolve() if args.repo_root else docs_root.parent

    manifest_part: Path | None = None
    if args.partial:
        doc = inside(docs_root, args.partial)
        if doc is None or not doc.is_file() or doc.suffix != ".md":
            print(f"ERROR: `--partial {args.partial}` is not a .md file inside `{docs_root}`", file=sys.stderr)
            return 2
        files = [doc]
        if args.manifest_part:
            manifest_part = Path(args.manifest_part).resolve()
            if not manifest_part.is_file():
                print(f"ERROR: `--manifest-part {args.manifest_part}` is not a file", file=sys.stderr)
                return 2
    else:
        # ast/ is produced by a different tool — exclude the whole directory from YAML checks.
        files = sorted(p for p in docs_root.rglob("*.md") if "ast" not in p.relative_to(docs_root).parts)
        if not files:
            print(f"ERROR: no .md files in `{docs_root}` (outside ast/)", file=sys.stderr)
            return 2

    findings = Findings()
    repos = set()
    prose_parts = []
    dependency_lines: list[tuple[Path, int, str]] = []  # (file, line, id) of every `calls → X` line and its kin
    modules = set()

    for file in files:
        rel = file.relative_to(docs_root)
        if rel.name in OLD_FILENAMES:
            findings.error(file, None, f"old file name `{rel.name}` — rename it to `{OLD_FILENAMES[rel.name]}` (schema 0.3.0)")
        text = file.read_text(encoding="utf-8")
        check_secrets(text, file, findings)
        fm, body, offset = parse_front_matter(text, file, findings)
        if fm is None:
            continue
        if fm.get("repo"):
            repos.add(fm["repo"])
        if fm.get("module"):
            modules.add(str(fm["module"]))
        # Fenced blocks (a Mermaid diagram, a YAML example) are invisible to retrieval, so a name
        # that appears only there does not count as mentioned.
        prose_parts.append("\n".join(line for _, line in outside_code_blocks(body)))
        for i, line in outside_code_blocks(body):
            m = RE_DEPENDENCY_LINE.match(line)
            if m:
                dependency_lines.append((file, offset + i + 1, m.group("id").rstrip(".;:")))
        check_front_matter(fm, file, rel, repo_root, findings)
        check_headings(body, fm, file, rel, offset, findings)
        check_body(body, file, rel, offset, docs_root, repo_root, findings)

    prose = "\n".join(prose_parts)
    if args.partial:
        # One module's work in progress: the index, the manifest and the agreement of `repo`
        # across documents are the full run's business, and CI always runs the full mode.
        manifest_checked = True
        if manifest_part:
            check_secrets(manifest_part.read_text(encoding="utf-8"), manifest_part, findings, manifest=True)
            manifest_checked = check_manifest_part(
                manifest_part,
                docs_root,
                repo_root,
                prose,
                findings,
                args.require_yaml,
                dependency_lines,
                module=next(iter(modules), None),
            )
    else:
        if len(repos) > 1:
            findings.errors.append(f"{docs_root}: inconsistent `repo` across documents: {sorted(repos)}")

        if not (docs_root / "index.md").exists():
            findings.errors.append(f"{docs_root}: missing required index page `index.md`")

        # The manifest and its per-module fragments are scanned raw: a leaked value hides in YAML as well.
        for yaml_file in [docs_root / MANIFEST_NAME, *sorted(docs_root.glob(MANIFEST_PART_GLOB))]:
            if yaml_file.is_file():
                check_secrets(yaml_file.read_text(encoding="utf-8"), yaml_file, findings, manifest=True)

        manifest_checked = check_manifest(
            docs_root, repo_root, prose, repos, findings, args.require_yaml, dependency_lines
        )

    for message in findings.infos:
        print(f"INFO     {message}")
    for message in findings.warnings:
        print(f"WARNING  {message}")
    for message in findings.errors:
        print(f"ERROR    {message}")

    print(
        f"\nChecked {len(files)} files"
        + ("" if manifest_checked else " (manifest checks skipped)")
        + f": {len(findings.errors)} errors, {len(findings.warnings)} warnings."
    )

    if findings.errors or (args.strict and findings.warnings):
        return 1
    return 0


if __name__ == "__main__":
    sys.exit(main())
