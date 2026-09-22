# Template: `reference/<module-id>.md`

One file = one module that passed the threshold (see SKILL.md).
Fill in the `<...>` slots. A section for which the code holds no evidence is **omitted entirely**
(do not write "none"). Minimum 3 H2 sections, maximum 6.
`<module-id>` = the canonical module id (lowercase, e.g. `orders`), **the same in every heading**
and in the `module:` front matter.

```markdown
---
title: "<repo-id> — <module-id> module"
repo: <repo-id>
module: <module-id>
generated-by: skill:generate-docs@0.3.5
source-commit: <sha of HEAD = source_commit of the fragment>
last-generated: <ISO date>
covers: [<path/to/module>]
confidence: draft
tags: [ai-generated, repo-<repo-id>, module-<module-id>, type-reference]
---

Repo: <repo-id> · Module: <module-id> · Type: reference · Status: current

## <module-id>: purpose

<module-id> <does X — one sentence, subject by full name, no pronoun>. <2–4 sentences on what the
module takes care of in the repo and what that follows from> [VERIFY: <path/File.ext#Symbol>].

> ⚠ add business context (elicitation)

## <module-id>: public interface

<module-id> exposes <N> public <endpoints|members|commands>.

| Member | Signature / path | Behaviour | Anchor |
|---|---|---|---|
| <name> | `<GET /orders/{id}>` | <what it does — from the code, not from the name> | [VERIFY: <path/File.ext#Symbol>] |

<Optional: 1–3 sentences on non-trivial behaviour the table cannot carry — authorisation, filtering, error states.
When the module registers more routes than fit 10 rows, group them by route group or controller —
one row per group, every route name greppable in its cell (`/blog/{slug}`, `/blog/rss.xml`, …);
the manifest lists every route regardless.>

## <module-id>: dependencies

<module-id> depends on <list>. Structured lines:

calls → <canonical-id> (<sync|async>, <protocol>)
called from → <canonical-id>

<1–2 sentences on what flows over that link> [VERIFY: <path/File.ext#Symbol>].

## <module-id>: data model

<module-id> works with <entity(ies)>.

| Entity | Field | Type | Note | Anchor |
|---|---|---|---|---|
| <Order> | <Total> | <decimal, computed> | <constraint from the code> | [VERIFY: <path/File.ext#Symbol>] |

## <module-id>: external links

<module-id> communicates outside the repo with <system/library/service>.

calls → <canonical-id> (<sync|async>, <protocol>)

<A sentence on what exactly is sent out / where it is read from> [VERIFY: <path/File.ext#Symbol>].

## <module-id>: known limitations

<module-id> has these limitations evidenced in the code: <TODO/FIXME comments, unsupported paths,
hardcoded values, commented-out code with a TODO> [VERIFY: <path/File.ext#Symbol>].
<A hardcoded value is named by its symbol or configuration key and anchored where the value lives,
e.g. [VERIFY: src/appsettings.json#ConnectionStrings]. Never copy the value.>
```

## Rules the validator fails on

Errors (exit code 1):

- Every H2 is `## <module-id>: <section>` — subject, colon, space, section name. Any other H2 fails.
- The subject of every heading equals `module:` in the front matter, character for character.
  `tags` carry `module-<module-id>`, `repo-<repo-id>`, `ai-generated` and exactly one `type-*`.
- The plain-text metadata line `Repo: … · Module: … · Type: … · Status: current` is in the body.
- Anchors `[VERIFY: <path/File.ext#Symbol>]`: the path is relative to the repository root, the file
  exists and the symbol occurs in it. `[VERIFY: <path/File.ext:line>]` only where the evidence has
  no name; the line must exist.
- No "see above" / "see below" / "as mentioned above" / "as described below" — retrieval returns
  a section without its surroundings.
- No values: connection strings, hosts, credentials, tokens, internal addresses. Keys and anchors only.
- `covers` is not empty and lists existing paths; links resolve to files inside `ai-docs/`.

Warnings (errors with `--strict`):

- 3–6 H2 sections per page; the first sentence of a section repeats the subject by full name —
  not "This module", not "It serves".
- A section outside ~120–1000 tokens (target 250–600).
- A table with more than 10 data rows. Longer → pick the essential rows and summarise the rest in
  a sentence with an anchor.
- A symbol anchor that occurs more than 8× in the file — anchor on something narrower.
- Private IP addresses and e-mail addresses.
