#!/usr/bin/env python3
"""Assemble `ai-docs/manifest.yaml` from the per-module parts of a team-mode generation.

Runs on the INTEGRATOR's machine as the last step of team mode: after every generator has produced
their module (`reference/<module-id>.md` plus `ai-docs/manifest.part.<module-id>.yaml`) and after a
verifier has checked every reference document in a fresh session (`templates/verify.md`, which
writes a record into `ai-docs-review/` at the repository root — one file per document,
`<doc-id>.md` with `<doc-id>` = the document's path under `ai-docs/`, `/` -> `-`, no extension
(`reference-orders.md`), so that parallel verifiers do not collide; the shared `REVIEW.md` stays accepted).

Usage:
    assemble.py <ai-docs dir> --service <id> --description "<one sentence>"
                [--draft-only] [--keep-parts] [--review-file <path>] [--mixed-commits]

What it does:
  * loads every `manifest.part.*.yaml` (none at all: cannot run, exit 2); all parts must carry the
    same `source_commit` and — where present — a `service` equal to `--service`, otherwise exit 1
    naming the part and nothing is written;
  * `--mixed-commits` accepts parts generated at different commits — a team that documents its
    modules over several days — when git proves that no source file changed between each part's
    commit and `HEAD`: only `ai-docs/`, `ai-docs-review/` and `.claude/` may differ, the manifest
    then records `HEAD`, and the documents keep their own `source-commit` (that is what
    `stale_check.py` watches). One changed source file is exit 1 naming the part, the file and the
    module to regenerate;
  * merges `assumptions` (what each run had to assume: branch, commit, anything not determinable)
    into the top-level `assumptions:` list — sorted, deduped, strings only — an extension key the
    skill copies into `index.md` under "generation assumptions"; omitted when no part has any;
  * passes a `repo:` through when a part carries one: it is the source git URL (`git@…`, `ssh://`,
    `http://`, `https://`; needed only when `ai-docs/` lives outside the code repo), never the repo
    id — that is `service`. Any other value is exit 1 naming the part;
  * merges the sections `modules, publishes, consumes, exposes, calls, owns_data,
    reads_external_data, frontends, unknowns`: concatenated, exact duplicates dropped, sorted by the
    entry's identifying key (`name` / `entity` / `service` / `question`) and then by `module`, so the
    result does not depend on the order of the parts or of the entries inside them;
  * merges `aliases` (sorted, deduped) and builds the header from the arguments, the shared
    `source_commit` and today's date; an empty section is omitted (manifest rule 7);
  * verification gate — unless `--draft-only`, every `reference/*.md` needs a record in ANY `*.md`
    under `<repo root>/ai-docs-review/` (`REVIEW.md` or `<doc-id>.md`; `--review-file` names one
    file instead) whose heading `## <path relative to repo> @ <commit> — <date>` names the document
    and a commit that is a prefix of (or extends) the document's `source-commit`; a missing record is
    listed as `not verified: reference/<x>.md @ <commit> — run templates/verify.md in a fresh
    session first`, a document without `source-commit` as `reference/<x>.md: no source-commit in
    front matter — run validate.py first`; either is exit 1, nothing written;
  * an existing `manifest.yaml` is one more part: it contributes the entries of every module that
    has no part in this run (`modules:` by `name`, the other sections by `module`), its `aliases`,
    `assumptions` and `unknowns` (deduplicated), and its `description` and `repo` when the run gives
    none. So a later `--scope` run of one module refreshes that module and keeps the rest; the
    kept documents keep their own `source-commit`, which is what `stale_check.py` watches;
  * writes `manifest.yaml` and deletes the parts and any `manifest.header.yaml` (kept with
    `--keep-parts`). `--draft-only` never touches the documents' `confidence`.

Exit 0 = assembled. 1 = the parts disagree, a `repo:` is not a git URL, or a document is not
verified. 2 = cannot run (no docs directory, no description and no manifest to keep it from, no parts). Findings go to stdout
(like validate.py); "cannot run" goes to stderr.

Requires PyYAML. Reuses the front-matter parser of validate.py.
"""

from __future__ import annotations

import argparse
import datetime as dt
import json
import re
import subprocess
import sys
from pathlib import Path

import yaml

sys.path.insert(0, str(Path(__file__).parent))
from validate import Findings, parse_front_matter  # noqa: E402

GENERATED_BY = "skill:generate-docs@0.3.5"
MANIFEST_NAME = "manifest.yaml"
PART_GLOB = "manifest.part.*.yaml"
HEADER_NAME = "manifest.header.yaml"
# Every `*.md` here is a record file: one per document (`reference-orders.md`), or the shared `REVIEW.md`.
DEFAULT_REVIEW_DIR = Path("ai-docs-review")
REVIEW_GLOB = "*.md"
VERIFY_HINT = "run templates/verify.md in a fresh session first"
# `repo:` in a part is the source git URL (the prefixes validate.py accepts too), never the repo id.
RE_GIT_URL = re.compile(r"^(git@|ssh://|http://|https://)\S+$")
# Paths that documentation work itself writes: a change here never invalidates a part under --mixed-commits.
DOC_PATHS = ("ai-docs/", "ai-docs-review/", ".claude/")
MIXED_HINT = "no source file changed between them: assemble with --mixed-commits"
RE_SHA = re.compile(r"^[0-9a-f]{7,40}$")

# Section -> key identifying an entry (the primary sort key; `module` is the secondary one).
# `frontends` is a plain list of names. The order here is the order in the written manifest.
SECTIONS: dict[str, str | None] = {
    "modules": "name",
    "publishes": "name",
    "consumes": "name",
    "exposes": "name",
    "calls": "service",
    "owns_data": "entity",
    "reads_external_data": "entity",
    "frontends": None,
    "unknowns": "question",
}

# `## ai-docs/reference/orders.md @ 3f2a9c1e0b44 — 2026-09-15` (en dash and hyphen tolerated).
RE_REVIEW_HEADING = re.compile(
    r"^##\s+(?P<doc>\S+)\s+@\s+(?P<sha>[0-9a-f]{7,40})\s+[—–-]\s+(?P<date>\d{4}-\d{2}-\d{2})\s*$",
    re.MULTILINE,
)


class AssembleError(Exception):
    """A finding that stops the assembly before anything is written (exit 1)."""


# --- parts ----------------------------------------------------------------------------------------


def load_parts(docs: Path) -> list[tuple[Path, dict]]:
    """Every part in `docs` in name order; empty when there is none (the caller decides that is "cannot run")."""
    parts = []
    for path in sorted(docs.glob(PART_GLOB)):
        try:
            data = yaml.safe_load(path.read_text(encoding="utf-8"))
        except yaml.YAMLError as e:
            raise AssembleError(f"{path.name}: not valid YAML: {e}") from None
        if not isinstance(data, dict):
            raise AssembleError(f"{path.name}: must be a mapping at the top level")
        parts.append((path, data))
    return parts


def load_existing(docs: Path) -> dict | None:
    """The `manifest.yaml` already in `docs`, or None on the first assembly."""
    path = docs / MANIFEST_NAME
    if not path.is_file():
        return None
    try:
        data = yaml.safe_load(path.read_text(encoding="utf-8"))
    except yaml.YAMLError as e:
        raise AssembleError(f"{MANIFEST_NAME}: not valid YAML: {e}") from None
    if not isinstance(data, dict):
        raise AssembleError(f"{MANIFEST_NAME}: must be a mapping at the top level")
    return data


def covered_modules(parts: list[tuple[Path, dict]]) -> set[str]:
    """The module ids this run brings: the part names, their `modules:` names and every `module:` value."""
    ids: set[str] = set()
    for path, data in parts:
        ids.add(path.name[len("manifest.part."):-len(".yaml")])
        for entry in data.get("modules") or []:
            if isinstance(entry, dict) and entry.get("name"):
                ids.add(str(entry["name"]))
        for section in SECTIONS:
            for entry in data.get(section) or []:
                if isinstance(entry, dict) and entry.get("module"):
                    ids.add(str(entry["module"]))
    return ids


def kept_part(existing: dict, covered: set[str]) -> tuple[Path, dict]:
    """The existing manifest as one more part: the entries of every module that has no part in this run."""
    kept: dict = {}
    for key in ("aliases", "assumptions"):
        if existing.get(key):
            kept[key] = existing[key]
    for section in SECTIONS:
        entries = existing.get(section)
        if not isinstance(entries, list):
            continue
        keep = []
        for entry in entries:
            owner = None
            if isinstance(entry, dict):
                owner = entry.get("name") if section == "modules" else entry.get("module")
            if owner is None or str(owner) not in covered:
                keep.append(entry)
        if keep:
            kept[section] = keep
    return (Path(MANIFEST_NAME), kept)


def kept_modules(kept: dict) -> int:
    return len(kept.get("modules") or [])


def shared_value(parts: list[tuple[Path, dict]], key: str, required: bool):
    """The one value of `key` every part agrees on; None when no part has it. Names the first part that differs."""
    first: tuple[Path, object] | None = None
    for path, data in parts:
        value = data.get(key)
        if value is None:
            if required:
                raise AssembleError(f"{path.name}: missing `{key}`")
            continue
        value = str(value).strip()
        if first is None:
            first = (path, value)
        elif value != first[1]:
            raise AssembleError(f"{path.name}: `{key}: {value}` differs from {first[0].name} (`{key}: {first[1]}`)")
    return None if first is None else first[1]


def git(repo_root: Path, *args: str) -> str:
    """One git command in the repository; a missing git or a failed command is a finding, never a guess."""
    try:
        r = subprocess.run(["git", "-C", str(repo_root), *args], capture_output=True, text=True)
    except OSError as e:
        raise AssembleError(f"--mixed-commits needs git and could not run it: {e}") from None
    if r.returncode != 0:
        raise AssembleError(f"git {' '.join(args)} failed: {r.stderr.strip() or r.returncode}")
    return r.stdout.strip()


def source_changes(repo_root: Path, sha: str, head: str) -> list[str]:
    """The files outside DOC_PATHS that changed between `sha` and `head`; empty means the source is the same."""
    names = git(repo_root, "diff", "--name-only", f"{sha}..{head}").splitlines()
    return sorted(n for n in names if n and not n.startswith(DOC_PATHS))


def part_commits(parts: list[tuple[Path, dict]]) -> list[tuple[Path, str]]:
    """(part, `source_commit`) for every part; a missing one or a value that is not a commit id stops the run."""
    out = []
    for path, data in parts:
        value = str(data.get("source_commit") or "").strip()
        if not value:
            raise AssembleError(f"{path.name}: missing `source_commit`")
        if not RE_SHA.fullmatch(value):
            raise AssembleError(f"{path.name}: `source_commit: {value}` is not a commit id")
        out.append((path, value))
    return out


def head_of_mixed_parts(parts: list[tuple[Path, dict]], repo_root: Path) -> str:
    """HEAD — once git proves no source file changed between any part's commit and HEAD (`--mixed-commits`)."""
    head = git(repo_root, "rev-parse", "HEAD")
    for path, sha in part_commits(parts):
        if head.startswith(sha):
            continue
        git(repo_root, "cat-file", "-e", f"{sha}^{{commit}}")  # a commit not in this history is a finding
        changed = source_changes(repo_root, sha, head)
        if changed:
            shown = ", ".join(changed[:5]) + (f" and {len(changed) - 5} more" if len(changed) > 5 else "")
            module = path.name[len("manifest.part."):-len(".yaml")]
            raise AssembleError(
                f"{path.name}: source changed since `source_commit: {sha[:12]}` ({shown}) — regenerate it with "
                f"`--scope {module} --draft-only` at HEAD {head[:12]}"
            )
    return head


def shared_source_commit(parts: list[tuple[Path, dict]], repo_root: Path) -> str:
    """The `source_commit` every part agrees on; a disagreement names the way out instead of only stating it."""
    try:
        return shared_value(parts, "source_commit", required=True)
    except AssembleError as e:
        raise AssembleError(f"{e} — {mixed_commits_hint(parts, repo_root)}") from None


def mixed_commits_hint(parts: list[tuple[Path, dict]], repo_root: Path) -> str:
    """What to do about parts from different commits: the cheap way out when git says the source is the same."""
    try:
        head_of_mixed_parts(parts, repo_root)
    except AssembleError:
        return "regenerate the older module at HEAD, or assemble with --mixed-commits when no source file changed"
    return MIXED_HINT


def check_service(parts: list[tuple[Path, dict]], expected: str) -> None:
    """Every part that names a `service` must name the one given as --service."""
    for path, data in parts:
        value = data.get("service")
        if value is not None and str(value).strip() != expected:
            raise AssembleError(f"{path.name}: `service: {value}` differs from --service `{expected}`")


def repo_url(parts: list[tuple[Path, dict]]) -> str | None:
    """The `repo:` the parts agree on — the source git URL, never the repo id; None when no part has one."""
    for path, data in parts:
        value = data.get("repo")
        if value is not None and not RE_GIT_URL.fullmatch(str(value).strip()):
            raise AssembleError(
                f"part {path.name}: `repo:` must be the source git URL (or be omitted) — the id belongs in `service:`"
            )
    return shared_value(parts, "repo", required=False)


def canonical(entry) -> str:
    """Order-independent identity of an entry, used to drop exact duplicates and as the last sort key."""
    return json.dumps(entry, sort_keys=True, ensure_ascii=False, default=str)


def entry_key(entry, key: str) -> tuple[str, str, str]:
    if isinstance(entry, dict):
        return (str(entry.get(key, "")), str(entry.get("module", "")), canonical(entry))
    return (str(entry), "", canonical(entry))


def merge_section(section: str, key: str | None, parts: list[tuple[Path, dict]]) -> list:
    merged, seen = [], set()
    for path, data in parts:
        entries = data.get(section)
        if entries is None:
            continue
        if not isinstance(entries, list):
            raise AssembleError(f"{path.name}: `{section}` must be a list")
        for entry in entries:
            identity = canonical(entry)
            if identity not in seen:
                seen.add(identity)
                merged.append(entry)
    if key is None:
        return sorted(merged, key=str)
    return sorted(merged, key=lambda e: entry_key(e, key))


def merge_assumptions(parts: list[tuple[Path, dict]]) -> list[str]:
    """The run assumptions of every part: strings only, sorted, exact duplicates dropped."""
    for path, data in parts:
        entries = data.get("assumptions")
        if entries is not None and (not isinstance(entries, list) or not all(isinstance(e, str) for e in entries)):
            raise AssembleError(f"{path.name}: `assumptions` must be a list of strings")
    return merge_section("assumptions", None, parts)


def build_manifest(
    service: str, description: str, source_commit: str, parts: list[tuple[Path, dict]], today: dt.date, existing: dict | None = None
) -> dict:
    manifest: dict = {"service": service}
    repo = repo_url(parts) or (existing or {}).get("repo")
    if repo:
        manifest["repo"] = repo
    manifest["description"] = description
    if existing:
        parts = parts + [kept_part(existing, covered_modules(parts))]
    aliases = merge_section("aliases", None, parts)
    if aliases:
        manifest["aliases"] = aliases
    manifest["generated-by"] = GENERATED_BY
    manifest["source_commit"] = source_commit
    manifest["last-generated"] = today  # a date object is written unquoted (`2026-09-15`), a string would be quoted
    assumptions = merge_assumptions(parts)
    if assumptions:
        manifest["assumptions"] = assumptions
    for section, key in SECTIONS.items():
        entries = merge_section(section, key, parts)
        if entries:
            manifest[section] = entries
    return manifest


# --- verification gate ----------------------------------------------------------------------------


def review_files(review: Path) -> list[Path]:
    """The record files to read: one file, or every `*.md` of a directory; none when neither exists."""
    if review.is_file():
        return [review]
    if review.is_dir():
        return sorted(p for p in review.glob(REVIEW_GLOB) if p.is_file())
    return []


def review_records(review: Path) -> list[tuple[str, str]]:
    """(document path relative to the repo, commit) for every record heading in the review file(s)."""
    records = []
    for path in review_files(review):
        text = path.read_text(encoding="utf-8", errors="replace").lstrip("\N{BYTE ORDER MARK}").replace("\r\n", "\n")
        records.extend((m.group("doc"), m.group("sha")) for m in RE_REVIEW_HEADING.finditer(text))
    return records


def same_commit(document_sha: str, record_sha: str) -> bool:
    return bool(document_sha) and (document_sha.startswith(record_sha) or record_sha.startswith(document_sha))


def document_commit(path: Path) -> str:
    text = path.read_text(encoding="utf-8", errors="replace").lstrip("\N{BYTE ORDER MARK}").replace("\r\n", "\n")
    front_matter, _, _ = parse_front_matter(text, path, Findings())  # validate.py reports defects
    return str((front_matter or {}).get("source-commit") or "").strip()


def unverified(docs: Path, repo_root: Path, records: list[tuple[str, str]]) -> list[str]:
    lines = []
    for path in sorted((docs / "reference").glob("*.md")):
        shown = path.relative_to(docs).as_posix()
        sha = document_commit(path)
        if not sha:
            lines.append(f"{shown}: no source-commit in front matter — run validate.py first")
            continue
        # A record names the document from the repository root (`ai-docs/reference/x.md`); a heading
        # written from `ai-docs/` (`reference/x.md`) means the same document and counts too.
        names = {path.relative_to(repo_root).as_posix(), shown}
        if not any(doc in names and same_commit(sha, record_sha) for doc, record_sha in records):
            lines.append(f"not verified: {shown} @ {sha[:8]} — {VERIFY_HINT}")
    return lines


# --- entry point ----------------------------------------------------------------------------------


def parse_args(argv: list[str] | None) -> argparse.Namespace:
    parser = argparse.ArgumentParser(description="Assemble ai-docs/manifest.yaml from manifest.part.*.yaml (team mode).")
    parser.add_argument("docs", help="the ai-docs directory holding the parts")
    parser.add_argument("--service", required=True, help="canonical repo id, identical to the documents' `repo:`")
    parser.add_argument("--description", help="one sentence on what the repo does; required on the first assembly, later the existing manifest keeps it")
    parser.add_argument("--draft-only", action="store_true", help="skip the verification gate; the result must not be published")
    parser.add_argument("--keep-parts", action="store_true", help="do not delete the parts and manifest.header.yaml")
    parser.add_argument("--review-file", help="one file of verification records (default: every *.md in <repo root>/ai-docs-review/)")
    parser.add_argument("--mixed-commits", action="store_true", help="accept parts from different commits when git proves no source file changed between them and HEAD")
    return parser.parse_args(argv)


def main(argv: list[str] | None = None) -> int:
    args = parse_args(argv)
    if hasattr(sys.stdout, "reconfigure"):
        sys.stdout.reconfigure(errors="replace")  # `—` must not crash an ASCII console
    docs = Path(args.docs).resolve()
    if not docs.is_dir():
        print(f"ERROR: `{docs}` is not a directory", file=sys.stderr)
        return 2
    repo_root = docs.parent
    review = Path(args.review_file).resolve() if args.review_file else repo_root / DEFAULT_REVIEW_DIR

    try:
        existing = load_existing(docs)
        description = (args.description or "").strip() or str((existing or {}).get("description") or "").strip()
        if not description:
            print(f"ERROR: --description must not be empty (no {MANIFEST_NAME} to keep it from)", file=sys.stderr)
            return 2
        parts = load_parts(docs)
        if not parts:
            print(f"ERROR: no {PART_GLOB} in `{docs}`", file=sys.stderr)
            return 2
        if existing and existing.get("service") is not None and str(existing["service"]).strip() != args.service:
            raise AssembleError(f"{MANIFEST_NAME}: `service: {existing['service']}` differs from --service `{args.service}`")
        source_commit = head_of_mixed_parts(parts, repo_root) if args.mixed_commits else shared_source_commit(parts, repo_root)
        check_service(parts, args.service)
        manifest = build_manifest(args.service, description, source_commit, parts, dt.date.today(), existing)
        kept = kept_modules(kept_part(existing, covered_modules(parts))[1]) if existing else 0
    except AssembleError as e:
        print(f"assemble: {e}")
        return 1

    if not args.draft_only:
        missing = unverified(docs, repo_root, review_records(review))
        if missing:
            print("\n".join(missing))
            return 1

    (docs / MANIFEST_NAME).write_text(yaml.safe_dump(manifest, sort_keys=False, allow_unicode=True), encoding="utf-8")
    removed = 0
    if not args.keep_parts:
        for path, _ in parts:
            path.unlink()
            removed += 1
        (docs / HEADER_NAME).unlink(missing_ok=True)
    kept_note = f", kept {kept} module(s) of the existing manifest" if existing else ""
    print(f"assembled {MANIFEST_NAME} from {len(parts)} part(s){kept_note}; removed {removed} part(s)")
    return 0


if __name__ == "__main__":
    sys.exit(main())
