# Builds 068–077 — Organization Operations

## Outcome

Organizations are now recoverable, multi-manager operating spaces with secure invitation delivery, scoped teams, Beacon-owned public presence, and consent-first Quest coordination. Personal identity and personal records remain independent.

## Build 068 — Authorization stabilization

Gather day-of, communication, lifecycle, workflow, command-center, and closeout management use `GatherAuthorization`. Organization Owners, Admins, and Creators receive the same scoped event-management path; inactive memberships receive none.

## Build 069 — Invitation delivery and management

Organization invitations are queued through Platform Mail. Tokens remain hashed, expire after fourteen days, and may be rotated by resend or invalidated by revoke. Acceptance remains email-bound.

## Build 070 — Lifecycle and settings

Owners may update Organization identity, contact, timezone, and brand settings. Suspension, archive, and restoration preserve Organization-owned records.

## Build 071 — Domains and branding

Owners may select a verified Organization or platform Beacon domain. Public naming and brand color remain Organization metadata while Beacon owns published presentation and routing.

## Build 072 — Audit and recovery

Privileged Organization changes write activity records. Lifecycle transitions also write a dedicated recovery history with previous and new state.

## Build 073 — Teams

Organizations may create internal teams and associate active Organization memberships as leads or members. Team roles do not grant Platform or Organization-wide authority.

## Build 074 — Public presence

Authorized Organization content managers may publish an unlisted or public Beacon page. Beacon owns the page; the Organization supplies approved identity, links, and public Gather references.

## Build 075 — Quest proposals

Authorized Organization creators may propose a Quest to an active member. The recipient must explicitly accept before Quests creates a personal Quest. Declining creates no Quest, and the Organization never owns personal completion state.

## Build 076 — Coordination center

Organization settings compose team counts, pending invitation operations, Quest proposal status, recent events, links, and activity without copying District truth.

## Build 077 — Release stabilization

The release checkpoint validates the migration, contextual Gather authorization, mail-backed invitations, lifecycle recovery, teams, public presence, Quest proposal consent, and health identifier.

## Migration

```text
database/migrations/053_062_organization_operations.sql
```

## Boundaries

- Organization membership remains optional.
- Team roles do not expand Organization capabilities.
- Beacon owns public pages and domains.
- Gather owns event and attendance truth.
- Quests owns accepted Quest records and completion.
- Organization proposals cannot silently create personal Quests.
- DNS and TLS provisioning remain hosting responsibilities.
- Household remains a separate future context.
