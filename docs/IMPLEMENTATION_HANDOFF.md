# Koravik-Final Implementation Handoff

**Status:** Builds 063–067 implemented on `main`  
**Version:** 1.67  
**Baseline date:** July 29, 2026  
**Authoritative branch:** `main`

## Repository authority

This handoff applies only to `arwentz01/Koravik-Final`. Do not import code, schemas, routes, build numbers, deployment assumptions, or framework conventions from earlier Koravik repositories or unrelated projects.

Before implementation, read `README.md`, `docs/README.md`, `docs/FOUNDATIONAL_DECISIONS.md`, all canonical documents, affected Product and Engineering Blueprint contracts, the ADR register, and this handoff. The documentation authority order in `docs/README.md` controls conflicts.

## Architecture baseline

Koravik-Final is a PHP 8.3+ custom modular monolith using MySQL or MariaDB through PDO and an Apache-compatible shared-hosting model.

- Personal participation does not require an Organization or Household.
- Organizations are optional contextual operating spaces.
- Platform account roles and Organization membership roles are separate.
- Districts retain ownership of their domain truth.
- Hearth composes but does not absorb Organization records.
- Beacon owns domains, links, pages, and QR definitions even when an Organization owns the resource context.
- Gather owns event truth even when an Organization owns the event context.
- Authorization is capability-based and contextual.
- Background work is finite, idempotent, lock-safe, and cron compatible.

## Completed implementation arc

### Builds 001–057

Platform identity and security, Hearth, Quests, Chronicle, Companion, Notifications, Search, Privacy and Audit, account-data lifecycle, Epic Ordinary and World lifecycle, first-use orientation, Journey experiences, Beacon/Gather foundation, Platform Mail, Gather operational tooling, agendas, reminders, day-of operations, closeout, Beacon domains, and consent-gated outcomes.

### Builds 058–062 — Stabilization and Beacon completion

- reminder cancellation and unsubscribe;
- approved-outcome application ledger;
- Beacon domain administration;
- Beacon link management and revision history;
- secure-context camera QR scanning with manual fallback.

See `docs/builds/BUILD_058_062_STABILIZATION_BEACON_COMPLETION.md`.

### Builds 063–067 — Organization foundation

- Build 063: optional Organization identity and profile lifecycle;
- Build 064: contextual Owner, Admin, Creator, and Member roles with invitations and membership controls;
- Build 065: Organization-owned Gather events and contextual command-center authorization;
- Build 066: Organization-owned Beacon links with stable platform fallback;
- Build 067: Organization operating home with members, invitations, events, links, activity, and capability-aware actions.

See `docs/builds/BUILD_063_067_ORGANIZATION_FOUNDATION.md`.

## Current migrations

Production deployment must apply every file in `database/migrations`, including:

```text
037_platform_mail_operations.sql
038_042_gather_lifecycle_beacon_domains.sql
043_047_lifecycle_stabilization_beacon_management.sql
048_052_organization_foundation.sql
```

Run:

```bash
php tools/migrate.php
```

## Background workers

```bash
php tools/mail-worker.php 20
php tools/gather-reminder-worker.php 100
```

## Organization routes

- `GET|POST /organizations`
- `GET /organizations/{id}`
- `GET /organizations/invitations/{token}`
- `POST /organizations/{id}/invitations`
- `POST /organizations/{id}/members/{membershipId}/role`
- `POST /organizations/{id}/members/{membershipId}/remove`
- `POST /organizations/{id}/ownership/{membershipId}`
- `POST /organizations/{id}/events`
- `POST /organizations/{id}/links`

## Organization rules

- Organization membership is optional.
- Creating an Organization creates one active Owner membership for the creator.
- Organization role never grants a platform-wide role.
- The final Owner cannot be removed by ordinary membership controls.
- Ownership transfer is atomic and leaves one active Owner.
- Organization-owned resources survive member departure.
- Personal records do not transfer into Organizations.
- Organization invitations are email-bound, expiring, and token-hashed.
- Organization-created Gather and Beacon resources retain their District ownership boundaries.

## Beacon domain rules

- `krvk.nl` remains the default verified platform short-link domain.
- `https://krvk.nl/` permanently redirects to `https://koravik.com/`.
- Custom hostnames require verification before activation.
- Organization-owned Beacon records keep stable UUID identity and a platform fallback.
- DNS and certificate provisioning remain hosting responsibilities.

## Explicit current boundaries

- Remaining legacy Gather day-of and communication ownership predicates still require reconciliation with `GatherAuthorization` before full multi-manager Organization event operations are declared complete.
- Invitation creation currently exposes a secure acceptance path to the authorized inviter; Platform Mail delivery of Organization invitations remains future work.
- Organization lifecycle editing, suspension, archive recovery, and ownership-safe deletion are not yet exposed visually.
- Organization domains use the existing Beacon domain administration foundation; Organization-scoped domain selection needs a focused integration pass.
- Household remains separate, independent, and optional.
- Payments and external calendar synchronization remain deferred unless separately approved.

## Validation

The single workflow at `.github/workflows/validate.yml` must lint PHP and validate migration `048_052_organization_foundation.sql`, contextual membership roles, Organization-owned Gather and Beacon creation, Organization operating routes, `GatherAuthorization`, and the Build 067 health checkpoint.

## Next build

Build 068 should stabilize Organization operations before broadening scope. Recommended arc: complete contextual authorization across Gather day-of and communications, automate invitation delivery and acceptance management, implement Organization lifecycle/settings, integrate Organization-scoped Beacon domains and branding, and add audit/recovery controls.

## Build workflow

For every build: inspect current `main`, read affected authority, identify the player-visible outcome and visual home, implement one coherent vertical slice, validate it, update affected documentation, and land one cohesive milestone.
