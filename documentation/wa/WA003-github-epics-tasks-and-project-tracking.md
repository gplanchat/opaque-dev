# WA003 — GitHub epics, tasks, and project tracking

## Status

Accepted

## Context

Work is tracked with **GitHub Issues** and, when used, **GitHub Projects**. Without shared
conventions, titles, bodies and parent links drift, boards become hard to read, and planning
documents stop matching reality.

## Agreement

### Issue kinds and title prefixes

| Kind | Title prefix | Purpose |
|------|--------------|---------|
| **Epic** | `[EPIC]` | Large outcome spanning several tasks or stories. |
| **Task** | `[Task]` | Concrete work unit, often technical or incremental. |
| **Story** | `[Story]` | User- or outcome-oriented slice with acceptance criteria. |

Prefixes are mandatory for new maintainer-filed issues of these kinds.

### Labels

- Consistent labels for **kind** (`epic`, `task`) and **area** (for instance `core`,
  `bridge-serializer`, `ci-publication`, `docs`, `process`, `agentic`).
- `human-decision` marks an item that touches a supervised path or settles a design, and therefore
  does not run unattended.
- A **batch label** groups the issues of one programme or one audit, so filters stay usable.

### Who the prefixes bind

The prefixes bind **maintainer-filed** issues. An issue filed from outside keeps the title its
author gave it; the trust gate labels it (WA007), and a maintainer who adopts it links it to its epic
with a comment rather than by retitling it.

### Parent / child linkage

- Tasks and stories that roll up to an epic state the parent in the body: `Parent Epic: #NN`.
- Prefer **GitHub sub-issues** so the hierarchy is visible on GitHub, not only in prose.

### Issue bodies

**Epics**: a link to the planning document, the **objective**, the **main deliverables**, and
**references** (ADRs, OpenSpec changes).

**Tasks and stories**: the plan link when there is one, a **description**, acceptance criteria as
checkboxes for stories, and the **Parent Epic** line.

### Planning document hygiene

- When issues are filed from a planning document, write their numbers back into it.
- **Re-audit at every milestone exit**: the audit that produced a milestone's issues is re-run when
  the milestone closes, with a prior-findings table (fixed / open / partial, one verified anchor
  each), and the delta is filed as issues.

### Scope

This WA governs how issues are structured, not the roadmap.

## Consequences

- Reviews of planning may reject ad hoc titles or missing parent links for hierarchical work.

## References

- [WA001 — English language for project documentation](WA001-english-language-documentation.md)
- [WA007 — The agentic loop and its ledgers](WA007-the-agentic-loop-and-its-ledgers.md)
- [documentation/INDEX.md](../INDEX.md)
