# Builds 047–051 — Gather Event Operations

## Build 047 — Gather Operational UI

Implemented an organizer command center linked from Gather event cards and organizer event views. Organizers can manage visibility, guest registration, additional-guest limits, capacity, waitlists, automatic promotion, event signup limits, offer timing, and organizer Reply-To settings.

## Build 048 — Email Delivery Visibility

Extended Platform Mail records with optional Gather event association and exposed recent event-related delivery state in the organizer command center. Messages remain provider-neutral and are still delivered by the finite mail worker.

## Build 049 — Organizer Command Center

Added a single event operations home showing confirmed party count, waitlisted party count, checked-in count, unfilled signup units, attendees, event settings, communication tools, and recent email delivery state.

## Build 050 — Check-In and Day-of Operations

Added party-aware RSVP check-in, duplicate prevention through event/RSVP uniqueness, updated check-in replacement, and correction provenance fields. The organizer command center provides a mobile-compatible check-in action for every RSVP.

## Build 051 — Targeted Event Communication

Added durable event announcements with normal or urgent status and audiences for everyone, confirmed attendees, waitlisted guests, volunteers, or a specific signup slot. Email-enabled announcements queue one Platform Mail delivery per distinct eligible recipient and preserve organizer Reply-To behavior.

## New routes

- `GET /gather/events/{id}/command`
- `POST /gather/events/{id}/settings`
- `POST /gather/events/{id}/check-in`
- `POST /gather/events/{id}/check-in/correct`
- `POST /gather/events/{id}/announce`

## Ownership boundaries

- Gather owns event operations, RSVP state, check-ins, and announcements.
- Platform Mail owns transport, retries, and delivery state.
- Beacon may provide QR entry points but does not own attendance truth.
- Notifications may later mirror operational announcements for Koravik accounts without taking ownership of the announcement.

## Deployment

1. Apply migration `032_036_gather_operational_ui_checkin_communications.sql`.
2. Confirm production SMTP variables and mail-worker cron.
3. Open an event command center and verify organizer-only access.
4. Queue a targeted announcement and run `php tools/mail-worker.php 20`.
5. Test full-party and partial-party check-in.
6. Confirm GitHub Actions before release.
