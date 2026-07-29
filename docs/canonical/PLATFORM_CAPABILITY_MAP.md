# Koravik Platform Capability Map

**Status:** Approved
**Version:** 1.0
**Baseline:** Build 040

## Purpose

This map identifies the single authoritative owner of each capability and the approved ways other Koravik areas may consume it. It prevents shadow ownership while allowing the product experience to blur technical boundaries for the person using it.

## Governing rules

1. Every canonical record has one primary owner.
2. Other modules consume records through documented services, references, or Platform Events.
3. A cohesive workflow may cross several owners without exposing those seams in the interface.
4. Cross-domain references never become shadow copies of the source record.
5. Consequential commitments require explicit consent.
6. Public presentation is explicit and never upgrades private source visibility.
7. Only the minimum approved fact crosses a boundary.

## Capability ownership

| Capability | Primary owner | Approved consumers and boundaries |
|---|---|---|
| Account identity | Platform Identity | All modules reference the account identifier; none replace identity authority. |
| Authentication and sessions | Platform Authentication | Shared entry and security service. |
| Authorization | Platform Authorization plus contextual module rules | Roles are bundles; resource ownership and consent remain contextual. |
| Consent grants | Platform Consent | Companion, Worlds, Journey, Beacon, Gather, and other modules enforce scoped approval. |
| Notifications | Platform Notifications | Districts request delivery; they do not duplicate channels, quiet hours, retries, or preferences. |
| Search | Platform Search | Indexes approved records without taking lifecycle ownership. |
| Audit | Platform Audit | Receives minimized meaningful actions. |
| Media assets | Platform Media | Districts attach owned media references with visibility constraints. |
| Hearth composition | Hearth | References source data; does not own District records. |
| Chronicle entries and reflections | Chronicle | May reference events, Quests, and approved World moments without replacing them. |
| Quests and real-life commitments | Quests | Gather, Health, Companion, and Worlds may propose; only Quests owns an accepted commitment. |
| Beacon short links | Beacon | Gather and other modules request provisioned links. Beacon owns redirect lifecycle and privacy-aware engagement counts. |
| Beacon QR definitions | Beacon | May encode Gather event, RSVP, signup, check-in, profile, contact, Wi-Fi, or external destinations. |
| Beacon pages and blocks | Beacon | May present Gather events, organization links, personal profiles, digital cards, Wi-Fi cards, and link hubs. |
| Digital business cards | Beacon | Accounts and Organizations may request cards; Beacon owns presentation. |
| Wi-Fi sharing cards | Beacon | Stores explicit shareable connection presentation; sensitive details require warning. |
| Public link hubs and landing pages | Beacon | Source modules retain their records; Beacon renders approved references. |
| Gather events | Gather | Beacon presents and distributes; Journey and Quests may receive consent-gated proposals. |
| Event schedules | Gather | Beacon may link to a schedule view; Calendar may compose read-only presentation later. |
| RSVPs | Gather | Beacon can route people into RSVP flows but never owns attendance intent. |
| Signup slots | Gather | Includes shifts, potluck needs, requested items, and planning tasks. |
| Signup commitments | Gather | Quests may reference a commitment only after explicit consent. |
| Attendance and check-in | Gather | Beacon QR may initiate check-in; Gather records attendance truth. |
| Event announcements and follow-up | Gather | Notifications may deliver; Chronicle creation remains user-controlled. |
| Journey orientation and Healing Home | Journey | Connects experiences and acknowledges approved outcomes without taking source ownership. |
| Companion proposals and memory | Companion | Approval-bound; owning modules execute accepted actions. |
| World definitions and World State | Worlds | May interpret approved minimized Platform Events; never writes District truth. |
| Households | Households | Optional context; no requirement for full Koravik participation. |
| Organizations | Organizations | Optional context; Gather owns events and Beacon owns public presentation. |

## Beacon and Gather integration contract

Creating a Gather event may provision, in the same user workflow:

- a Beacon short link;
- a Beacon event landing page;
- an event QR definition;
- later, RSVP, signup, check-in, schedule, and organizer-contact QR destinations.

The interface may present this as one event-creation action. Persistence ownership remains distinct:

- Gather owns event details, RSVP, signup, attendance, and planning state;
- Beacon owns short-link resolution, QR definitions, public-page presentation, and privacy-aware engagement data.

A private Gather event may only provision private Beacon presentation. Unlisted and public presentation must remain explicit and revocable.

## Journey integration contract

Gather and Beacon may propose a Quest or Chronicle action. They do not silently create one.

Examples:

- prepare supplies for a Gather event;
- attend or volunteer at an event;
- follow up with attendees;
- remember an event in Chronicle;
- reflect on a meaningful contribution.

The source record remains with Gather or Beacon. Accepted real-life commitments belong to Quests. Saved personal memories belong to Chronicle.

## Prohibited ownership drift

- Beacon must not own RSVP, signup, attendance, event schedules, or event lifecycle.
- Gather must not own redirect infrastructure, QR definitions, digital business cards, Wi-Fi cards, or general link hubs.
- Journey must not become an event database or URL shortener.
- Hearth must not duplicate full Beacon or Gather management screens.
- Worlds must not create or mutate real-life events or commitments.
- Cross-domain integration must not expose private event data through a public Beacon page.
