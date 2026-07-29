# Koravik-Final Implementation Handoff

**Status:** Build 041 implemented on `main`  
**Version:** 1.41  
**Baseline date:** July 28, 2026  
**Authoritative branch:** `main`

## Repository authority

This handoff applies only to `arwentz01/Koravik-Final`. Do not import code, schemas, routes, build numbers, deployment assumptions, or framework conventions from earlier Koravik repositories or unrelated projects.

## Mandatory reading order

Before implementation, read `README.md`, `docs/README.md`, `docs/FOUNDATIONAL_DECISIONS.md`, all canonical documents, Project Zero, affected Product and Engineering Blueprint contracts, the ADR register, and this handoff. The documentation authority order in `docs/README.md` controls conflicts.

Focused ownership authority includes `docs/canonical/PLATFORM_CAPABILITY_MAP.md`.

## Architecture baseline

Koravik-Final is a PHP 8.3+ custom modular monolith using MySQL or MariaDB through PDO and an Apache-compatible shared-hosting model.

- Districts own real-life truth.
- Hearth composes but does not own source records.
- Healing Home and Journey orient, acknowledge, and connect experiences without taking ownership from Quests, Chronicle, Worlds, Companion, Beacon, or Gather.
- Beacon owns short links, QR definitions, public pages, link hubs, digital cards, Wi-Fi cards, and privacy-aware distribution data.
- Gather owns events, schedules, RSVP, guest parties, signup slots, volunteer shifts, potluck and requested-item commitments, waitlists, attendance, and follow-up.
- A cohesive workflow may cross Beacon and Gather without exposing technical seams.
- Worlds interpret approved minimized facts into independent fictional World State.
- Companion owns proposals and approved Companion memory, not destination records.
- Chronicle owns saved personal and approved reflections.
- Search and Notifications are non-owning utilities.
- Platform events describe committed facts and use a transactional outbox.
- Authorization is capability-based and contextual.
- Background work is finite, idempotent, lock-safe, and cron compatible.

## Completed implementation arc

### Builds 001–025 — Platform, Companion, Chronicle, security, visual system, and Worlds

Secure identity, Hearth, Quests, Chronicle, Companion consent and execution, Notifications, Search, Privacy and Audit, account-data lifecycle, authentication recovery, unified visual shell, Epic Ordinary, World progress and reactions, and installed World lifecycle.

### Builds 026–030 — First use, Living Quests, Healing Home, reflection, and relationships

Secure registration and orientation, Quest purpose and provenance, non-binary outcomes, Healing Home rooms and atmosphere, keepsakes, Caretaker relationship state, shared memories, and readable relationship history.

### Builds 031–035 — Journey invitation arc

Story invitations, accept/decline/snooze consent, Garden and Library presentation, Caretaker conversations, consent-gated Beacon and Gather source proposals, and cooperative invitation foundations.

### Builds 036–040 — Beacon and Gather foundation

Beacon short links, public pages, cards, Wi-Fi sharing, QR definitions, Gather events, RSVPs, planning signups, Beacon × Gather provisioning, Journey proposal boundaries, and the canonical capability map.

See `docs/builds/BUILD_036_040_BEACON_GATHER.md`.

### Build 041 — Gather guest registration and capacity

Unlisted and public events support registration without a Koravik account using name and email. Registrations represent whole parties, additional guests may be disabled or limited, total capacity counts every attendee, event and signup waitlists are independent, and shifts or food items may allow configurable multiple commitments. Secure RSVP-management tokens are stored hashed, and neutral email lookup queues provider-neutral management-link deliveries.

See `docs/builds/BUILD_041_GATHER_GUEST_REGISTRATION.md`.

## Current product loop

`Create a Gather event → Gather provisions Beacon sharing tools → guests register or join a waitlist → participants claim shifts, potluck needs, items, and tasks under organizer-defined limits → Beacon and Gather may propose meaningful action → explicit acceptance creates a Quest → reflection and Journey experiences may acknowledge approved outcomes.`

## Current database migrations

Production deployment must apply all migrations in `database/migrations`, including:

```text
022_living_quests_healing_home
023_reflection_relationships
024_journey_arc_invitations_rooms_conversations
025_beacon_gather_capabilities
026_gather_guest_registration_capacity
```

Run:

```bash
php tools/migrate.php
```

## Current Beacon and Gather routes

- `/beacon`
- `/beacon/links`
- `/beacon/pages`
- `/b/{slug}`
- `/p/{page-key}`
- `/gather`
- `/gather/events`
- `/gather/events/{id}`
- `/gather/events/{id}/guest-rsvp`
- `/gather/events/{id}/slots`
- `/gather/rsvp/lookup`
- `/gather/rsvp/manage/{token}`
- `/gather/slots/{id}/claim`

## Explicit current boundaries

- Beacon does not own events, RSVP, signup, attendance, schedules, or event lifecycle.
- Gather does not own general redirects, QR definitions, digital business cards, Wi-Fi cards, or link hubs.
- Restricted Gather events may not become public through Beacon.
- QR image rendering and downloadable printable assets remain a renderer/adaptor follow-up; canonical QR definitions are implemented.
- Guest registration does not create a Koravik account.
- Management-token hashes are stored; raw tokens are not persisted.
- Actual SMTP/provider delivery remains deferred to the delivery adapter. Queueing a management-link delivery is not represented as a successfully sent email.
- Restricted-event eligibility for friends, invitations, Households, and Organizations remains deferred.
- Automatic event and signup waitlist promotion remains deferred.
- Overlapping-shift policy is persisted, but full conflict evaluation remains deferred.
- Companion does not autonomously monitor behavior or scan private records.
- Companion does not directly own or mutate Quest or Chronicle source records.
- Approval and execution remain separate actions.
- Household, Organization, messaging, payments, external calendar synchronization, and full collaborative authorization remain deferred.
- Accepted commitments belong to Quests; source records remain with Beacon or Gather.
- Chronicle memory creation remains user-controlled.
- Worlds receive only approved minimized facts and never mutate District truth.

## Validation

Build 041 adds `.github/workflows/build-041.yml`, which must lint PHP and validate migration, token hashing, visibility, party capacity, waitlist, signup-limit, routing, and ownership boundaries. A successful GitHub Actions run must be confirmed before treating this checkpoint as release-ready.

## Build workflow

For every build: inspect current `main`, read affected authority, identify the player-visible outcome and visual home, implement one coherent vertical slice, validate it, update affected documentation, and land one cohesive milestone.

## Change control

When implementation reveals a blueprint flaw, stop at the affected boundary and resolve it through an explicit ADR or deliberate document revision before continuing.