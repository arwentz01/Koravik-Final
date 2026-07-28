# Product Experience

## Experience objective

Koravik should help a person answer three questions with minimal friction:

1. What matters now?
2. What is the next meaningful action?
3. What changed because I acted?

## Primary experience

Hearth is the daily orientation surface. It composes relevant information from owning Districts, active World continuation, and approved Companion proposals. Hearth does not own the underlying records.

The default experience must remain useful without a Household, Organization, social connection, Companion, or installed World.

## Core product areas

### Hearth

Provides orientation, continuation, and a calm path to action. It should be curated and bounded rather than an unlimited dashboard.

### Quests

Owns tasks, responsibilities, projects, recurring actions, and completion state. A Quest may exist independently or be associated with a Household, Organization, or World-facing interpretation.

### Chronicle

Owns reflective and historical records intentionally preserved by the person. Drafts proposed by Companion or a World require approval before Chronicle saves them.

### Health

Owns health-related records and workflows. Sensitive details must remain minimized, consent-aware, and unavailable to Worlds when a derived condition is sufficient.

### Gather

Owns events, attendance, invitations, participation, and shared experiences.

### Beacon

Owns public presentation, outreach, campaigns, and organization-facing communication surfaces.

### Worlds

Provide optional persistent narrative contexts. Worlds interpret explicitly approved, minimized platform facts into independent World State.

### Companion

Supports orientation, explanation, summarization, drafting, and suggested next actions. Companion must reveal sources and request approval for consequential actions.

## Interaction principles

- One primary question per screen.
- Clear hierarchy before additional features.
- Helpful empty states rather than dead ends.
- Visible loading and recovery states.
- Plain-language explanations for permissions and consequences.
- Confirmation proportional to consequence.
- Safe undo or correction where feasible.
- Return paths that preserve context.
- No punishment for absence.

## Returning after absence

Koravik should acknowledge elapsed time without moral judgment. It should summarize meaningful changes, identify unfinished items that still matter, allow stale intentions to be dismissed, and offer a manageable next step.

## First complete vertical slice

The initial proof should support this journey:

1. Sign in securely.
2. Enter the shared application shell.
3. Open Hearth.
4. Open and complete one Quests-owned action.
5. Commit the change and publish a minimized platform event through the outbox.
6. Allow Epic Ordinary to interpret the approved fact.
7. Show an explainable World reaction and durable World State change.
8. Return the person to Hearth with clear continuation.
9. Leave and later resume safely.

## Success standard

A feature is not complete merely because its route, table, or service exists. It is complete when the person can understand the purpose, perform the action accessibly, see the result, recover from interruption, and understand any consequential downstream behavior.