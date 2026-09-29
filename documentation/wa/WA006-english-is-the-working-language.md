# WA006 — English is the working language

## Status

Accepted. Widens [WA001](WA001-english-language-documentation.md), which stays in force.

## Context

WA001 settles the language of `documentation/` and the agent instructions. It says nothing about
everything else the project writes, and that gap fills itself with whatever language the author
thinks in. A contributor who lands on a pull request cannot review a change whose justification
they cannot read — and the justification is the part that matters, because this project writes down
*why* a fix takes the shape it takes. English documentation that cites a non-English artefact also
sends its reader out of the language they were reading in, mid-sentence.

## Agreement

**English is the working language of this project**, in the repository and on GitHub:

| Surface | Language |
|---|---|
| Pull request titles and bodies | English |
| Issue titles and bodies | English |
| Comments — on issues, on pull requests, inline review comments | English |
| Review summaries and approvals | English |
| Commit messages, including trailers | English |
| Prise files under `.worktrees/prises/` | English |
| Journal entries under `documentation/journal/` | English (already required by WA001) |
| ADRs, WAs, OpenSpec changes | English (already required by WA001) |
| Code identifiers, docblocks, comments in `src/` and `tests/` | English |
| Release notes and tags | English |

### The one exception

A **translation of the user documentation**, if the project ever ships one, is a product for its
readers, not a working language. The test for any future case: *is this text addressed to a user of
Opaque, in a language we chose to serve them in?* If yes, it follows the product, and its English
counterpart is the source of record — when the two disagree, the translation is the bug. If the text
is addressed to whoever works on Opaque, it is English.

**Third-party quotations** stay in their original language, as WA001 allows.

### What this does not do

It does not rewrite history. Commit messages already written stay as they are: rewriting them would
invalidate every reference to them.

## Consequences

- Reviews may reject a non-English addition to any surface in the table above.
- An agent working on this repository writes English by default and asks before writing anything
  else.

## References

- [WA001 — English language for project documentation](WA001-english-language-documentation.md)
- [documentation/INDEX.md](../INDEX.md)
