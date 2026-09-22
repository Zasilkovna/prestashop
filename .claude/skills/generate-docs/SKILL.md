---
name: generate-docs
description: Use when a repository has no AI-facing documentation, agents orient only by grepping, documentation has drifted from code, or the user asks for "ai-docs", "document the modules" or a "repo manifest"; also CI/batch runs. Not for `docs/`, README or the business "why".
---

# Generate AI-facing documentation into `ai-docs/`

One repository, documented for AI tools and retrieval, not for people; `docs/` and README stay
untouched. Two layers: `manifest.yaml` (machine inventory, cross-repo backbone) and prose adding
why and when — **the manifest says what, the prose says why; drift is an error.** `ai-docs/ast/`
is another tool's output, never edited. Output:
`ai-docs/{index,overview,architecture,dependencies}.md`, `reference/<module-id>.md`,
`manifest.yaml`; verification records: `ai-docs-review/<doc-id>.md`.

## Installation and parameters

```
rsync -a --exclude tests/ --exclude '.*' --exclude '__pycache__/' handbook/documentation/skills/generate-docs/ <repo>/.claude/skills/generate-docs/
```

Permissions: [`settings.example.json`](../../claude/settings.example.json). Prerequisite PyYAML
(`pip install pyyaml`): `assemble.py` fails without it, `validate.py` skips the manifest checks
with a warning unless `--require-yaml`. Team mode: `../../procedures/01-initialize.md`.

- `--scope <module-id>[,…]` — only the module's reference page and fragment; ends after step 3
  (4 without `--draft-only`), never assembles.
- `--draft-only` — skip step 4, for the four shared pages too; documents stay `confidence: draft`,
  unpublishable.
- `--pages` — the four shared pages from `manifest.yaml` + `reference/*.md`, under-threshold
  modules included; no delta check.
- `--language <code>` — prose language; default English.

## Contract

1. **Code is the source of truth.** Every claim about the code is checkable against a file in the repo.
2. **Never invent** APIs, endpoints, configuration keys, feature flags or dependencies the code lacks.
3. **Names are copied, not paraphrased** — event, endpoint, entity, channel, key, exactly as in source
   (greppable). A counterparty that named the thing first wins (grep other documented repos'
   manifests): a cross-repo edge exists only where names match.
4. **Write uncertainty down, do not fill it in.** Not determinable from code → `unknown` plus an
   `unknowns:` entry, never a TODO for a human. Inferred indirectly → `unconfirmed:`. Business "why"
   → `> ⚠ add business context (elicitation)`, the only marker aimed at a human; it never blocks
   validity.
5. **Key claims carry an anchor** `[VERIFY: …]` checked by the validator.
6. **Non-interactive.** No questions (CI/batch): record the assumption under
   `## <repo-id>: generation assumptions` in `index.md` and continue. No side effects: no `/init`,
   context clearing or commits. Team-mode exception: after a `--scope` run the generator commits
   exactly its two files on the shared branch (`../../procedures/01-initialize.md`).
7. **Write only into `ai-docs/`** (plus `ai-docs-review/`); never into `docs/`, `CLAUDE.md`, source
   or a foreign repo.
8. **Terminology:** "module" (= one project/package/service in the repo), never "component" or "service".
9. **Output language:** English; `--language` overrides.
10. **Keys, not values.** Configuration keys, endpoints and names are copied; connection strings, hosts,
    IPs, credentials, contents of `.env`/`appsettings*`/fixtures and personal data never — anchor where
    the value lives. The manifest's `repo:` URL is the one exception.

## Anchors

`[VERIFY: path/File.ext#Symbol]`, checked for file and symbol; `[VERIFY: path:123]` only where
the evidence has no name. The symbol never contains `<`, `>`, `]` or `|`: generic arguments in any
syntax (C# `AddHttpClient<AuthClient>`, PHPDoc `Collection<int, Comment>`) are dropped, and appear
in prose only inside a code span (Confluence reads them as HTML). Anchor what the claim evidences
(`Protect`, not `CreateProtector`); two claims, two anchors. Never anchor into
`ai-docs/ast/map.json` (flat JSON, repeated keys): cite `ai-docs/ast/map.md` with `path:line`, or
state the number without an anchor.

## Identifiers and thresholds

**Repo id** (`service`): the repository name from the origin URL — last path segment, lowercase,
without `.git`; without a remote, the directory name. A team may fix a canonical id instead, the
URL name becoming an alias. **Module id:** lowercase project or package name, dots, slashes and
spaces → hyphens (`Todo.Api` → `todo-api`, `acme/shop` → `acme-shop`); a Composer package alone in
its repository keeps the package part only (`symfony/symfony-demo` → `symfony-demo`), a monorepo
keeps `vendor-package`. Test projects and directories (`*.Tests`, `tests/`) are never modules.

`ai-docs/ast/map.json` lists types and their public methods only — no properties, fields or
attributes; data-model fields and route attributes are read from the source. Own `reference/` file
on any of: **> 200 lines of logic** (the `lines` field of `map.json` summed per module, excluding
migrations, generated code, `bin/`, `obj/`, `vendor/`; no field, no number), a non-trivial
algorithm, a state machine, **5+ public members** (the public methods `map.json` lists; the
document lists exactly the members it counts). Below → a subsection in `overview.md`. Above 3 000
lines, split by namespace or directory into several `reference/` pages (`orders-api`,
`orders-domain`).

## Procedure

0. **Preflight.** `git status --porcelain` non-empty → STOP and say so; never stash, reset or
   commit. Any branch; record branch and commit under generation assumptions; ahead of origin is
   fine, behind → warn and continue, never pull or fetch. No git → `source_commit: unknown`. Done:
   clean tree, `source_commit` settled.
1. **Delta check** (refresh only; skipped by `--pages`). No `ai-docs/manifest.yaml` yet → first
   run, go to step 2. Else `git log --oneline <source_commit>..HEAD` excluding tests and docs;
   empty → report "current at `<sha>`" and STOP. The diff hints where to look first; it never
   bounds what changed. Done: current, or the changed paths listed.
2. **Structure scan.** Boundaries: `*.csproj`, `package.json`, `pyproject.toml`, `go.mod`,
   `composer.json` (`autoload.psr-4` prefixes map to directories), tests excluded.
   Files, lines and public methods come from `ai-docs/ast/map.json`; a map older than HEAD is used
   when `git diff --stat <map commit>..HEAD` touches no source files (recorded under generation
   assumptions), otherwise regenerated first (`ast-map`, outside the skill); no map → no numbers,
   decide by the other criteria, record it. Apply the thresholds; `--scope` keeps only the listed
   modules. Done: every module has id, counts, decision.
3. **Per-module analysis.** One `templates/module.md` document with anchors plus the fragment
   `manifest.part.<module-id>.yaml`: `service`, `source_commit`, the module's entries, `unknowns`,
   `aliases` (any second name the code uses for the module or its counterparts, e.g. in service
   discovery) and `assumptions:` (strings: branch, commit, anything assumed). A full run uses
   parallel subagents, each reading ONLY its module and each applying the prose-form rule below
   (STE skill when available). A foreign module is read solely to evidence
   an edge (`calls →`, `called from →`, `references →`, `hosts →`), never described; no evidence →
   no edge. Every registered route is an `exposes` entry (no threshold); the prose table groups
   routes by route group or controller (max 10 rows), every route name greppable in its cell. A
   route handled by a framework class (identity, health, template controllers) → `unconfirmed`,
   evidence = the registration, plus an `unknowns` item. In-process events (Symfony
   EventDispatcher, MediatR, .NET events) have no channel: never in `publishes`/`consumes`;
   `architecture.md` describes them, one sentence per event family. Done: each module in scope has
   document (or `overview.md` subsection) and fragment.
4. **Verification** (skipped with `--draft-only`). A subagent or person who did not write the
   document runs `templates/verify.md` in a fresh session and only classifies, into
   `ai-docs-review/<doc-id>.md` (never published). The generator fixes every INCORRECT, SENSITIVE
   and INSTRUCTION finding and sets `confidence` by the promotion rule in `verify.md`. Done: each
   document has a record and a `confidence`.
5. **Assemble** (never in a `--scope` run). `python3 .claude/skills/generate-docs/assemble.py ai-docs
   --service <id> --description "<one sentence>"` merges the fragments (their `assumptions` into the
   top-level `assumptions:`) into `manifest.yaml` and deletes them; it refuses without verification
   records unless `--draft-only`. When `manifest.yaml` already exists, it keeps the entries of every
   module that has no fragment in this run, so one `--scope` run later refreshes one module and
   keeps the rest. Promote the reference pages first: `index.md` copies their
   `confidence` and verification summary into its status table, so a later promotion means
   re-running `--pages` or editing it. Then `/generate-docs --pages` writes `index.md`,
   `overview.md`, `architecture.md` and `dependencies.md` from the manifest, including
   under-threshold modules: module id by the convention, `modules:` entry with `doc: overview.md`,
   short `overview.md` subsection. In `manifest.yaml` a `--pages` run writes those `modules:` entries
   and `unknowns:` — nothing else. `calls`, `exposes`, `publishes`, `consumes`, `owns_data`,
   `reads_external_data`, `frontends` and `aliases` describe code, so they are written by the
   `--scope` run that reads that module; a gap you see in them belongs in the run report and in
   `unknowns:`, and it is closed by a `--scope` run, never by editing the manifest here. Then step 4
   for the four pages (in team mode by someone who did not write them; skipped with `--draft-only`),
   then step 6. Done: no fragment left, four pages with records (or `--draft-only`).
6. **Validate.** `python3 .claude/skills/generate-docs/validate.py ai-docs --strict --require-yaml`;
   one module before assembly: the same with `--partial reference/<module-id>.md --manifest-part
   ai-docs/manifest.part.<module-id>.yaml` (resolved from the current directory). Drift is checked
   both ways: manifest names and aliases in the prose, dependency-line ids in the manifest. Done at
   exit 0.
7. **Run report**, spoken, not written: manifest counts per section, modules above/below the
   threshold, `confidence` per document, every `unknowns:` entry, other repos met without
   `ai-docs/`, and the STE sentence of the prose-form rule. Then the person commits `ai-docs/` and
   `ai-docs-review/`; the skill itself never commits. Done: every `unknowns:` entry reported and the
   STE sentence said.

Reader entry point (`CLAUDE.md`), separate step:
[`CLAUDE.md-section.md`](../../claude/CLAUDE.md-section.md).

## Front matter, size and templates

Front matter of every `.md`: `title`, `repo` (= manifest `service`), `module`,
`generated-by: skill:generate-docs@0.3.5`, `source-commit` (= manifest `source_commit`),
`last-generated`, `covers`, `confidence` (`draft`/`reviewed`/`verified`), `tags` (`ai-generated`,
`repo-<repo-id>`, `module-<module-id>`, one `type-*`). Keep `last-generated` when the body did not
change; drift is computed from git. Target 250–600 tokens per H2 section; the validator warns
below 120 on a reference page, below 60 on the four shared pages, and above 1 000; never pad to
reach a number. `exposes[].kind` adds `html` for server-rendered pages. Read
`templates/writing-sections.md` first, then fill the page templates; `manifest.yaml`'s header
comments are the contract.

**Prose form.** Before you write any prose, invoke the skill `esterka:writing-technical-docs-in-ste`
(plugin `esterka@packeta-dev` from Packeta's Claude Code marketplace `packeta-dev` <!-- packeta -->)
and hold every sentence to its contract (ASD-STE100 Simplified Technical English). Attempt the call:
an error from that call is the only evidence that the skill is missing, and nothing else — not your
memory, not a listing you did not read — decides it. The run report then carries exactly one of two
sentences, `STE skill invoked` or `STE skill not installed, prose follows templates/writing-sections.md
alone`; a run that writes neither is not finished. The skill governs the documents written to disk,
never the spoken run report, and it is never a prerequisite: without it the templates alone still
produce a valid document. Under both rule sets, code, identifiers, anchors and manifest values are
quoted text and never change.
