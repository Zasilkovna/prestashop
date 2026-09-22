# Template: `index.md`

The signpost. Assembled **after** the modules. Subject of all headings = `<repo-id>`.
The sections `generation assumptions` and `what the documentation does not know` are mandatory even
when empty (then they hold one sentence saying that nothing of the kind arose). The section
`what the documentation does not know` is the prose counterpart of `unknowns:` in `manifest.yaml` —
both must say the same.

```markdown
---
title: "<repo-id> — documentation index"
repo: <repo-id>
module: null
generated-by: skill:generate-docs@0.3.5
source-commit: <manifest source_commit>
last-generated: <ISO date>
covers: [.]
confidence: draft
tags: [ai-generated, repo-<repo-id>, type-index]
---

Repo: <repo-id> · Module: — · Type: index · Status: current

## <repo-id>: documentation map

<repo-id> is <one sentence on what the repo is>. The documentation of <repo-id> is split into these pages:

| Page | Content | Open it when |
|---|---|---|
| [manifest.yaml](manifest.yaml) | <machine-readable inventory: what the repo publishes, consumes, exposes, calls and owns as data> | <you grep for an event, entity or interface name — **start here**> |
| [overview.md](overview.md) | <what the repo does, module list, small modules> | <you need orientation / a small module> |
| [architecture.md](architecture.md) | <how the modules hold together, runtime flows> | <you trace where a request flows> |
| [reference/<module-id>.md](reference/<module-id>.md) | <module: purpose, interface, dependencies> | <you work in this module> |
| [dependencies.md](dependencies.md) | <external dependencies, integrations, aliases> | <you look for what the repo needs from outside> |

## <repo-id>: modules

<repo-id> contains <N> modules. Modules of <repo-id> and their documentation:

| Module | Path | Document |
|---|---|---|
| <module-id> | `<path>` | [reference/<module-id>.md](reference/<module-id>.md) |
| <small-module-id> | `<path>` | [overview.md](overview.md#<heading-anchor>) — below the threshold (`doc: overview.md` in the manifest) |

## <repo-id>: generation assumptions

<repo-id> was documented under these assumptions (uncertainties the skill resolved on its own so that
it could finish non-interactively):

- <assumption — what was not certain from the code and how the skill decided it>

<If none: "The generation of <repo-id> carried no uncertain assumptions — every statement has support in the code.">

## <repo-id>: generation status

<repo-id> — documentation generated from commit `<sha>`.

| Document | confidence | Verification |
|---|---|---|
| <file.md> | <draft\|reviewed\|verified> | <X/Y CONFIRMED, Z INCORRECT — or "not yet verified"> |

<A sentence on what the lowest confidence reached means and what would raise it. The table copies
each page's `confidence` at the time `--pages` ran: promote the reference pages first, or re-run
`--pages` (or edit this table) after a later promotion.>

## <repo-id>: what the documentation does not know

<repo-id> has these evidenced blind spots (carried machine-readably by `manifest.yaml`, section `unknowns`).
They are not tasks for the reader — they are findings to report when a query runs into them.

| What is unknown | What was searched | What would resolve it |
|---|---|---|
| <question> | <files, configuration, other repos> | <repo / contract / infra config / a person> |

<If none: "The generation of <repo-id> did not run into a fact that could not be determined from the code.">
```
