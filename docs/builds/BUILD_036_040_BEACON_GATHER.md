# Builds 036–040 — Beacon and Gather

**Status:** Implemented on `main`
**Baseline date:** July 28, 2026

## Build 036 — Beacon Core

Delivered:

- account-owned short links;
- redirect resolution and privacy-aware visit counts;
- Beacon pages for link hubs, digital business cards, Wi-Fi cards, and event landing pages;
- QR definitions that store an encoded destination without pretending that image rendering is already implemented;
- authenticated Beacon management at `/beacon`;
- public routes `/b/{slug}` and `/p/{page-key}`.

Beacon owns distribution and presentation. It does not own events or participation.

## Build 037 — Gather Event Engine

Delivered:

- Gather event creation;
- title, description, venue, start, end, timezone, visibility, capacity, and lifecycle state;
- event list and event planning screen;
- authenticated routes under `/gather` and `/gather/events/{id}`.

Gather owns event truth.

## Build 038 — Gather Planning Toolkit

Delivered reusable signup blocks for:

- RSVP responses;
- volunteer shifts;
- potluck contributions;
- requested items;
- planning tasks;
- bounded quantities and commitments.

This is the first functional slice toward a more capable SignupGenius-style experience.

## Build 039 — Beacon × Gather integration

Creating a Gather event now provisions:

- a Beacon short link;
- a Beacon event landing page;
- a Beacon QR definition;
- durable references on the Gather event.

This is experienced as one event-creation action while ownership remains separate.

## Build 040 — Journey integration boundary

The existing consent-gated source proposal contract remains the only path from Beacon or Gather into Quests. Gather and Beacon may propose preparation, attendance, volunteering, contribution, or follow-up actions; Quests owns an accepted commitment.

Chronicle and Healing Home may later acknowledge approved event outcomes, but neither receives event ownership.

## Capability Map

`docs/canonical/PLATFORM_CAPABILITY_MAP.md` is the controlling focused contract for capability ownership and Beacon × Gather integration.

## Migration

Apply:

```text
025_beacon_gather_capabilities
```

with:

```bash
php tools/migrate.php
```

## Routes

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

## Deliberate boundaries

- QR image rendering and downloadable print assets remain a renderer/adaptor follow-up; the canonical QR definition is implemented.
- Public anonymous RSVP and signup tokens are not yet implemented; the current slice requires an authenticated account.
- Full host/moderator authorization, Organizations, Households, messaging, email delivery, calendar synchronization, and payment remain deferred.
- A successful CI run is required before this checkpoint is release-ready.
