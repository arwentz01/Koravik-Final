# Build 041 — Gather Guest Registration and Capacity

**Status:** Implemented on `main`  
**Date:** July 28, 2026

## Player-visible outcome

Gather events now support registration without a Koravik account for unlisted and public events. The default event type is **Anyone with the link** (`unlisted`). Public events are discoverable-capable. Restricted events remain available only through future account, friendship, Household, Organization, or invitation authorization contracts.

## Event registration

A guest provides a name and email address. A registration represents a party:

- the primary guest counts as one attendee;
- organizers may disable additional guests;
- organizers may set a maximum number of additional guests per RSVP;
- event capacity counts every member of the party;
- named additional-guest records are supported;
- guest registration never silently creates a Koravik account.

## RSVP management

Guest RSVPs receive a cryptographically random management token. Only the SHA-256 hash is stored on the RSVP. A neutral email lookup flow prevents registration enumeration and queues a new management-link delivery without revealing whether an RSVP exists.

`gather_management_link_deliveries` is the durable provider-neutral delivery queue. SMTP/provider execution remains an adapter concern; the application does not claim an email was sent until a delivery worker marks it sent.

## Event waitlist

When confirmed party size would exceed event capacity:

- the whole party joins the event waitlist when enabled;
- waitlist position is durable;
- organizers may enable future automatic promotion;
- promotion must respect requested party size.

## Signup rules

Every shift, potluck item, needed item, or task may independently define:

- whether multiple commitments are allowed;
- an optional maximum per participant;
- whether a signup waitlist is enabled;
- whether overlapping volunteer shifts are allowed.

A full signup may waitlist the participant instead of failing. Event attendance limits and planning-signup limits remain separate.

## Ownership and privacy

Gather owns events, participants, RSVPs, party members, signup slots, commitments, and waitlists. Beacon owns the short link, landing page, and QR definition. Email addresses are used only for event participation and management-link delivery and must not become public page content.

## Migration

`026_gather_guest_registration_capacity.sql`

## Routes

- `GET /gather/events/{event-id}` — authenticated owner view or public/unlisted participant view
- `POST /gather/events/{event-id}/guest-rsvp`
- `POST /gather/rsvp/lookup`
- `GET /gather/rsvp/manage/{token}`
- `POST /gather/rsvp/manage/{token}`
- `POST /gather/slots/{slot-id}/claim`

## Deferred boundaries

- actual SMTP/provider delivery worker
- restricted-event eligibility evaluation for friends, Organizations, Households, and invitation allowlists
- automatic event and signup waitlist promotion worker
- overlapping-shift conflict evaluation
- guest claiming of historical RSVPs after account creation
