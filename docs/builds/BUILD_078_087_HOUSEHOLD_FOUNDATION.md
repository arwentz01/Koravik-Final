# Builds 078–087 — Household Foundation

## Outcome

Koravik now supports optional, private Households for home-life coordination without transferring personal identity, Quest ownership, Chronicle history, or unrelated Account authority.

## Build 078 — Build 077 acceptance

The Build 077 candidate passes PHP 8.3 syntax validation across `src`, `public`, and `tools`. Static checkpoint and whitespace validation remain part of the release workflow.

## Build 079 — Household identity

Accounts may create a Household with a name, summary, timezone, preferences, lifecycle state, and one protected Owner membership. Household participation remains optional.

## Build 080 — Membership and roles

Households support contextual Owner, Admin, and Member roles; email-bound, expiring Platform Mail invitations; role changes; removal; and atomic ownership transfer. Household roles never grant Platform authority.

## Build 081 — Quest proposals

Members may propose a responsibility to another active member. The recipient must explicitly accept before Quests creates a personal Quest. Declining creates no Quest.

## Build 082 — Recurring responsibilities

Responsibility proposals may carry one-time, daily, weekly, monthly, or yearly recurrence intent. After acceptance, Quests owns recurrence, occurrences, progress, and completion.

## Build 083 — Private resources

Owners and Admins may maintain private Household instructions, contacts, links, and references. Resources are never publicly published by default.

## Build 084 — Gather integration

Household members may create private Household Gather events. Gather owns event, RSVP, attendance, and operational truth while Household membership supplies contextual authorization.

## Build 085 — Household home

The Household home composes members, pending choices, responsibilities, events, resources, invitations, and lifecycle controls without replacing personal Hearth.

## Build 086 — Notifications and recovery

Platform Notifications explains Household responsibility proposals and membership changes. Platform Mail handles invitations. Lifecycle transitions preserve dedicated recovery records and activity history.

## Build 087 — Release stabilization

The release checkpoint validates Household migrations, contextual authorization, private-by-default resources and events, proposal consent, notification categories, lifecycle recovery, and the health identifier.

## Migration

```text
database/migrations/063_072_household_foundation.sql
```

## Boundaries

- Household membership is optional.
- Household authority is contextual.
- Resources and events are private by default.
- Quests owns action and recurrence truth after acceptance.
- Gather owns event and attendance truth.
- Hearth only composes Household context.
- No public Household Beacon page is introduced.
