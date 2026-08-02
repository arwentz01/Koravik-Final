# Builds 198–207: Source Inbox Maturity

Builds 198–207 make the Source Inbox more useful day-to-day without turning it into a hidden automation engine.

## Implemented slices

1. Source Inbox Counts — `/source-review` now shows total waiting items and bucket counts.
2. Source Owner Filters — the inbox can filter by Chronicle, Companion, Gather, and Healing Home.
3. Top Priority Card — the newest visible item is promoted into a “review now” card.
4. Resume Later Affordance — decisions can be parked safely without dismissal, approval, execution, or read-state mutation.
5. Filtered Empty States — empty filtered views explain that nothing changed and offer a clear-filter action.
6. Source Owner Styling Hooks — cards now include source-owner classes for visual differentiation.
7. Hearth Source Inbox Badge — Hearth shows Source Inbox counts by owner.
8. Safer Review Metadata — cards expose timestamps and stable resume tokens.
9. System Health Checkpoint — health identifies Build 207 and `source-inbox-maturity`.
10. Release Contract Coverage — the release suite covers counts, filters, top-priority, resume-later, Hearth badge, checkpoint, and docs.

## Contract

- Filtering and resume-later are read-only.
- The Source Inbox still creates no destination records by itself.
- Hearth displays counts but does not approve, dismiss, execute, publish, or mark anything read.
- Build 207 is the current operational checkpoint: `source-inbox-maturity`.
