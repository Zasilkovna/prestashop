#!/usr/bin/env python3
"""Covers-aware staleness check for `ai-docs/`.

Every document in `ai-docs/` records the commit it describes (`source-commit`) and the paths it
describes (`covers`). A document is stale when one of its covered paths changed after that commit.
The check is deterministic — git plus the front matter, no model — and the same script serves
three callers:

1. CI job: `stale_check.py --repo . --format text --fail-on-stale`
   Compares commits (`<source-commit>..HEAD`). One line per stale document on stdout, a closing
   hint, and exit 1 so the job fails until the documents are refreshed. When nothing is stale it
   prints `fresh: ai-docs @ <HEAD>` (exit 0), so a person sees that the check ran.

2. Stop hook in `.claude/settings.json`: `stale_check.py --repo . --format hook [--nudge]`
   Compares the WORKING TREE with `source-commit` (uncommitted and untracked files count), so the
   agent hears about stale documents before it commits. Prints one JSON object with
   `systemMessage` (shown to the user); prints nothing when nothing is stale. With `--nudge` the
   object also carries `{"decision": "block", "reason": ...}`, which makes the agent continue with
   the reason as its instruction. A nudge is suppressed when the hook input has
   `stop_hook_active: true`, so it cannot loop. Exit is always 0 — exit 2 would block the agent,
   and a broken check must never do that.

3. Pre-check before a headless refresh: `stale_check.py --repo . --format text`
   A stdout that starts with `fresh:` means there is nothing to refresh.

Rules: documents under `ast/` (produced by another tool, skipped by validate.py and publish_gate.py
too) and documents tagged `type-index` are skipped, and so are the entries `.` and `` in `covers`.
A path counts as changed for a document when it equals a covered path or lies below a covered
directory. A `source-commit` that is not a hex commit id is reported on stderr and skipped — the
value is never passed to git. A commit id git does not know (shallow clone, rewritten history) is
reported on stderr as `not in history` and does not count as stale, so stdout carries stale
documents — or, in text mode, the fresh line — and nothing else.

Failure modes differ by caller. Hook mode fails open: a usage error, a `--repo` outside a git work
tree or any unexpected error is reported on stderr and exits 0, because exit 2 would block the
agent. Text mode fails closed: a usage error exits 2 (argparse), the other two conditions print to
stderr and exit 2, so a CI job or pre-check sees a broken check instead of a silent pass. The mode
is read from the raw argv (`--format hook` / `--format=hook`) before argparse, so even a usage
error is handled in the right mode. A missing docs directory exits 0 without output in both modes.

Stdlib only; reuses the front-matter parser of validate.py.
"""

from __future__ import annotations

import argparse
import json
import re
import subprocess
import sys
from dataclasses import dataclass, field
from pathlib import Path

sys.path.insert(0, str(Path(__file__).parent))
from validate import Findings, parse_front_matter  # noqa: E402

GIT_TIMEOUT = 20  # seconds, per git call
RE_COMMIT_ID = re.compile(r"[0-9a-f]{7,40}")
INDEX_TAG = "type-index"
MAX_LISTED_PATHS = 5
SKIPPED_DIR = "ast"
CLOSING_LINE = "Refresh with the generate-docs skill before opening the merge request."


@dataclass
class Report:
    stale: list[tuple[str, str, list[str]]] = field(default_factory=list)  # (document, sha, hit paths)
    problems: list[str] = field(default_factory=list)  # documents that could not be judged


# --- git ------------------------------------------------------------------------------------------


def git(repo: Path, *args: str) -> subprocess.CompletedProcess:
    return subprocess.run(["git", *args], cwd=repo, capture_output=True, text=True, timeout=GIT_TIMEOUT)


def inside_work_tree(repo: Path) -> bool:
    r = git(repo, "rev-parse", "--is-inside-work-tree")
    return r.returncode == 0 and r.stdout.strip() == "true"


def head_commit(repo: Path) -> str:
    """The id of HEAD, abbreviated like the ids in the stale lines; `HEAD` when the repository has no commit yet."""
    r = git(repo, "rev-parse", "--verify", "--quiet", "HEAD")
    return r.stdout.strip()[:12] if r.returncode == 0 and r.stdout.strip() else "HEAD"


def resolve_commit(repo: Path, sha: str) -> str | None:
    """Full id when git knows `sha` as a commit, else None. `sha` has already been checked to be hex."""
    r = git(repo, "rev-parse", "--verify", "--quiet", "--end-of-options", f"{sha}^{{commit}}")
    return r.stdout.strip() if r.returncode == 0 else None


def changed_paths(repo: Path, since: str, working_tree: bool) -> list[str]:
    """Paths (relative to `repo`) that differ from commit `since` — in HEAD, or in the working tree."""
    target = since if working_tree else f"{since}..HEAD"
    r = git(repo, "diff", "--name-only", "-z", "--relative", "--end-of-options", target)
    if r.returncode != 0:
        raise RuntimeError(f"git diff failed: {r.stderr.strip()}")
    paths = set(r.stdout.split("\0"))
    if working_tree:
        u = git(repo, "ls-files", "--others", "--exclude-standard", "-z")
        if u.returncode != 0:
            raise RuntimeError(f"git ls-files failed: {u.stderr.strip()}")
        paths.update(u.stdout.split("\0"))
    paths.discard("")
    return sorted(paths)


# --- documents ------------------------------------------------------------------------------------


def load_documents(docs_root: Path, repo: Path) -> list[tuple[str, dict]]:
    """(path relative to the repo, front matter) for every document that has a front matter."""
    documents = []
    for path in sorted(docs_root.rglob("*.md")):
        if SKIPPED_DIR in path.relative_to(docs_root).parts:  # the predicate validate.py uses
            continue
        text = path.read_text(encoding="utf-8", errors="replace")
        text = text.lstrip("\ufeff").replace("\r\n", "\n")
        front_matter, _, _ = parse_front_matter(text, path, Findings())  # validate.py reports defects
        if front_matter:
            documents.append((path.relative_to(repo).as_posix(), front_matter))
    return documents


def as_list(value) -> list:
    if value is None:
        return []
    return value if isinstance(value, list) else [value]


def normalize_cover(entry) -> str | None:
    """`covers` entry as a clean relative path; None for `.`, empty and non-string entries."""
    if not isinstance(entry, str):
        return None
    path = entry.strip().replace("\\", "/")
    while path.startswith("./"):
        path = path[2:]
    path = path.strip("/")
    return None if path in ("", ".") else path


def hits(covers: list[str], changed: list[str]) -> list[str]:
    return [p for p in changed if any(p == c or p.startswith(c + "/") for c in covers)]


def check(repo: Path, docs_root: Path, working_tree: bool) -> Report:
    report = Report()
    changed_since: dict[str, list[str] | None] = {}  # sha -> changed paths (None: unknown commit)
    for document, front_matter in load_documents(docs_root, repo):
        if INDEX_TAG in as_list(front_matter.get("tags")):
            continue
        covers = [c for c in map(normalize_cover, as_list(front_matter.get("covers"))) if c]
        if not covers:
            continue
        sha = str(front_matter.get("source-commit") or "").strip()
        if not RE_COMMIT_ID.fullmatch(sha):
            report.problems.append(f"{document}: source-commit `{sha[:40]}` is not a commit id — skipped")
            continue
        if sha not in changed_since:
            full = resolve_commit(repo, sha)
            changed_since[sha] = None if full is None else changed_paths(repo, full, working_tree)
        changed = changed_since[sha]
        if changed is None:
            report.problems.append(f"{document}: source-commit {sha[:12]} not in history — cannot judge staleness")
            continue
        hit = hits(covers, changed)
        if hit:
            report.stale.append((document, sha, hit))
    return report


# --- output ---------------------------------------------------------------------------------------


def print_text(report: Report, docs: str, head: str) -> None:
    if not report.stale:
        print(f"fresh: {docs} @ {head}")  # the person must see that the check ran; the hook stays silent
        return
    for document, sha, paths in report.stale:
        listed = ", ".join(paths[:MAX_LISTED_PATHS]) + (" …" if len(paths) > MAX_LISTED_PATHS else "")
        print(f"{document}: covered paths changed since {sha[:12]}: {listed}")
    print(CLOSING_LINE)


def hook_message(report: Report) -> str:
    documents = ", ".join(document for document, _, _ in report.stale)
    return f"ai-docs stale: {len(report.stale)} document(s) — {documents} — run the generate-docs skill before opening the MR"


def read_hook_input() -> dict:
    """The JSON Claude Code passes to a Stop hook on stdin; `{}` when absent or unreadable."""
    try:
        if sys.stdin is None or sys.stdin.isatty():
            return {}
        data = json.loads(sys.stdin.read() or "{}")
    except (OSError, ValueError):
        return {}
    return data if isinstance(data, dict) else {}


def print_hook(report: Report, nudge: bool) -> None:
    if not report.stale:
        return
    if nudge and read_hook_input().get("stop_hook_active"):
        return  # the agent is already continuing because of a Stop hook — nudging again would loop
    message = hook_message(report)
    payload = {"systemMessage": message}
    if nudge:
        payload.update(decision="block", reason=message)
    print(json.dumps(payload))


# --- entry point ----------------------------------------------------------------------------------


def wants_hook_format(argv: list[str]) -> bool:
    """Whether the raw argv selects `--format hook`. Read before argparse so that even a usage error
    is handled in the right mode; the last `--format` wins, as it does in argparse."""
    fmt = None
    for i, arg in enumerate(argv):
        if arg == "--format" and i + 1 < len(argv):
            fmt = argv[i + 1]
        elif arg.startswith("--format="):
            fmt = arg.partition("=")[2]
    return fmt == "hook"


def parse_args(argv: list[str]) -> argparse.Namespace:
    parser = argparse.ArgumentParser(description="Report ai-docs documents whose covered paths changed since their source-commit.",
                                     allow_abbrev=False)  # so `wants_hook_format` and argparse read the same `--format`
    parser.add_argument("--repo", required=True, help="repository root, the directory that contains the docs directory")
    parser.add_argument("--docs", default="ai-docs", help="docs directory relative to --repo (default: ai-docs)")
    parser.add_argument("--format", choices=("text", "hook"), default="text",
                        help="text: lines for CI / pre-check, commits compared; hook: JSON for a Stop hook, working tree compared")
    parser.add_argument("--nudge", action="store_true", help="hook mode: also return decision=block so the agent refreshes the documents itself")
    parser.add_argument("--fail-on-stale", action="store_true", help="text mode: exit 1 when a document is stale (CI)")
    return parser.parse_args(argv)


def main(argv: list[str] | None = None) -> int:
    argv = sys.argv[1:] if argv is None else list(argv)
    # Hook mode fails open (exit 2 would block the agent, and a broken check must never do that);
    # text mode fails closed (CI and the pre-check must not mistake a broken check for a pass).
    on_failure = 0 if wants_hook_format(argv) else 2
    try:
        try:
            args = parse_args(argv)
        except SystemExit as exc:
            return on_failure if exc.code else 0  # argparse exits 0 for --help, 2 on a usage error
        if hasattr(sys.stdout, "reconfigure"):
            sys.stdout.reconfigure(errors="replace")  # `…` and `—` must not crash an ASCII console

        repo = Path(args.repo).resolve()
        docs_root = repo / args.docs
        if not docs_root.is_dir():
            return 0
        if not inside_work_tree(repo):
            print(f"stale_check: {repo} is not inside a git work tree — nothing checked", file=sys.stderr)
            return on_failure

        hook = args.format == "hook"
        report = check(repo, docs_root, working_tree=hook)
        for line in report.problems:
            print(line, file=sys.stderr)
        if hook:
            print_hook(report, args.nudge)
            return 0
        print_text(report, args.docs, head_commit(repo))
        return 1 if args.fail_on_stale and report.stale else 0
    except Exception as exc:  # noqa: BLE001 — open in hook mode, closed in text mode; see the module docstring
        print(f"stale_check: {type(exc).__name__}: {exc}", file=sys.stderr)
        return on_failure


if __name__ == "__main__":
    sys.exit(main())
