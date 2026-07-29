# Build 051 Stabilization — Gather Operational Integrity

**Status:** Implemented on `main`  
**Checkpoint:** Build 051 remains the deployed health identifier; Build 052 has not started.

## Purpose

This checkpoint reconciles the fast connector-written Builds 047–051 against the actual routes, schema, services, and organizer experience. It does not claim deferred Build 052 mail-console work.

## Verified operational loop

- `/gather` links each owned event to its organizer command center.
- `/gather/events/{id}/command` is account-owned and displays attendance, waitlist, signup, check-in, communication, and event-linked mail state.
- event settings preserve the current visibility and normalize disabled additional-guest limits to zero.
- an RSVP can be checked in once, updated by re-check-in, voided with a required correction note, and checked in again later.
- announcement history is visible to the organizer.
- confirmed, waitlisted, all-RSVP, volunteer, and individual-slot audiences are explicit.
- volunteer and slot delivery reads direct signup identities as well as account-backed identities; it does not require a commitment to be linked to an RSVP.
- queued announcement mail remains event-linked through Platform Mail.

## Corrections made

1. The command-center visibility selector now preserves the stored value.
2. Check-in correction had a route but no organizer-facing action; the command center now exposes it.
3. The attendee table now shows active check-in state instead of always rendering a new check-in form.
4. Announcement history is now returned and rendered.
5. Slot-targeted communication now has a selectable visual control and rejects a missing slot reference.
6. Volunteer and slot recipient queries now use signup commitments directly, preventing direct signups from being silently omitted.
7. Build 051 CI now validates these integrations instead of checking only broad keywords.

## Explicitly deferred

The following remain future vertical slices and must not be described as complete:

- mail resend, cancel, test-send, queue recovery, and delivery-detail tools;
- dedicated rapid attendee search, walk-in registration, kiosk mode, and camera QR scanning;
- public event-page visual redesign;
- schedules, agenda favorites, reminders, and event closeout.

## Deployment

Apply all migrations with:

```bash
php tools/migrate.php
```

Run the finite mail worker through cron:

```bash
php tools/mail-worker.php 20
```

A successful Build 051 Stabilization workflow is required before this checkpoint is treated as release-ready.
