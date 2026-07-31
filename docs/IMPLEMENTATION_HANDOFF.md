# Koravik-Final Implementation Handoff

**Status:** Worlds and Epic Ordinary Polish vertical product slice in the current working tree
**Version:** 2.42
**Baseline date:** July 30, 2026
**Authoritative branch:** `main`

## Repository authority

This handoff applies only to `arwentz01/Koravik-Final`. Do not import code, schemas, routes, build numbers, deployment assumptions, or framework conventions from earlier Koravik repositories or unrelated projects.

Before implementation, read `README.md`, `docs/README.md`, `docs/FOUNDATIONAL_DECISIONS.md`, all canonical documents, affected Product and Engineering Blueprint contracts, the ADR register, and this handoff. The documentation authority order in `docs/README.md` controls conflicts.

## Architecture baseline

Koravik-Final is a PHP 8.3+ custom modular monolith using MySQL or MariaDB through PDO and an Apache-compatible shared-hosting model.

- Personal participation does not require an Organization or Household.
- Organizations are optional contextual operating spaces.
- Platform account roles and Organization membership roles are separate.
- Districts retain ownership of their domain truth.
- Hearth composes but does not absorb Organization records.
- Beacon owns domains, links, pages, and QR definitions even when an Organization owns the resource context.
- Gather owns event truth even when an Organization owns the event context.
- Authorization is capability-based and contextual.
- Background work is finite, idempotent, lock-safe, and cron compatible.

## Completed implementation arc

### Builds 001–057

Platform identity and security, Hearth, Quests, Chronicle, Companion, Notifications, Search, Privacy and Audit, account-data lifecycle, Epic Ordinary and World lifecycle, first-use orientation, Journey experiences, Beacon/Gather foundation, Platform Mail, Gather operational tooling, agendas, reminders, day-of operations, closeout, Beacon domains, and consent-gated outcomes.

### Builds 058–062 — Stabilization and Beacon completion

- reminder cancellation and unsubscribe;
- approved-outcome application ledger;
- Beacon domain administration;
- Beacon link management and revision history;
- secure-context camera QR scanning with manual fallback.

See `docs/builds/BUILD_058_062_STABILIZATION_BEACON_COMPLETION.md`.

### Builds 063–067 — Organization foundation

- Build 063: optional Organization identity and profile lifecycle;
- Build 064: contextual Owner, Admin, Creator, and Member roles with invitations and membership controls;
- Build 065: Organization-owned Gather events and contextual command-center authorization;
- Build 066: Organization-owned Beacon links with stable platform fallback;
- Build 067: Organization operating home with members, invitations, events, links, activity, and capability-aware actions.

See `docs/builds/BUILD_063_067_ORGANIZATION_FOUNDATION.md`.

### Builds 068–077 — Organization operations

- contextual Gather authorization across day-of, communication, workflow, lifecycle, and closeout;
- Platform Mail invitation delivery with resend and revoke;
- Organization settings, lifecycle, audit, and recovery;
- verified Beacon domain selection and Beacon-owned public presence;
- internal teams with non-escalating team roles;
- consent-first Organization Quest proposals;
- composed operational coordination and Build 077 stabilization.

See `docs/builds/BUILD_068_077_ORGANIZATION_OPERATIONS.md`.

### Builds 078–087 — Household foundation

- Build 077 PHP 8.3 acceptance and release checks;
- optional Household identity, preferences, and lifecycle;
- contextual Owner, Admin, and Member roles with secure invitations;
- consent-first one-time and recurring responsibility proposals;
- private Household resources;
- Household-owned private Gather events with Gather-owned truth;
- Household home composition, notifications, audit, and recovery;
- Build 087 stabilization.

See `docs/builds/BUILD_078_087_HOUSEHOLD_FOUNDATION.md`.

### Builds 088–097 — Release verification

- unified local and CI release test runner;
- isolated migration and critical-schema verification;
- authentication, CSRF, Organization, Household, and Gather authorization tests;
- live subdirectory routing and accessibility smoke checks;
- Platform Mail, lifecycle recovery, and bounded-worker checks;
- MySQL-backed continuous integration and Build 097 release gate.

See `docs/builds/BUILD_088_097_RELEASE_VERIFICATION.md`.

### Builds 098–107 — Accessibility personalization

- durable reading and interaction preferences with safe defaults;
- text scale, readable typeface, relaxed spacing, and narrow reading width;
- emphasized links and enhanced keyboard focus;
- dedicated settings, preview, reset, audit, and global visual-system integration;
- automated persistence, validation, reset, CSS-contract, and Build 107 health checks.

See `docs/builds/BUILD_098_107_ACCESSIBILITY_PERSONALIZATION.md`.

### Builds 108–117 — Workflow resilience

- accessible form-error summaries and safe value preservation;
- expiring account-owned drafts with credential-field exclusion;
- database-backed duplicate-submit protection;
- tracked, hashed, revocable sessions with current-session protection;
- unified recovery center for unfinished work and operational recovery;
- executable resilience tests and Build 117 stabilization.

See `docs/builds/BUILD_108_117_WORKFLOW_RESILIENCE.md`.

### Hearth Daily Focus — complete vertical slice

- Account-local daily intention and up to three ordered Quest references;
- ownership and availability validation inside one Hearth transaction;
- complete empty, editor, validation, success, revision, clear, and failure states;
- responsive Hearth composition and JavaScript-independent editor;
- Account export and closure lifecycle coverage;
- service, authorization, rendering, limit, and browser-journey verification.

See `docs/features/HEARTH_DAILY_FOCUS.md`.

### Worlds Home and Reaction Review — implemented vertical slice

- story-first `/worlds` composition with active chapter, scene, continuation,
  World State, permissions, and lifecycle paths;
- durable account-scoped review state for explainable World reactions;
- new, reviewed, empty, unavailable, and success interface states;
- idempotent Epic Ordinary first-install initialization;
- Account export coverage, audit evidence, ownership tests, and responsive
  browser verification.

See `docs/features/WORLDS_HOME_AND_REACTION_REVIEW.md`.

### Healing Home Visual Foundation — implemented vertical slice

- illustrated `/home` and `/healing-home` room composition with Quest Board,
  Fireplace, Journal Table, Keepsake Shelf, relationship memory, Companion
  Chair, and visible unopened rooms;
- account-scoped materialization of owned World changes and Caretaker
  continuity through existing Journey persistence;
- durable return presentation through `last_returned_at` without guilt,
  punishment, streak framing, or source ownership drift;
- accessible room labels, meaningful illustration alternative text,
  responsive single-column reflow, and source-owner links for full workflows.

See `docs/features/HEALING_HOME_VISUAL_FOUNDATION.md`.

### Healing Home Room Detail — implemented vertical slice

- `GET /home/rooms/{roomKey}` opens focused room detail pages from the
  Healing Home overview;
- open rooms render source-aware next actions for Quest Board, Journal Table,
  Fireplace, Entry Hall, and Companion Chair;
- visible locked rooms render a clear unavailable state without guilt,
  invented rituals, or hidden requirements;
- room reads remain account-scoped and update `current_room` only for open
  rooms.

See `docs/features/HEALING_HOME_ROOM_DETAIL.md`.

### Healing Home Room Presence — implemented vertical slice

- `POST /home/rooms/{roomKey}/rest` lets the person explicitly choose the
  open room they are resting in;
- visiting room pages is read-only and no longer changes `current_room`;
- current room state is rendered on room detail and the Healing Home overview
  with text plus `aria-current`;
- locked or invalid rooms cannot be selected, and successful room-rest actions
  append minimized audit evidence.

See `docs/features/HEALING_HOME_ROOM_PRESENCE.md`.

### Healing Home Room Notes — implemented vertical slice

- open Healing Home rooms can save or clear one private account-scoped note;
- room notes remain Healing Home state and do not create Quests, Chronicle
  entries, World facts, Companion memory, or notifications;
- locked rooms reject room notes, notes are bounded to 600 characters, and
  save/clear actions append minimized audit evidence;
- Account export includes room notes and Account closure deletes Healing Home
  state explicitly.

See `docs/features/HEALING_HOME_ROOM_NOTES.md`.

### Healing Home Eastern Room — implemented vertical slice

- the visible locked Eastern Room opens when the account's Epic Ordinary
  Chapter Two refuge choice exists;
- the room detail composes the source World choice, room change, fictional
  keepsake, room rest state, and private room notes;
- materialization is account-scoped and does not create real-life Quests,
  Chronicle entries, Companion memory, notifications, or District facts;
- the room renders specific source-ownership copy and remains locked with a
  useful unavailable state before the choice.

See `docs/features/HEALING_HOME_EASTERN_ROOM.md`.

### Healing Home Caretaker Conversation — implemented vertical slice

- `/home/relationships/caretaker` now includes a CSRF-protected bounded
  conversation form with four authored choices;
- submitted conversations persist as account-scoped relationship continuity,
  render in recent history, and include optional minimized remembered context;
- conversation records do not create Quests, Chronicle entries, Companion
  memory, notifications, World facts, or District records;
- Account export includes relationship conversations and Account closure
  deletes them with Healing Home composition.

See `docs/features/HEALING_HOME_CARETAKER_CONVERSATION.md`.

### Healing Home Room Map — implemented vertical slice

- `/home` now renders a labeled room map with descriptions for every room;
- open, waiting, current, and restored Eastern Room states are visible as text
  and not only as color or decoration;
- the current room retains `aria-current="location"` and visible status copy;
- the room map is presentation-only and does not mutate source District or
  World-owned records.

See `docs/features/HEALING_HOME_ROOM_MAP.md`.

### Healing Home Fireplace Reactions — implemented vertical slice

- `/home/rooms/fireplace` now lists owned World reactions with explanation
  details, approved minimized fact, World rule, interpreted time, and privacy
  exclusions;
- Fireplace reaction review reuses the existing Worlds-owned review service
  through a CSRF-protected room-local action;
- reviewed and unreviewed states render visibly inside the room;
- reactions remain account-scoped and World-owned while Healing Home composes
  them for context.

See `docs/features/HEALING_HOME_FIREPLACE_REACTIONS.md`.

### Healing Home Keepsake Shelf — implemented vertical slice

- `/home/keepsakes` lists account-owned displayed keepsakes with source and
  room labels;
- `/home/keepsakes/{keepsakeId}` renders provenance, room placement, creation
  time, and boundary copy;
- keepsake detail reads are account-scoped and return a useful unavailable
  state when missing;
- the shelf remains presentational and does not create Quests, Chronicle
  entries, Companion memory, World facts, notifications, or District records.

See `docs/features/HEALING_HOME_KEEPSAKE_SHELF.md`.

### Healing Home Journal Table Reflection Bridge — implemented vertical slice

- `/home/rooms/journal_table` now includes a visible **Start a reflection**
  action into Chronicle;
- `/chronicle/new` recognizes the Healing Home Journal Table context and
  renders editable prefilled title and tags plus ownership copy;
- Chronicle remains the source owner for saved entries, validation, privacy,
  archive, and deletion behavior;
- opening the bridge does not create entries, Quests, Companion memory, World
  facts, notifications, or Healing Home state.

See `docs/features/HEALING_HOME_JOURNAL_BRIDGE.md`.

### Healing Home Garden Unlock — implemented vertical slice

- the Garden remains a visible locked room until the account has a bounded
  Caretaker conversation;
- after that relationship moment, the Garden opens with room-specific tending
  and recovery copy, map state, and a minimized room change;
- the unlock is not a streak, score, achievement, punishment, or productivity
  reward;
- Chronicle remains the owner of any reflection started from the Garden.

See `docs/features/HEALING_HOME_GARDEN_UNLOCK.md`.

### Healing Home Room Expansion — implemented vertical slice

- Workshop, Library, and Guest Room now open from documented Journey source
  moments rather than manual scaffolding;
- Garden tending is an explicit CSRF-protected room action with minimized
  Healing Home change history and a calmer atmosphere state;
- `/home/timeline` composes room changes, keepsakes, and Caretaker continuity
  into a private room-memory timeline;
- room notes now surface a private first-line intention label without creating
  Quests, Chronicle entries, Companion memory, notifications, or World facts;
- `/home/privacy` explains what Healing Home composes and what it deliberately
  does not access.

See `docs/features/HEALING_HOME_ROOM_EXPANSION.md`.

### Healing Home Visual Depth — implemented vertical slice

- `/home` now presents an arrival scene that names atmosphere, recent change,
  open-room count, and the next threshold without pressure;
- the room map has symbolic room markers and clearer blueprint-style state
  language for open, waiting, and current rooms;
- room detail pages include room symbols and a keyboard-accessible room
  walkway for previous room, house map, and next room navigation;
- Garden, Workshop, and Library have stronger front-facing visual motifs while
  preserving text states and source ownership;
- the slice remains presentation and navigation focused: no hidden scoring,
  no new District ownership, and no invented backend obligations.

See `docs/features/HEALING_HOME_VISUAL_DEPTH.md`.

### Healing Home Intrigue — implemented vertical slice

- room doors now communicate richer waiting/open states while preserving exact
  text status and unlock boundaries;
- `/home` includes a non-diagnostic House Pulse panel tied to atmosphere;
- Fireplace, Library, Garden, Workshop, Guest Room, and Eastern Room have
  deeper front-facing panels for echoes, explanations, care, unfinished ideas,
  consent preview, and chosen purpose;
- ambient empty states explain what belongs in a room without pressure;
- source-thread routes show where a room memory came from, what room it
  affected, and what stayed private.

See `docs/features/HEALING_HOME_INTRIGUE.md`.

### Healing Home Resonance — implemented vertical slice

- `/home` now includes House Resonance routes for understanding, making, and
  gentle recovery;
- room detail pages include non-mutating Room Practice panels that suggest a
  way to use the room without creating hidden obligations;
- `/home/guide` explains how to move through the house by meaning, making,
  recovery, or boundaries;
- source-thread pages now include follow-through actions back to the affected
  room, privacy boundary, and house guide;
- the slice remains front-facing and consent-preserving: no new scoring,
  diagnosis, District writes, or hidden automation.

See `docs/features/HEALING_HOME_RESONANCE.md`.

### Healing Home Presence — implemented vertical slice

- `/home/today` gives a one-page read of current room, atmosphere, latest
  threshold, and a gentle route;
- `/home/rooms` provides a full room directory with state, symbols, door copy,
  and source-aware purpose;
- `/home/sources` explains source owners and deliberately excluded data;
- `/home/guide` now links to Today, the room directory, source glossary, and
  threshold reminders;
- `/home` exposes Today, Room Directory, and Source Glossary as primary house
  orientation paths.

See `docs/features/HEALING_HOME_PRESENCE.md`.

### Healing Home Living House — implemented vertical slice

- `/home/invitations` presents gentle room invitations without assigning work;
- `/home/thresholds` groups open and waiting doorways with source-aware door
  copy;
- `/home/atlas` maps the house into meaning, story, care, making, and return;
- room detail pages now include room invitation panels alongside practices;
- `/home` exposes the living-house surfaces from the main orientation flow.

See `docs/features/HEALING_HOME_LIVING_HOUSE.md`.

### Healing Home Deepening — implemented vertical slice

- `/home/lore` gives every known room authored lore while preserving source
  ownership;
- `/home/constellations` groups rooms by meaning, making, care, and welcome;
- `/home/boundaries` provides a boundary ledger for may-show, may-suggest,
  must-ask-first, and must-not-touch rules;
- `/home/wayfinding` answers intent-based navigation questions;
- `/home` exposes these deepening surfaces from the living-house panel.

See `docs/features/HEALING_HOME_DEEPENING.md`.

### Healing Home Compass — implemented vertical slice

- `/home/compass` orients the house by direction and purpose;
- `/home/moods` explains mood language as presentation, not diagnosis;
- `/home/by-need` maps common user needs to safe room paths;
- `/home/consent-map` shows explicit-approval boundaries for saving, acting,
  sharing, Companion proposals, and excluded sources;
- `/home/changelog` gives a human-readable in-product guide to current Healing
  Home surfaces.

See `docs/features/HEALING_HOME_COMPASS.md`.

### Healing Home Final Polish — implemented vertical slice

- `/home` now consolidates the many Healing Home surfaces into grouped shelves:
  Start Here, Living House, Trust and Meaning, and Compass;
- the final polish keeps every route available while reducing the entry-point
  link pile;
- the page explicitly records that Healing Home is ready to move sections after
  this polish pass;
- no new state, migrations, scoring, diagnosis, hidden automation, or District
  writes were introduced.

See `docs/features/HEALING_HOME_FINAL_POLISH.md`.

### Hearth Dashboard Polish — implemented vertical slice

- `/hearth` now includes a dashboard orientation grid for Act, Reflect,
  Healing Home, and Worlds;
- the Hearth hero exposes Healing Home, Daily Focus, and Guide actions;
- a Hearth trust strip explains that Hearth composes and links but does not
  create Quests, Chronicle entries, Companion memory, World facts, or Healing
  Home state by itself;
- the polish uses a dedicated `hearth-polish.css` stylesheet with responsive
  and forced-color support.

See `docs/features/HEARTH_DASHBOARD_POLISH.md`.

### Quest and Chronicle Polish — implemented vertical slice

- `/quests` now frames real-life action as an intentionally light commitment
  surface with direct paths to create a Quest, reflect in Chronicle, or open
  the Healing Home Quest Board;
- Quest detail pages include a non-mutating reflection bridge into Chronicle;
- `/chronicle` now frames saved reflection as intentional memory with paths
  back to Quests and the Healing Home Journal Table;
- `/chronicle/new` explains before saving that drafting does not complete a
  Quest, notify anyone, create Companion memory, or change World State;
- the shared polish uses `action-memory-polish.css` with responsive and
  forced-color support.

See `docs/features/QUEST_CHRONICLE_POLISH.md`.

### Worlds and Epic Ordinary Polish — implemented vertical slice

- `/worlds` now includes a story doorway for the active World with current
  chapter, scene, objective, keepsake, fact boundary, latest signal, and
  received-fact summary;
- Worlds Home reaction cards now show approved fact and World rule summaries
  before linking to the full explanation;
- a Worlds ownership strip explains that Worlds own fictional progress,
  choices, objectives, keepsakes, relationship state, and reactions without
  owning Quests, Chronicle, Companion memory, account secrets, or unrelated
  records;
- Epic Ordinary Chapter Two now has stronger Eastern Room preview, refuge
  choice, consequence, and post-choice navigation surfaces;
- the polish uses `world-home.css` and `world.css` additions with responsive
  and forced-color support.

See `docs/features/WORLDS_EPIC_ORDINARY_POLISH.md`.

## Current migrations

Production deployment must apply every file in `database/migrations`, including:

```text
037_platform_mail_operations.sql
038_042_gather_lifecycle_beacon_domains.sql
043_047_lifecycle_stabilization_beacon_management.sql
048_052_organization_foundation.sql
053_062_organization_operations.sql
063_072_household_foundation.sql
073_082_accessibility_personalization.sql
083_092_workflow_resilience.sql
093_hearth_daily_focus.sql
094_hearth_daily_focus_lifecycle.sql
095_world_reaction_reviews.sql
096_healing_home_room_notes.sql
```

Run:

```bash
php tools/migrate.php
```

## Background workers

```bash
php tools/mail-worker.php 20
php tools/gather-reminder-worker.php 100
php tools/worker.php 10
```

## Organization routes

- `GET|POST /organizations`
- `GET /organizations/{id}`
- `GET /organizations/invitations/{token}`
- `POST /organizations/{id}/invitations`
- `POST /organizations/{id}/members/{membershipId}/role`
- `POST /organizations/{id}/members/{membershipId}/remove`
- `POST /organizations/{id}/ownership/{membershipId}`
- `POST /organizations/{id}/events`
- `POST /organizations/{id}/links`

## Household routes

- `GET|POST /households`
- `GET /households/{id}`
- `GET /households/invitations/{token}`
- `POST /households/{id}/settings`
- `POST /households/{id}/lifecycle/{state}`
- `POST /households/{id}/invitations`
- `POST /households/{id}/members/{membershipId}/role`
- `POST /households/{id}/members/{membershipId}/remove`
- `POST /households/{id}/ownership/{membershipId}`
- `POST /households/{id}/leave`
- `POST /households/{id}/resources`
- `POST /households/{id}/events`
- `POST /households/{id}/quest-proposals`
- `POST /households/quest-proposals/{proposalId}/{accepted|declined}`

## Hearth Daily Focus routes

- `GET /hearth/focus`
- `POST /hearth/focus`
- `POST /hearth/focus/clear`

## Healing Home routes

- `GET /home`
- `GET /healing-home`
- `GET /home/timeline`
- `GET /home/privacy`
- `GET /home/rooms/{roomKey}`
- `POST /home/rooms/{roomKey}/rest`
- `POST /home/rooms/{roomKey}/note`
- `POST /home/rooms/{roomKey}/note/clear`
- `POST /home/rooms/garden/tend`
- `GET /home/relationships/{characterKey}`
- `POST /home/relationships/{characterKey}/converse`

## Worlds Home routes

- `GET /worlds`
- `POST /worlds/reactions/{reactionId}/review`

## Organization rules

- Organization membership is optional.
- Creating an Organization creates one active Owner membership for the creator.
- Organization role never grants a platform-wide role.
- The final Owner cannot be removed by ordinary membership controls.
- Ownership transfer is atomic and leaves one active Owner.
- Organization-owned resources survive member departure.
- Personal records do not transfer into Organizations.
- Organization invitations are email-bound, expiring, and token-hashed.
- Organization-created Gather and Beacon resources retain their District ownership boundaries.

## Beacon domain rules

- `krvk.nl` remains the default verified platform short-link domain.
- `https://krvk.nl/` permanently redirects to `https://koravik.com/`.
- Custom hostnames require verification before activation.
- Organization-owned Beacon records keep stable UUID identity and a platform fallback.
- DNS and certificate provisioning remain hosting responsibilities.

## Explicit current boundaries

- Organization-owned Gather management is capability-based; participant self-service and personal outcome consent remain separate.
- Organization invitations depend on configured Platform Mail workers and never expose stored raw tokens.
- Archive and suspension are recoverable; destructive Organization deletion is intentionally not implemented.
- Organization domain selection accepts only verified Organization or platform domains.
- Team roles do not grant Organization-wide or Platform-wide capabilities.
- Organization Quest coordination is proposal-only until the recipient explicitly accepts.
- Household remains separate, independent, private by default, and optional.
- Household responsibility proposals create no Quest until the recipient accepts.
- Household Gather records remain owned by Gather, and Household resources are not public Beacon content.
- Payments and external calendar synchronization remain deferred unless separately approved.

## Validation

The single workflow at `.github/workflows/validate.yml` must lint PHP, migrate an isolated MySQL database, start the application, and run `php tools/test.php`. The release suite verifies migration inventory, critical schema, security primitives, Organization and Household capabilities, Gather authorization boundaries, subdirectory routing, accessibility preferences, Platform Mail operations, workflow recovery, duplicate protection, session revocation, bounded workers, the Build 117 checkpoint, Hearth Daily Focus, Worlds Home ownership, reaction review, rendering, first-install initialization, Healing Home owned-room continuity, Healing Home room detail ownership, explicit Healing Home room presence, and private Healing Home room notes.

## Next build

Continue the forward-facing product phase with another complete vertical slice. Prioritize cohesive District screens, Organization or Household dashboards, Epic Ordinary continuation, and responsive interaction polish; add backend work only when the selected visible workflow requires it.

## Build workflow

For every build: inspect current `main`, read affected authority, identify the player-visible outcome and visual home, implement one coherent vertical slice, validate it, update affected documentation, and land one cohesive milestone.
