# Builds 188–197: Actionable Cross-Module Flow

Builds 188–197 make Koravik’s “something happened → review it → decide what it becomes” loop visible across modules.

## Implemented slices

1. Hearth Source Inbox — `/source-review` gathers source-owned decisions from Chronicle proposals, Companion proposals, Gather outcomes, Gather follow-up drafts, Healing Home room notes, and notifications.
2. Quest-from-Anywhere Draft Bridge — `/quests/create` accepts prefilled draft context and preserves source provenance through `origin_type` / `origin_reference`.
3. Chronicle-from-Anywhere Reflection Bridge — `/chronicle/new` accepts source context, title, body, and tags while explaining Chronicle ownership before save.
4. Source Draft Review Center — the Source Inbox shows review cards with source owner, type, summary, consequence, and source link.
5. Decision Consequence Preview — cross-module draft routes explain what changes, what does not, and who owns the result.
6. Hearth Today Decision Strip Upgrade — Hearth links directly to the Source Inbox and nearby review surfaces.
7. Companion Proposal Routing Upgrade — Companion now routes proposal decisions into the same Source Inbox context.
8. Healing Home Room Note Promotion — private room notes can intentionally start Quest or Chronicle drafts without automatic promotion.
9. Gather Follow-Up to Quest/Chronicle Drafts — Gather follow-up drafts can intentionally start Quest or Chronicle drafts while Gather remains source owner.
10. Release Checkpoint + Audit Coverage — health identifies Build 197 and release tests cover the cross-module flow.

## Contract

- The Source Inbox creates no destination records by itself.
- Quest and Chronicle drafts are prefilled, not saved, until the destination form is submitted.
- Source records remain owned by their original modules.
- Build 197 is the current operational checkpoint: `actionable-cross-module-flow`.
