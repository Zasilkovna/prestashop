# Template: `dependencies.md`

What the repo needs from outside and who needs it. Plus **aliases** — a map of canonical ids to
abbreviations and old names, so that retrieval finds a module even under a name the repo no longer
uses. Subject of the headings = `<repo-id>`.

**Prose counterpart of `manifest.yaml`.** The manifest carries the same links machine-readably
(`publishes`, `consumes`, `exposes`, `calls`, `reads_external_data`, `aliases`) and the cross-repo
map is computed from it — `dependencies.md` adds "what flows over that link and what happens when
the counterpart is down". **Every manifest entry has a line here and vice versa**; what is in only
one of them is an error. Names are copied from the manifest character by character.

```markdown
---
title: "<repo-id> — dependencies and external links"
repo: <repo-id>
module: null
generated-by: skill:generate-docs@0.3.5
source-commit: <manifest source_commit>
last-generated: <ISO date>
covers: [<dependency manifests: *.csproj, package.json, composer.json, Directory.Packages.props, …>]
confidence: draft
tags: [ai-generated, repo-<repo-id>, type-dependencies]
---

Repo: <repo-id> · Module: — · Type: dependencies · Status: current

## <repo-id>: links outside the repo

<repo-id> communicates with <N> systems outside its own code.

calls → <canonical-id> (<sync|async>, <protocol>)
called from → <canonical-id>
references → <canonical-id>

| Counterpart | Direction | Sync/async | Protocol | Where in code | Anchor |
|---|---|---|---|---|---|
| <system> | <calls / called from / references> | <sync or —> | <HTTP/JSON or —> | `<path>` | [VERIFY: <path/File.ext#Symbol>] |

<One sentence per link: what flows over it. Only what the code evidences — no system that is not
in the code. `references →` is a contract or client package from another repo: a build-time link
with no runtime call of its own.>

## <repo-id>: links between modules

<repo-id> holds together through these internal links:

calls → <module-id-b> (<sync|async>, <protocol>)
references → <module-id-b>
hosts → <module-id-b>

| From module | To module | Link | Sync/async | Protocol | Anchor |
|---|---|---|---|---|---|
| <module-id-a> | <module-id-b> | <calls / references / hosts> | <sync or —> | <HTTP/JSON or —> | [VERIFY: <path/File.ext#Symbol>] |

<`calls →` for a runtime call between modules (an intra-repo HTTP call is also a `calls:` entry in
the manifest with the module id as `service`); `references →` for a project reference
(`<ProjectReference>`, a workspace package) without a runtime call; `hosts →` when <module-id-a>
serves the build of <module-id-b> — a SPA bundle, static files — from its own process.>

## <repo-id>: external packages

<repo-id> depends on these packages <from the manifest file> [VERIFY: <path/File.ext#Symbol>].
<List **only** packages of architectural significance (framework, DB driver, proxy, auth, telemetry);
leave out transitive and purely development ones and say so in a sentence.>

| Package | Version | Role in <repo-id> | Anchor |
|---|---|---|---|
| <name> | <version> | <role — from its use in the code> | [VERIFY: <path/File.ext#Symbol>] |

## <repo-id>: environment configuration

<repo-id> reads these keys that point at external systems:

| Key | Read in | Where the value lives | Anchor |
|---|---|---|---|
| <OTEL_EXPORTER_OTLP_ENDPOINT> | `<path/File.ext>` | <environment — deployment config outside the repo> | [VERIFY: <path/File.ext#Symbol>] |
| <Payments:BaseUrl> | `<path/File.ext>` | [VERIFY: src/appsettings.json#Payments] | [VERIFY: <path/File.ext#Symbol>] |

<Only keys that point at external systems (endpoints, service names, credential locations of a
counterpart). Keys that affect process start belong to `overview.md`, section "startup and
configuration"; a key appears in exactly one of the two tables. Keys, not values: the third column
anchors the config file by key, or names the secret store or the environment. Never copy the value.>

## <repo-id>: aliases

<repo-id> uses these canonical ids. Aliases serve retrieval — a query by an old name must find the right module.

Aliases: <canonical-id> = <abbreviation>, <old name>, <name in another system>

| Canonical id | Aliases | What it is | Alias source |
|---|---|---|---|
| <orders> | <orders-svc>, <API> | <module of the repo> | `<path/File.ext#Symbol>` |

<State an alias only when it has support — a name in configuration, service discovery, CI, or an
evidenced old name. An invented alias is a hallucination like any other. Every alias in the
manifest is named here (the validator checks it); when the manifest has none, this section says so
in one sentence.>
```

## Keys, not values

The environment table names the key and says where the value lives — an anchor into the config
file by key (`[VERIFY: src/appsettings.json#ConnectionStrings]`), the secret store, or the
environment. Never copy the value. The validator rejects connection strings, hosts, credentials,
tokens and internal addresses.
