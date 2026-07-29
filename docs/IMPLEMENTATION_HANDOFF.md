# Koravik-Final Implementation Handoff

**Status:** Build 040 implemented on `main`  
**Version:** 1.40  
**Baseline date:** July 28, 2026  
**Authoritative branch:** `main`

## Repository authority

This handoff applies only to `arwentz01/Koravik-Final`. Do not import code, schemas, routes, build numbers, deployment assumptions, or framework conventions from earlier Koravik repositories or unrelated projects.

## Mandatory reading order

Before implementation, read `README.md`, `docs/README.md`, `docs/FOUNDATIONAL_DECISIONS.md`, all canonical documents, Project Zero, affected Product and Engineering Blueprint contracts, the ADR register, and this handoff. The documentation authority order in `docs/README.md` controls conflicts.

Focused ownership authority now includes `docs/canonical/PLATFORM_CAPABILITY_MAP.md`.

## Architecture baseline

Koravik-Final is a PHP 8.3+ custom modular monolith using MySQL or MariaDB through PDO and an Apache-compatible shared-hosting model.

- Districts own real-life truth.
- Hearth composes but does not own source records.
- Healing Home and Journey orient, acknowledge, and connect experiences without taking ownership from Quests, Chronicle, Worlds, Companion, Beacon, or Gather.
- Beacon owns short links, QR definitions, public pages, link hubs, digital cards, Wi-Fi cards, and privacy-aware distribution data.
- Gather owns events, schedules, RSVP, signup slots, volunteer shifts, potluck and requested-item commitments, attendance, and follow-up.
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

### Build 036 — Beacon Core

Account-owned short links, redirect resolution, visit counts, link hubs, business cards, Wi-Fi cards, event landing pages, QR definitions, `/beacon`, `/b/{slug}`, and `/p/{page-key}`.

### Build 037 — Gather Event Engine

Account-owned events with description, venue, schedule, timezone, visibility, lifecycle, capacity, event list, and planning view.

### Build 038 — Gather Planning Toolkit

RSVPs, volunteer shifts, potluck needs, requested items, planning tasks, quantity limits, and signup commitments.

### Build 039 — Beacon × Gather integration

Creating a Gather event provisions a Beacon short link, event landing page, and QR definition while preserving separate ownership.

### Build 040 — Journey integration and capability map

The existing consent-gated proposal contract remains the path from Beacon or Gather into Quests. `docs/canonical/PLATFORM_CAPABILITY_MAP.md` now defines one owner per capability and the approved cross-domain boundaries.

See `docs/builds/BUILD_036_040_BEACON_GATHER.md`.

## Current product loop

`Create a Gather event → Gather provisions Beacon sharing tools → people RSVP or claim shifts, potluck needs, items, and tasks → Beacon and Gather may propose meaningful action → explicit acceptance creates a Quest → reflection and Journey experiences may acknowledge approved outcomes.`

## Current database migrations

Production deployment must apply all migrations in `database/migrations`, including:

```text
022_living_quests_healing_home
023_reflection_relationships
024_journey_arc_invitations_rooms_conversations
025_beacon_gather_capabilities
```

Run:

```bash
php tools/migrate.php
```

## Current routes added by Builds 036–040

- `/beacon`
- `/beacon/links`
- `/beacon/pages`
- `/b/{slug}`
- `/p/{page-key}`
- `/gather`
- `/gather/events`
- `/gather/events/{id}`
- `/gather/events/{id}/rsvp`
- `/gather/events/{id}/slots`
- `/gather/slots/{id}/claim`

## Explicit current boundaries

- Beacon does not own events, RSVP, signup, attendance, schedules, or event lifecycle.
- Gather does not own general redirects, QR definitions, digital business cards, Wi-Fi cards, or link hubs.
- Private Gather events may not become public through Beacon.
- QR image rendering and downloadable printable assets remain a renderer/adaptor follow-up; canonical QR definitions are implemented.
- Public anonymous RSVP and signup tokens are not implemented in this slice; current participation requires authentication.
- Companion does not autonomously monitor behavior or scan private records.
- Companion does not directly own or mutate Quest or Chronicle source records.
- Approval and execution remain separate actions.
- Household, Organization, messaging, email sending, payments, external calendar synchronization, and full collaborative authorization remain deferred.
- Accepted commitments belong to Quests; source records remain with Beacon or Gather.
- Chronicle memory creation remains user-controlled.
- Worlds receive only approved minimized facts and never mutate District truth.

## Validation

Build 040 adds `.github/workflows/build-040.yml`, which must lint PHP and validate migration, capability-map, ownership, routing, and consent boundaries. A successful GitHub Actions run must be confirmed before treating this checkpoint as release-ready.

## Build workflow

For every build: inspect current `main`, read affected authority, identify the player-visible outcome and visual home, implement one coherent vertical slice, validate it, update affected documentation, and land one cohesive milestone.

## Change control

When implementation reveals a blueprint flaw, stop at the affected boundary and resolve it through an explicit ADR or deliberate document revision before continuing.
