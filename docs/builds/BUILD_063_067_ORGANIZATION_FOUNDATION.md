# Builds 063–067 — Organization Foundation

## Outcome

Koravik now supports optional Organizations as shared operating spaces without requiring Organization membership for personal participation.

## Build 063 — Organization identity

- organization profile and lifecycle schema;
- creator becomes the first contextual Owner;
- personal Hearth and personal records remain independent;
- `/organizations` provides creation and membership navigation.

## Build 064 — Memberships and contextual roles

Roles are contextual to one Organization:

- Owner;
- Admin;
- Creator;
- Member.

Invitations are email-bound, token-hashed, expiring, and accepted only by the matching active Koravik account. Admins and Owners may manage non-owner memberships. Ownership transfer demotes the former Owner to Admin and promotes one active member atomically.

## Build 065 — Organization-owned Gather

Organization Owners, Admins, and Creators may create Organization-owned Gather events. Event records retain Gather ownership of event truth while recording `owner_type=organization` and `organization_id`. The Gather command center now authorizes contextual Organization managers through `GatherAuthorization`.

## Build 066 — Organization-owned Beacon

Organization Owners, Admins, and Creators may create Organization-owned Beacon links. Links retain stable UUID identity and the existing `krvk.nl` fallback architecture while recording Organization ownership separately from the creating account.

## Build 067 — Organization operations home

`GET /organizations/{id}` provides:

- Organization identity and current member role;
- recent and upcoming events;
- Organization Beacon links;
- member and invitation administration according to capability;
- content creation for authorized roles;
- recent operational activity.

## Boundaries

- Organization membership is optional.
- Personal accounts, Hearth, Quests, Chronicle, Journey, Companion, and Worlds are not transferred into an Organization.
- Platform roles and Organization roles remain separate.
- Organization membership removal does not delete Organization-owned resources.
- The final active Owner cannot be removed through ordinary membership controls.
- Gather command-center authorization supports Organization managers. Remaining legacy day-of and communication ownership predicates require a dedicated stabilization pass before full multi-manager operations are declared complete.
- Organization invitation delivery is not yet automated through Platform Mail; the secure acceptance path is generated for authorized sharing.
- Organization domain assignment builds on Beacon domain administration but automated DNS/TLS provisioning remains outside application authority.

## Deployment

Run:

```bash
php tools/migrate.php
```

Migration:

```text
database/migrations/048_052_organization_foundation.sql
```
