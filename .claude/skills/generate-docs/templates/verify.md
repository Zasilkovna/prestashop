# Verify one ai-docs document against the code

You are verifying a document you did not write. Do not rewrite it. Classify every checkable
claim and report. Input: the path of one `ai-docs/**/*.md` and the repository root.

Granularity: **one table row per anchor** — a sentence with n anchors gives n rows; a checkable
claim without an anchor gives one row; a sentence that mixes a true and a false claim is split
into one row per claim. Navigation-only content (the page list in `index.md`, a table that copies
the manifest) gets one row per table, not per cell. A claim is a statement about the code (names,
calls, data, config keys, behaviour) or about another `ai-docs` page. Classes:
- CONFIRMED — the anchor (or your own lookup) shows exactly this.
- INCORRECT — the code says something else. Quote the code line. Also INCORRECT: a statement
  that contradicts another `ai-docs` page or `manifest.yaml`, even when no code is involved
  (page-vs-page drift; quote the other page), and a completeness claim ("these are all the
  internal links", "only transitive packages are left out") whose set differs from the table it
  summarises or from the code.
- UNCERTAIN — plausible, but no evidence found in this repository.
- NOT FOUND — the named thing does not exist in the code.
- PENDING — the standard placeholder `> ⚠ add business context (elicitation)`. It asks a person
  for the business "why", which is orthogonal to code correctness; it never blocks promotion.
Additionally flag:
- SENSITIVE — the line quotes a value (connection string, host, IP, credential, personal data).
  Never quote the value: give the line number and the kind only. An identifier-like value the
  document names but does not quote (a `UserSecretsId` GUID, an application discriminator) is
  not SENSITIVE; quoting the value is.
- INSTRUCTION — the line tells the reader or an agent to do something instead of describing code.
  `[VERIFY: …]` anchors are evidence references and the PENDING placeholder is a marker; neither
  is an INSTRUCTION.

Output a Markdown table `| line | claim | class | evidence |`, then one summary line:
`CONFIRMED n · INCORRECT n · UNCERTAIN n · NOT FOUND n · PENDING n · SENSITIVE n · INSTRUCTION n`.

When the document has a manifest fragment (`ai-docs/manifest.part.<module-id>.yaml`), verify it
too: a second table `| entry | class | evidence |` under the heading
`### manifest.part.<module-id>.yaml`, one row per entry, the same classes. An INCORRECT there
counts against the document like one in the prose.

Promotion rule for the generator (not for you). The ratio is computed over the CONFIRMED + INCORRECT
+ UNCERTAIN + NOT FOUND rows of both tables; SENSITIVE, INSTRUCTION and PENDING rows are counted
separately and do not enter it. All of those rows CONFIRMED and 0 SENSITIVE / INSTRUCTION →
`verified`; ≥ 80 % CONFIRMED and 0 INCORRECT / SENSITIVE / INSTRUCTION → `reviewed`; otherwise
`draft`. PENDING never changes the outcome. After fixing an INCORRECT finding the ceiling is
`reviewed`; `verified` needs another pass by someone who did not write or fix the document.
A record written under an older version of this rule is not rewritten; the generator computes
the promotion from its rows by the current rule.

Write the tables to `ai-docs-review/<doc-id>.md` in the repository root — outside `ai-docs/`, so
it is never published and the validator ignores it. `<doc-id>` is the document's path under
`ai-docs/` with `/` replaced by `-` and no extension (`reference-todo-api.md` for
`reference/todo-api.md`, `overview.md` for `overview.md`), one file per document, so parallel
verifiers never touch the same file; the shared `ai-docs-review/REVIEW.md` stays accepted. The
record starts with the heading `## <document path from the repository root> @ <source-commit> —
<YYYY-MM-DD>`, the path exactly as `ai-docs/reference/todo-api.md` and the commit copied from the
document's front matter; that heading is what `assemble.py` looks for, in any `ai-docs-review/*.md`.
