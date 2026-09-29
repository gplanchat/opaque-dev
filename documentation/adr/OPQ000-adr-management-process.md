# OPQ000 — ADR management process

## Status

Accepted

## Context

The **Opaque** component needs one place where architecture decisions are recorded, with the
alternatives they turned down, so that code changes can point at an explicit choice.

## Decision

**Architecture Decision Records (ADRs)** for the Opaque project are numbered with the **`OPQ`**
prefix, followed by a three-digit identifier and a short kebab-case title.

### Location

- Directory: `documentation/adr/`
- Files: `OPQ{NNN}-{short-title}.md` (e.g. `OPQ001-opaque-and-ciphered-value-objects.md`)

### Numbering

- **OPQ000**: this document (meta-process)
- **OPQ001 onward**: sequential numbering; a withdrawn or obsolete number is never reused

### META documents

When a decision needs more detail than fits in one file, supplementary documents may live in a
subfolder: `documentation/adr/OPQ{NNN}-{short-title}/OPQ{NNN}-META{MM}-{topic}.md`.

### Structure

1. Title and identifier
2. Status (draft, proposed, accepted, deprecated, superseded by OPQ{NNN})
3. Context
4. Decision
5. Consequences (positive, negative, follow-up as needed)

### An ADR is a record, not a manual

An accepted ADR states what was decided when it was written. It is **not edited** afterwards to
follow the code: a decision that changes gets a new ADR that supersedes the old one, and the old
one only gains a status line pointing at its successor. New ADRs are a human decision
(`CLAUDE.md`).

### Index

The up-to-date ADR list is maintained in `documentation/INDEX.md`.

## Consequences

- Any major architectural decision in the Opaque scope is reflected by a new ADR.
- Future implementations can refer to these records without relying on documents outside the
  repository.
