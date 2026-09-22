#!/usr/bin/env python3
"""Publish gate for `ai-docs/`: no draft leaves for Confluence.

Runs in the CI `publish` job, before the sync script pushes the pages. Every `*.md` under the directory
(except `ast/`, which another tool produces) must carry a front matter whose `confidence` is
`reviewed` or `verified`. A `draft`, a missing or invalid value, or a document without front
matter blocks the publish. The one exception: a page whose `generated-by` starts with `tool:`
(the outputs of `cross-repo-map`) is computed, not written, so it has no verification ladder and
is publishable without `confidence`; when it carries one anyway, that value is honoured.

Usage:
    publish_gate.py <ai-docs dir>          (`-h` for help)

Exit 0 = `publish gate passed: N document(s)`. 1 = at least one document is listed as
`draft cannot be published: <path> (confidence: draft)`. 2 = a usage error, the directory does not
exist, or it holds no documents. Stdlib only; reuses the front-matter parser of validate.py.
"""

from __future__ import annotations

import argparse
import sys
from pathlib import Path

sys.path.insert(0, str(Path(__file__).parent))
from validate import Findings, parse_front_matter  # noqa: E402

PUBLISHABLE = {"reviewed", "verified"}
SKIPPED_DIR = "ast"
# `generated-by: tool:<name>@<version>` — a computed page (cross-repo-map) needs no `confidence`.
TOOL_PREFIX = "tool:"


def parse_args(argv: list[str] | None) -> argparse.Namespace:
    parser = argparse.ArgumentParser(description="Refuse to publish ai-docs while a document is still a draft.")
    parser.add_argument("docs", help="the ai-docs directory")
    return parser.parse_args(argv)  # a usage error exits 2, `-h` exits 0


def main(argv: list[str] | None = None) -> int:
    docs = Path(parse_args(argv).docs)
    if not docs.is_dir():
        print(f"ERROR: `{docs}` is not a directory", file=sys.stderr)
        return 2
    files = sorted(p for p in docs.rglob("*.md") if SKIPPED_DIR not in p.relative_to(docs).parts)
    if not files:
        print(f"ERROR: no .md files in `{docs}` (outside {SKIPPED_DIR}/)", file=sys.stderr)
        return 2

    blocked = []
    for path in files:
        text = path.read_text(encoding="utf-8", errors="replace").lstrip("\N{BYTE ORDER MARK}").replace("\r\n", "\n")
        front_matter, _, _ = parse_front_matter(text, path, Findings())  # validate.py reports defects
        shown = path.relative_to(docs).as_posix()
        if front_matter is None:
            blocked.append(f"cannot be published: {shown} (no front matter)")
            continue
        confidence = front_matter.get("confidence")
        if confidence is None and str(front_matter.get("generated-by") or "").startswith(TOOL_PREFIX):
            continue
        if confidence not in PUBLISHABLE:
            blocked.append(f"draft cannot be published: {shown} (confidence: {confidence or 'missing'})")
    if blocked:
        print("\n".join(blocked))
        return 1
    print(f"publish gate passed: {len(files)} document(s)")
    return 0


if __name__ == "__main__":
    sys.exit(main())
