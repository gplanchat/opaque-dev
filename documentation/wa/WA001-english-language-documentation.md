# WA001 — English language for project documentation

## Status

Accepted

## Context

Opaque is developed in the open and relies on shared written material (ADRs, working agreements,
tracking notes). A single written language for specifications, rules and operational docs reduces
ambiguity and keeps search, review and automation consistent.

## Agreement

**All** of the following **must be written in English**:

1. **Tracking and journals** — `documentation/journal/` (inbox entries, README) and session notes
   used as project memory.
2. **Specifications and architecture** — ADRs (`documentation/adr/`), working agreements
   (`documentation/wa/`), OpenSpec changes, and `documentation/INDEX.md`.
3. **Agent instructions** — `CLAUDE.md` and the prompts under `loop/`.

### Scope clarification

- **Code** (identifiers, user-visible strings) follows product needs; this WA governs
  documentation and rule text in the paths above. WA006 widens it to every working surface.
- **Third-party quotes** or RFC excerpts may remain in the original language if cited as
  references.

## Consequences

- Reviews may reject non-English additions to the listed areas.
- An agent writes English for any new text in `documentation/`, `loop/` and `CLAUDE.md`.

## References

- [WA006 — English is the working language](WA006-english-is-the-working-language.md)
- [documentation/INDEX.md](../INDEX.md)
