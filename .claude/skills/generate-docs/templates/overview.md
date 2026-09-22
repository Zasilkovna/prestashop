# Template: `overview.md`

What the repo does + the module list + **the home of small modules** that did not pass the threshold
for `reference/`. Subject of the main headings = `<repo-id>`; for merged modules = the module id
(a section must carry its own subject for retrieval).

```markdown
---
title: "<repo-id> — overview"
repo: <repo-id>
module: null
generated-by: skill:generate-docs@0.3.5
source-commit: <manifest source_commit>
last-generated: <ISO date>
covers: [<paths of the modules under the threshold documented here>, <solution-level files that belong to no module>]
confidence: draft
tags: [ai-generated, repo-<repo-id>, type-overview]
---

Repo: <repo-id> · Module: — · Type: overview · Status: current

## <repo-id>: what the repo does

<repo-id> is <kind of application> — <2–4 sentences on what the repo does, evidenced from the code:
entry points, main use case> [VERIFY: <path/File.ext#Symbol>].

> ⚠ add business context (elicitation)

## <repo-id>: modules

<repo-id> consists of <N> modules. <M> of them passed the threshold for a standalone `reference/` document.

| Module | Path | Lines of logic | Document |
|---|---|---|---|
| <module-id> | `<path>` | <N> | [reference/<module-id>.md](reference/<module-id>.md) |
| <small-module-id> | `<path>` | <N> | below — under the threshold |

## <repo-id>: startup and configuration

<repo-id> starts <how — from the code, not from the README> [VERIFY: <path/File.ext#Symbol>].
Configuration keys of <repo-id> that affect process start:

| Key | Read in | Where the value lives | Anchor |
|---|---|---|---|
| <ConnectionStrings:Orders> | `<path/File.ext>` | [VERIFY: src/appsettings.json#ConnectionStrings] | [VERIFY: <path/File.ext#Symbol>] |
| <Logging:LogLevel:Default> | `<path/File.ext>` | [VERIFY: src/appsettings.json#Logging] | [VERIFY: <path/File.ext#Symbol>] |

<Only keys that affect process start (ports, listeners, connection to the own store, logging,
feature flags). Keys that point at external systems belong to `dependencies.md`, section
"environment configuration"; a key appears in exactly one of the two tables. Keys, not values: the
third column anchors the config file by key, or names the secret store or the environment. Never
copy the value.>

## <small-module-id>: summary

<small-module-id> <does X — subject by full name> [VERIFY: <path/File.ext#Symbol>].
<small-module-id> did not pass the threshold for a standalone document (<reason: N lines of logic, M public members>);
its `modules:` entry in the manifest points here (`doc: overview.md`).

Public interface of <small-module-id>:

| Member | Behaviour | Anchor |
|---|---|---|
| <name> | <what it does> | [VERIFY: <path/File.ext#Symbol>] |

calls → <canonical-id> (<sync|async>, <protocol>)

<Repeat this section for every module under the threshold — a `--pages` run writes them from the
module list, with a module id by the convention in SKILL.md, never an ad-hoc name. Watch the
ceiling of 6 H2 sections per page — when the small modules do not fit, merge related ones into one
section headed by their common subject.>
```

## Keys, not values

The configuration table names the key and says where the value lives — an anchor into the config
file by key (`[VERIFY: src/appsettings.json#ConnectionStrings]`), the secret store, or the
environment. Never copy the value. The validator rejects connection strings, hosts, credentials,
tokens and internal addresses.
