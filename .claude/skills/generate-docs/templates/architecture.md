# Template: `architecture.md`

How the modules hold together and where the requests flow. Not a repetition of `reference/` — this
page is about the **links between** modules. Subject of the headings = `<repo-id>`.

````markdown
---
title: "<repo-id> — architecture"
repo: <repo-id>
module: null
generated-by: skill:generate-docs@0.3.5
source-commit: <manifest source_commit>
last-generated: <ISO date>
covers: [<paths of the entry points and of the places where the links are established>]
confidence: draft
tags: [ai-generated, repo-<repo-id>, type-architecture]
---

Repo: <repo-id> · Module: — · Type: architecture · Status: current

## <repo-id>: module composition

<repo-id> is built as <topology — e.g. "frontend → BFF → API → DB"> [VERIFY: <path/File.ext#Symbol>].

```mermaid
graph LR
  <A>[<module A>] --> <B>[<module B>]
```

Textual description of the diagram's links (**mandatory** — the diagram is invisible to retrieval):

calls → <canonical-id-B> (<sync>, <HTTP/JSON>)
called from → <canonical-id-A>

<One sentence per edge: what flows over it and where it is established> [VERIFY: <path/File.ext#Symbol>].

## <repo-id>: runtime flow <flow name>

<repo-id> handles <flow — e.g. "user sign-in"> like this:

1. <step — who, what, where to> [VERIFY: <path/File.ext#Symbol>]
2. <step>

<A sentence on what the flow assumes and what happens on failure — only when it is in the code.>

## <repo-id>: authentication and authorisation

<repo-id> authenticates <what, how> [VERIFY: <path/File.ext#Symbol>].

| Boundary | Mechanism | Where | Anchor |
|---|---|---|---|
| <browser → BFF> | <cookie> | `<path>` | [VERIFY: <path/File.ext#Symbol>] |

## <repo-id>: data and persistence

<repo-id> stores <what, where> [VERIFY: <path/File.ext#Symbol>].
<A sentence on the schema/migrations and who owns them.>

## <repo-id>: cross-cutting mechanisms

<repo-id> applies across modules <rate limiting, telemetry, validation, health checks — only what is in the code>.

| Mechanism | Scope | Anchor |
|---|---|---|
| <rate limiting> | <which endpoints> | [VERIFY: <path/File.ext#Symbol>] |

<One sentence per in-process event family — dispatcher, event family, who handles it, e.g. "<repo-id>
dispatches `CommentCreatedEvent` through Symfony EventDispatcher to `CommentNotificationSubscriber`"
[VERIFY: <path/File.ext#Symbol>]. In-process events have no channel and are never manifest entries.>
````

## Watch out

- **Never the diagram alone** — always the textual description of the links next to it.
- **In-process events** (Symfony EventDispatcher, MediatR, .NET events) are described here, one
  sentence per event family; they never go into `publishes`/`consumes` (no channel).
- Describe 1–3 flows (the main ones), not all of them. The page ceiling is 6 H2 sections.
- The business "why" of the architecture (why a BFF, why this DB) is not in the code →
  `> ⚠ add business context (elicitation)`.
