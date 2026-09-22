# Template: how to write sections (applies to every `ai-docs/` page)

Rules for the shape of the text, shared by all templates. There is one reason for them: **retrieval
returns one section without its surroundings**. What makes sense only in the context of the page
is useless after chunking — and through Confluence/Rovo that is how most readers reach it.

## Sentence form

- This template governs the shape of a section; the sentences inside it follow
  `esterka:writing-technical-docs-in-ste` (ASD-STE100 Simplified Technical English) whenever that
  skill is available in the session (plugin `esterka@packeta-dev` <!-- packeta -->). Invoke it before
  writing. Without it, this template alone applies. Code, identifiers and anchors are quoted text
  under both and never change.

## Headings and subject

- **An H2 carries the subject by full name:** `## orders: purpose`, `## orders: public interface`.
  Never a generic "Overview" / "Details".
- **The first sentence of a section repeats the subject by full name.** No pronouns ("the module",
  "this service"), no "see above" / "see below" / "as mentioned above" / "as described below" —
  the validator rejects the phrases and warns about a first sentence without the subject.
- The subject in the heading must equal `module:` in the front matter (for `reference/`) or
  `<repo-id>` (every other page).

## Size

- An H2 section targets **250–600 tokens** (the validator warns below 120 on a reference page,
  below 60 on one of the four shared pages, and above 1 000), a
  page **3–6 H2 sections**. Never pad a section to reach a number: a short section that says
  everything the code evidences is finished.
- The ceiling holds for large repos too: when a module does not fit, **split it across several
  pages** — do not inflate one. A thousand-line document is one unusable chunk and a token cost
  on every answer.
- Tables without merged cells, max 10 data rows. Longer → pick the essential rows and summarise
  the rest in a sentence with an anchor.

## What the body must repeat

- **Metadata in plain text** directly below the front matter:
  `Repo: <repo-id> · Module: <module-id or —> · Type: <type> · Status: current`.
  The front matter does not survive publication to Confluence and the MCP read tools do not
  return it — what is not in the body, an agent reading the page will not see.
- **Dependencies as structured lines**, not prose, one of four kinds:
  `calls → <canonical-id> (sync, HTTP/JSON)` — a runtime call this module makes;
  `called from → <canonical-id>` — a runtime call this module receives;
  `references → <canonical-id>` — a project or package reference (the module compiles or installs
  against it; no runtime call is implied);
  `hosts → <canonical-id>` — this module serves another module's build (a SPA bundle, static
  files) from its own process.
  `<canonical-id>` is a `<module-id>` of this repo (a module under the threshold included — it has
  a module id by the convention and a `modules:` entry), the `<repo-id>` of another repo, or the
  name of an external system — copied from `manifest.yaml` character by character, never an ad-hoc
  name; the validator rejects an id the manifest does not know. These lines can be grepped and
  queried through CQL; prose cannot. Examples in `dependencies.md`.
- **A textual description of the links next to every Mermaid diagram.** A diagram is information
  in pixels: invisible to retrieval and to an agent without a renderer. When text and diagram
  disagree, the text wins.
  A `<canonical-id>` is lowercase letters, digits, `-` and `.`: a module id of this repo (also one
  under the threshold), the `service` of another repo, an external system (`smtp`, `auth0`) or the
  engine of a data store from `owns_data[].store` (`sqlite`). Never a test project (say "tested by
  `Todo.Api.Tests`" in prose instead) and never a display name with spaces or capitals.

## Links, anchors and values

- Relative links to `.md` files between pages — the publishing pipeline rewrites them to Confluence
  URLs. A link never leaves `ai-docs/`.
- A link never replaces a claim. "Details in X" in a section that is supposed to answer the
  question is a hole.
- **Anchors `[VERIFY: <path/File.ext#Symbol>]`** — path relative to the repository root, symbol
  present in the file. `[VERIFY: <path/File.ext:line>]` only where the evidence has no name. An
  anchor supports the claim it stands next to; two claims in one sentence = two anchors. The
  symbol part never contains `<`, `>`, `]` or `|`: generic arguments in any syntax (C#
  `ReadFromJsonAsync<AuthToken>` → `ReadFromJsonAsync`, PHPDoc `Collection<int, Comment>` →
  `Collection`) are dropped, and the anchor sits on the nearest named symbol. Never anchor into
  `ai-docs/ast/map.json` (flat JSON, keys repeat); cite the map through `ai-docs/ast/map.md` with
  `path:line`, or state the number without an anchor.
- **Angle brackets only inside code spans.** Any angle-bracket token in prose (`<AuthToken>`,
  `Authentication:Schemes:<Name>`) must sit inside a code span — the Confluence converter reads a
  bare `<Name>` as an HTML tag and the page fails to convert.
- **Keys, not values.** Configuration keys, endpoints and names are copied; connection strings,
  hosts, credentials, tokens, internal addresses and personal data are never written — anchor
  where the value lives instead (`[VERIFY: src/appsettings.json#ConnectionStrings]`, the secret
  store, the environment). Never copy the value.
