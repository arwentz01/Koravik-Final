# Koravik-Final Implementation Handoff

**Status:** Builds 058–062 implemented on `main`  
**Version:** 1.62  
**Baseline date:** July 29, 2026  
**Authoritative branch:** `main`

## Repository authority

This handoff applies only to `arwentz01/Koravik-Final`. Do not import code, schemas, routes, build numbers, deployment assumptions, or framework conventions from earlier Koravik repositories or unrelated projects.

Before implementation, read `README.md`, `docs/README.md`, `docs/FOUNDATIONAL_DECISIONS.md`, all canonical documents, affected Product and Engineering Blueprint contracts, the ADR register, and this handoff. The documentation authority order in `docs/README.md` controls conflicts.

## Architecture baseline

Koravik-Final is a PHP 8.3+ custom modular monolith using MySQL or MariaDB through PDO and an Apache-compatible shared-hosting model.

- Districts own real-life truth.
- Hearth composes but does not own source records.
- Beacon owns domains, short links, QR definitions, public pages, link hubs, digital cards, Wi-Fi cards, and privacy-aware distribution data.
- Gather owns events, agendas, RSVP, guest parties, signup slots, waitlists, attendance, reminders, communications, closeout, and event-outcome proposals.
- Worlds interpret approved minimized facts into independent fictional World State.
- Chronicle, Quests, Journey, and Worlds own records created from approved outcome handoffs.
- Authorization is capability-based and contextual.
- Background work is finite, idempotent, lock-safe, and cron compatible.

## Completed implementation arc

### Builds 001–052

Platform identity and security, Hearth, Quests, Chronicle, Companion, Notifications, Search, Privacy and Audit, account-data lifecycle, Epic Ordinary and World lifecycle, first-use orientation, Journey experiences, Beacon/Gather foundation, authenticated Platform Mail, Gather waitlists and operational tooling, organizer communications, check-in, and Platform Mail operations.

### Builds 053–057 — Gather lifecycle and Beacon domains

- host-aware Beacon domains and `krvk.nl` root redirect;
- public agendas, favorites, reminders, day-of search and walk-ins;
- closeout and preserved history;
- consent-gated outcome proposals.

See `docs/builds/BUILD_053_057_GATHER_LIFECYCLE.md`.

### Builds 058–062 — Stabilization and completion

- reminder cancellation and unsubscribe;
- idempotent approved-outcome application ledger;
- Owner/Admin Beacon domain administration;
- editable, pausable, archivable Beacon links with revision history;
- secure-context QR camera scanning with manual fallback.

See `docs/builds/BUILD_058_062_STABILIZATION_BEACON_COMPLETION.md`.

## Current migrations

Production deployment must apply every file in `database/migrations`, including:

```text
037_platform_mail_operations.sql
038_042_gather_lifecycle_beacon_domains.sql
043_047_lifecycle_stabilization_beacon_management.sql
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

## Beacon domain rules

- `krvk.nl` is the default verified platform short-link domain.
- `https://krvk.nl/` permanently redirects to `https://koravik.com/`.
- `https://krvk.nl/{slug}` resolves through Beacon.
- Beacon records retain stable UUID identity independent of hostname.
- Custom hostnames require verification before activation.
- Domain suspension or disconnection never deletes the underlying Beacon item.
- Deployment must route `koravik.com`, `krvk.nl`, and future verified hosts to the application with TLS and the incoming Host header preserved.
- Application verification does not automatically provision DNS or certificates.

## Current completion routes

- `GET /gather/reminders/unsubscribe/{token}`
- `POST /gather/outcomes/{id}/apply`
- `GET /gather/events/{id}/scan`
- `GET /beacon/manage`
- `POST /beacon/domains`
- `POST /beacon/domains/{id}/verify`
- `POST /beacon/links/{id}`
- `POST /beacon/links/{id}/{active|paused|archived}`

## Explicit current boundaries

- Beacon does not own events, RSVP, signup, attendance, schedules, or event lifecycle.
- Gather does not own DNS, certificate provisioning, general redirect infrastructure, or final destination records.
- Restricted Gather events may not become public through Beacon.
- Guest registration does not create a Koravik account.
- Reminder delivery requires an explicit favorite/reminder choice and supports cancellation.
- Camera scanning requires HTTPS and browser support; manual lookup remains required.
- Outcome application records an approved handoff and stable reference. Rich destination-specific draft/review screens remain future work.
- Organization membership, organization roles, household membership, payments, and external calendar synchronization remain deferred unless separately approved.

## Validation

The single workflow at `.github/workflows/validate.yml` must lint PHP and validate migration `043_047_lifecycle_stabilization_beacon_management.sql`, reminder unsubscribe, outcome application, Beacon administration and revision history, QR camera capability detection, and the Build 062 health checkpoint.

## Next build

Build 063 should begin the Organization foundation only after Build 062 validation is green. Recommended arc: neutral Organizations and memberships, contextual roles/capabilities, organization-owned Gather/Beacon resources, branded verified domains, and organization operating surfaces. Household remains independent and optional rather than a prerequisite for participation.

## Build workflow

For every build: inspect current `main`, read affected authority, identify the player-visible outcome and visual home, implement one coherent vertical slice, validate it, update affected documentation, and land one cohesive milestone.
