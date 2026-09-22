# Sources of adopted patterns

The `generate-docs` skill builds on patterns from existing public skills (Jakub Esterka's brief:
"there is already a pile of docs skills — pick and merge").

**What was adopted:** *patterns* — the procedure, the section structure, the classification scheme,
the anchor format. **What was not:** no code and no text was copied verbatim. Every file of this skill
(`SKILL.md`, `templates/*`, the Python scripts) is written from scratch against Packeta's brief, in
English throughout (decision of 2 September 2026).

All public sources are **MIT** — licence verified via the GitHub API license endpoint
(`gh api repos/<repo>/license --jq '.license.spdx_id'`) on the date given in the table. Note:
`gh repo view --json licenseInfo` and `gh search repos` return no licence for these repos even though
a LICENSE file demonstrably exists — use the license endpoint.

## Adopted patterns

| Pattern in this skill | Source | Licence |
|---|---|---|
| Pipeline: scan → per-module analysis → verification in a separate context → assembly | [workingdanny911/cai](https://github.com/workingdanny911/cai) | SPDX: MIT, verified 2026-07-17 via the GitHub API license endpoint |
| Thresholds for a standalone module document (> 200 lines / algorithm / state machine / 5+ public members) | [workingdanny911/cai](https://github.com/workingdanny911/cai) | SPDX: MIT, verified 2026-07-17 via the GitHub API license endpoint |
| Anti-hallucination as a positive contract (code = source of truth, a gap is marked) | [workingdanny911/cai](https://github.com/workingdanny911/cai) + Dosu (an article, not code) | SPDX: MIT, verified 2026-07-17 via the GitHub API license endpoint / concept without code |
| Drift computed from git (`covers` + `source-commit`), not from a date | [ryanwaits/drift](https://github.com/ryanwaits/drift) | SPDX: MIT, verified 2026-07-17 via the GitHub API license endpoint |
| `[VERIFY: path:line]` anchors next to checkable claims | [Ycsyyds/codebase-analysis-skill](https://github.com/Ycsyyds/codebase-analysis-skill) | SPDX: MIT, verified 2026-07-17 via the GitHub API license endpoint |
| Skill `description` = WHEN to use, not a workflow summary (otherwise the agent skips the body) | [obra/superpowers](https://github.com/obra/superpowers) | SPDX: MIT, verified 2026-07-17 via the GitHub API license endpoint |
| Templates as positive recipes with slots, not lists of prohibitions | [obra/superpowers](https://github.com/obra/superpowers) | SPDX: MIT, verified 2026-07-17 via the GitHub API license endpoint |
| Deterministic output validator runnable in CI | [softspark/ai-toolkit](https://github.com/softspark/ai-toolkit) | SPDX: MIT, verified 2026-07-17 via the GitHub API license endpoint |
| Skill-writing checklist (RED/GREEN/REFACTOR, CSO, leanness) | cf-powers:writing-skills — a derivative of [obra/superpowers](https://github.com/obra/superpowers), © 2025 Jesse Vincent | SPDX: MIT, verified 2026-07-17 (local plugin `cf-powers@1.4.0`, LICENSE file) |
| Structure of `SKILL.md` 0.3.0 (`description` says when; steps end with a completion criterion; reference material behind pointers; no restating of `--help`) | [mattpocock/skills](https://github.com/mattpocock/skills), skill `writing-for-agents` | SPDX: MIT, verified 2026-09-06 via the GitHub API license endpoint |
| Business-context elicitation procedure in the handbook (structured questioning, questionnaire output) | [mattpocock/skills](https://github.com/mattpocock/skills), skills `grilling` and `to-questionnaire` | SPDX: MIT, verified 2026-09-06 via the GitHub API license endpoint |

## Patterns adopted from the Orbis KB (v0.2, 18 Aug 2026)

Source: **`orbis-docs`** — Packeta's internal repository (author Vít Bejček), read-only clone at
`4bae693` (10 Aug 2026).

**Legal status:** it is the **client's** repository, not a public project — it has no licence and needs
none. We adopt **patterns** (what to do in the documentation), **not code or text**: `manifest.yaml`,
`SKILL.md` and the cross-repo map tool are written from scratch and the schema differs (`evidence:`,
`unknowns:`, `modules:`, multi-repo, language-agnostic). Patterns from meetings and from the client's
repository belong to the client — the point is not permission to use them but not publishing them
outside without the client's knowledge.

| Pattern in this skill | Where in `orbis-docs` |
|---|---|
| Machine-readable manifest next to prose; prose 1:1 with the manifest | `templates/service/manifest.yaml`, `.claude/skills/document-service/SKILL.md` |
| Freshness preflight of the source repo (clean tree, no pull or fetch, never touch a foreign repo) | `document-service/SKILL.md`, step 2 |
| Refresh delta check against `source_commit` with "nothing changed → stop" | `document-service/SKILL.md`, step 3 |
| `unknown` with an evidence trail instead of a TODO for a human; "inferred → unconfirmed" | `document-service/SKILL.md`, section Rules; `docs/design.md`, invariant 4 |
| Names copied from source; the counterparty's existing name takes precedence | `document-service/SKILL.md`, section Rules |
| Data ownership: projections of foreign data do not belong in `owns_data` | ibid. |
| Run report listing unresolved facts for the next run | `document-service/SKILL.md`, step 7 |
| Entry point for the reading agent (lookup order, "dropping to source = a gap") | `CLAUDE.md` |
| Derived cross-repo views generated by a script + inconsistency reporting | `scripts/build-overview.py`, `.claude/skills/build-overview/SKILL.md` |

## Deliberately unused sources

| Source | Reason |
|---|---|
| borghei/Claude-Skills | Commons Clause — restricts commercial use. No code or text copied. |
| SpillwaveSolutions | No licence stated → no right of use. No code or text copied. |
