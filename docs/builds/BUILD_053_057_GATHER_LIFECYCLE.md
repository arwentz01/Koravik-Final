# Builds 053–057 — Gather Lifecycle and Beacon Domains

**Status:** Implemented on `main`  
**Baseline:** Build 052  
**Health checkpoint:** 057

## Build 053 — Public event experience and Beacon domains

- Adds `beacon_domains` as the hostname authority for Beacon delivery surfaces.
- Seeds `krvk.nl` as the verified default platform short-link domain.
- Defines `https://krvk.nl/` as a permanent redirect to `https://koravik.com/`.
- Resolves `https://krvk.nl/{slug}` through Beacon without making the hostname part of the link's stable identity.
- Makes short links and public pages domain-aware through `domain_id`.
- Preserves a future path for verified organization and personal domains.
- Adds public event agenda presentation as a dedicated experience at `/gather/events/{id}/agenda`.

## Build 054 — Day-of operations

- Adds attendee search bounded to the event owner.
- Adds walk-in registration and immediate check-in provenance.
- Adds a mobile-oriented day-of home at `/gather/events/{id}/day-of`.
- Keeps manual lookup as the fallback for QR workflows; camera capture requires HTTPS and compatible browser support.

## Build 055 — Personal agenda and reminders

- Adds agenda favorites for account holders and email-identified guests.
- Supports explicit reminder choices of none, 15 minutes, one hour, or one day.
- Adds a finite cron-compatible reminder worker:

```bash
php tools/gather-reminder-worker.php 100
```

- Reminder execution queues Platform Mail deliveries and retains delivery linkage.

## Build 056 — Event closeout

- Adds completed, cancelled, and archived lifecycle states.
- Adds a visual closeout home at `/gather/events/{id}/closeout`.
- Shows confirmed attendance, checked-in parties, and walk-in parties.
- Preserves event, RSVP, attendance, signup, announcement, and delivery history.

## Build 057 — Approved event outcomes

- Adds reviewable outcome proposals for Chronicle reflection, Quest progress, Journey invitation, and minimized World facts.
- Requires a separate consent-review step before approval.
- Stores a minimized payload preview and does not directly mutate destination records.
- Keeps destination application as a subsequent bounded workflow rather than pretending approval already changed Chronicle, Quests, Journey, or World State.

## Migration

Apply:

```text
database/migrations/038_042_gather_lifecycle_beacon_domains.sql
```

Then run:

```bash
php tools/migrate.php
```

## Domain deployment requirement

Both `koravik.com` and `krvk.nl` must point to the same deployed application entry point, or an equivalent front proxy must forward the host header. TLS must be configured for each hostname. The application uses the incoming `Host` header to distinguish the main app from Beacon delivery domains.

Organization domains remain a prepared architecture boundary rather than a self-service product in these builds. Future onboarding must verify DNS ownership before changing `verification_status` to `verified`.

## Boundaries

- Gather owns events, agendas, attendance, favorites, reminders, closeout, and outcome proposals.
- Beacon owns hostnames, slugs, redirects, public delivery addresses, and QR destinations.
- `krvk.nl` is a delivery surface, not the identity of a Beacon record.
- Event outcome approval does not itself create or alter a Chronicle entry, Quest, Journey record, Companion memory, or World State.
- Restricted events remain governed by existing Gather access rules.