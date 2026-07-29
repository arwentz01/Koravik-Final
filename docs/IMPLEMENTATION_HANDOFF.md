# Koravik-Final Implementation Handoff

**Status:** Build 052 implemented on `main`  
**Version:** 1.52  
**Baseline date:** July 29, 2026  
**Authoritative branch:** `main`

## Repository authority

This handoff applies only to `arwentz01/Koravik-Final`. Do not import code, schemas, routes, build numbers, deployment assumptions, or framework conventions from earlier Koravik repositories or unrelated projects.

## Mandatory reading order

Before implementation, read `README.md`, `docs/README.md`, `docs/FOUNDATIONAL_DECISIONS.md`, all canonical documents, affected Product and Engineering Blueprint contracts, the ADR register, and this handoff. The documentation authority order in `docs/README.md` controls conflicts. Focused ownership authority includes `docs/canonical/PLATFORM_CAPABILITY_MAP.md`.

## Architecture baseline

Koravik-Final is a PHP 8.3+ custom modular monolith using MySQL or MariaDB through PDO and an Apache-compatible shared-hosting model.

- Districts own real-life truth.
- Hearth composes but does not own source records.
- Beacon owns short links, QR definitions, public pages, link hubs, digital cards, Wi-Fi cards, and privacy-aware distribution data.
- Gather owns events, schedules, RSVP, guest parties, signup slots, volunteer shifts, potluck and requested-item commitments, waitlists, attendance, communications, and follow-up.
- A cohesive workflow may cross Beacon and Gather without exposing technical seams.
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

Beacon sharing capabilities, Gather events, guest registration, party capacity, secure RSVP management, event and signup waitlists, and planning signups. See:

- `docs/builds/BUILD_036_040_BEACON_GATHER.md`
- `docs/builds/BUILD_041_GATHER_GUEST_REGISTRATION.md`

### Builds 042–046 — Gather operational core

Authenticated SMTP, Platform Mail queue and finite worker, restricted-event access grants, guest RSVP self-service, event and signup waitlist workflows, promotion offers, and signup rules. See `docs/builds/BUILD_042_046_GATHER_OPERATIONAL_CORE.md`.

### Builds 047–051 — Gather event operations

Organizer command center, event settings, attendance summaries, party-aware check-in and correction history, durable announcements, targeted audiences, and event-linked mail delivery visibility. The original fast implementation was reconciled through a dedicated stabilization pass. See:

- `docs/builds/BUILD_047_051_GATHER_EVENT_OPERATIONS.md`
- `docs/builds/BUILD_051_STABILIZATION.md`

### Build 052 — Platform Mail operations

Owner/Admin mail operations home, queue-health metrics, delivery details, retry, cancellation, resend lineage, test delivery, stale-processing recovery, and redacted diagnostics. See `docs/builds/BUILD_052_PLATFORM_MAIL_OPERATIONS.md`.

## Current product loop

`Create a Gather event → Gather provisions Beacon sharing tools → guests register or join a waitlist → participants claim shifts, potluck needs, items, and tasks → organizers configure access and capacity → organizers communicate with bounded audiences → staff check in attending parties → Platform Mail provides authorized delivery operations → approved outcomes may later connect to Quests, Chronicle, Journey, or Worlds through their existing consent and ownership boundaries.`

## Current migrations

Production deployment must apply every file in `database/migrations`, including:

```text
025_beacon_gather_capabilities.sql
026_gather_guest_registration_capacity.sql
027_031_gather_delivery_access_management_waitlists_rules.sql
032_036_gather_operational_ui_checkin_communications.sql
037_platform_mail_operations.sql
```

Run:

```bash
php tools/migrate.php
```

## Current Gather routes

- `GET /gather`
- `POST /gather/events`
- `GET /gather/events/{id}`
- `GET /gather/events/{id}/command`
- `POST /gather/events/{id}/settings`
- `POST /gather/events/{id}/guest-rsvp`
- `POST /gather/events/{id}/slots`
- `POST /gather/events/{id}/check-in`
- `POST /gather/events/{id}/check-in/correct`
- `POST /gather/events/{id}/announce`
- `POST /gather/rsvp/lookup`
- `GET|POST /gather/rsvp/manage/{token}`
- `POST /gather/slots/{id}/claim`

## Platform Mail operations

Configure authenticated SMTP with environment variables documented in `docs/builds/BUILD_042_046_GATHER_OPERATIONAL_CORE.md`. Run the finite queue worker through cron, for example:

```bash
php tools/mail-worker.php 20
```

Owner and Admin accounts may open `/system/mail` to inspect queue health, queue a test delivery, recover stale processing claims, and perform bounded retry, cancellation, or resend operations. A queued message is not represented as sent until the SMTP adapter succeeds and the delivery record is updated.

## Explicit current boundaries

- Beacon does not own events, RSVP, signup, attendance, schedules, or event lifecycle.
- Gather does not own general redirects, QR definitions, digital business cards, Wi-Fi cards, or link hubs.
- Restricted Gather events may not become public through Beacon.
- Guest registration does not create a Koravik account.
- Management-token hashes are stored; raw tokens are not persisted.
- Organizer communications are bounded by event ownership and explicit audience selection.
- Check-in correction preserves operational provenance instead of deleting evidence.
- Platform Mail cancellation and resend preserve delivery history and lineage.
- Platform Mail operations are restricted to authenticated Owner and Admin roles.
- Dedicated attendee search, walk-ins, kiosk mode, and camera-based QR scanning remain Build 054 work.
- Public event redesign, schedules, agenda favorites, reminders, and closeout remain later vertical slices.
- Household, Organization, payments, external calendar synchronization, and full collaborative authorization remain deferred unless separately approved.
- Companion does not autonomously monitor behavior or directly mutate Quest or Chronicle source records.
- Worlds receive only approved minimized facts and never mutate District truth.

## Validation

The repository's consolidated validation workflow must lint PHP, verify migration inventory, and validate the current health checkpoint. Build 052 additionally requires migration `037_platform_mail_operations.sql`, the authorized `/system/mail` routes, CSRF-protected mutations, redacted diagnostics, resend lineage, cancellation provenance, and stale-claim recovery.

## Next build

Build 053 should deliver the next coherent Gather experience slice without entering the Build 054 day-of tooling boundary. Recommended focus: a clearer public event page with schedule/agenda presentation and participant-facing favorites or reminders only where the governing documents support them.

## Build workflow

For every build: inspect current `main`, read affected authority, identify the player-visible outcome and visual home, implement one coherent vertical slice, validate it, update affected documentation, and land one cohesive milestone.

## Change control

When implementation reveals a blueprint flaw, stop at the affected boundary and resolve it through an explicit ADR or deliberate document revision before continuing.
