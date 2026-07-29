# Koravik-Final Implementation Handoff

**Status:** Builds 053–057 implemented on `main`  
**Version:** 1.57  
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
- Gather owns events, agendas, RSVP, guest parties, signup slots, volunteer shifts, potluck and requested-item commitments, waitlists, attendance, reminders, communications, closeout, and event-outcome proposals.
- Worlds interpret approved minimized facts into independent fictional World State.
- Companion owns proposals and approved Companion memory, not destination records.
- Chronicle owns saved personal and approved reflections.
- Search and Notifications are non-owning utilities.
- Platform events describe committed facts and use a transactional outbox.
- Authorization is capability-based and contextual.
- Background work is finite, idempotent, lock-safe, and cron compatible.

## Completed implementation arc

### Builds 001–035

Platform identity, Hearth, Quests, Chronicle, Companion, Notifications, Search, Privacy and Audit, account-data lifecycle, authentication recovery, unified visual shell, Epic Ordinary, World lifecycle, first-use orientation, Living Quests, Healing Home, relationships, and Journey invitation experiences.

### Builds 036–041 — Beacon and Gather foundation

Beacon sharing capabilities, Gather events, guest registration, party capacity, secure RSVP management, event and signup waitlists, and planning signups.

### Builds 042–046 — Gather operational core

Authenticated SMTP, Platform Mail queue and finite worker, restricted-event access grants, guest RSVP self-service, event and signup waitlist workflows, promotion offers, and signup rules.

### Builds 047–051 — Gather event operations

Organizer command center, event settings, attendance summaries, party-aware check-in and correction history, durable announcements, targeted audiences, and event-linked mail delivery visibility.

### Build 052 — Platform Mail operations

Owner/Admin mail operations home, queue-health metrics, delivery details, retry, cancellation, resend lineage, test delivery, stale-processing recovery, and redacted diagnostics.

### Builds 053–057 — Gather lifecycle and Beacon domains

- Build 053: host-aware Beacon domains, `krvk.nl` default links, root redirect to `koravik.com`, public agenda presentation, and domain-neutral link identity.
- Build 054: attendee search, walk-in registration, and mobile-oriented day-of operations.
- Build 055: personal agenda favorites, explicit reminders, and a finite reminder worker.
- Build 056: completed/cancelled/archived event closeout with preserved operational history.
- Build 057: consent-gated outcome proposals for Chronicle, Quests, Journey, and minimized World facts.

See `docs/builds/BUILD_053_057_GATHER_LIFECYCLE.md`.

## Current migrations

Production deployment must apply every file in `database/migrations`, including:

```text
025_beacon_gather_capabilities.sql
026_gather_guest_registration_capacity.sql
027_031_gather_delivery_access_management_waitlists_rules.sql
032_036_gather_operational_ui_checkin_communications.sql
037_platform_mail_operations.sql
038_042_gather_lifecycle_beacon_domains.sql
```

Run:

```bash
php tools/migrate.php
```

## Background workers

Platform Mail:

```bash
php tools/mail-worker.php 20
```

Gather agenda reminders:

```bash
php tools/gather-reminder-worker.php 100
```

Both workers are finite and cron compatible.

## Beacon domain rules

- `krvk.nl` is the default verified platform short-link domain.
- `https://krvk.nl/` permanently redirects to `https://koravik.com/`.
- `https://krvk.nl/{slug}` resolves through Beacon.
- Beacon records retain stable UUID identity independent of hostname.
- Future organization domains must be verified before activation.
- Removing or suspending a custom domain must not destroy the underlying Beacon record or its platform fallback.
- Deployment must route both `koravik.com` and `krvk.nl` to the application while preserving the incoming `Host` header and TLS.

## Current Gather lifecycle routes

- `GET|POST /gather/events/{id}/agenda`
- `POST /gather/agenda/{id}/favorite`
- `GET /gather/events/{id}/day-of`
- `POST /gather/events/{id}/walk-ins`
- `GET|POST /gather/events/{id}/closeout`
- `GET /gather/events/{id}/reflect`
- `POST /gather/events/{id}/outcomes`
- `GET /gather/outcomes/{id}/review`
- `POST /gather/outcomes/{id}/approve`

## Explicit current boundaries

- Beacon does not own events, RSVP, signup, attendance, schedules, or event lifecycle.
- Gather does not own domain verification, general redirects, QR definitions, digital business cards, Wi-Fi cards, or link hubs.
- Restricted Gather events may not become public through Beacon.
- Guest registration does not create a Koravik account.
- Reminder delivery requires an explicit favorite/reminder choice.
- Camera QR capture is not considered complete merely because a route or QR record exists; manual lookup remains the implemented fallback.
- Event closeout never deletes RSVP, attendance, signup, announcement, or mail history.
- Outcome approval does not directly mutate Chronicle, Quest, Journey, Companion, or World records; destination application remains a separately authorized workflow.
- Organization-domain self-service verification and certificate automation remain future work.
- Household, Organization, payments, and external calendar synchronization remain deferred unless separately approved.

## Validation

The single consolidated workflow at `.github/workflows/validate.yml` must lint PHP and validate:

- migration `038_042_gather_lifecycle_beacon_domains.sql`;
- `krvk.nl` and `koravik.com` root redirect configuration;
- host-aware Beacon resolution;
- agenda, day-of, walk-in, reminder, closeout, and outcome routes;
- the finite reminder worker;
- the Build 057 health checkpoint.

## Next build

Build 058 should stabilize and complete the new lifecycle arc before expanding scope. Recommended priorities are end-to-end migration testing on MySQL/MariaDB, owner authorization review, reminder cancellation/unsubscribe, QR camera implementation only if HTTPS/browser support is validated, and destination adapters that apply approved outcome proposals without crossing ownership boundaries.

## Build workflow

For every build: inspect current `main`, read affected authority, identify the player-visible outcome and visual home, implement one coherent vertical slice, validate it, update affected documentation, and land one cohesive milestone.