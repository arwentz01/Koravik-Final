# Koravik-Final Implementation Handoff

**Status:** Moment Engine Foundation in the current working tree
**Version:** 2.59
**Baseline date:** August 1, 2026
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

### Epic Ordinary Reclamation Sprint — implemented major reclamation pass

- Audited Koravik-Final authority and the approved legacy Epic-Ordinary source at `/Applications/MAMP/htdocs/Epic-Ordinary`;
- reclaimed Healing Home as the flagship emotional center using “evidence, not rewards” and “nothing important happens off-screen” as implementation rules;
- added source-aligned evidence objects, Quiet Hearth whispers, Caretaker lantern continuity, discoveries, tiny joys, seasonal life, and Moments Remembered surfaces;
- added player routes `/home/reclamation`, `/home/discoveries`, `/home/tiny-joys`, `/home/seasons`, and `/home/moments`;
- keeps the `/home/reclamation` reclamation hearth as the source-aware doorway into recovered Epic Ordinary identity;
- kept Chronicle integration explicit through player-chosen Chronicle links rather than automatic writes;
- reused existing Koravik-Final Healing Home, keepsake, and relationship-memory tables without importing legacy schemas or routes.

See `docs/features/EPIC_ORDINARY_RECLAMATION_SPRINT.md` and `docs/reclamation/EPIC_ORDINARY_RECLAMATION_AUDIT.md`.

### Moment Engine Foundation — implemented broad runtime foundation

- Adds `platform_moments` for source-aware Moment candidates, arrival/read state, remembered state, and Chronicle proposal linkage;
- adds `MomentService` and `MomentController`;
- adds `/moments`, `/moments/next`, `/moments/remembered`, and `/moments/{id}`;
- seeds initial candidates from Epic Ordinary World reactions and Healing Home visible changes;
- supports one-at-a-time arrival scenes, replay-safe remembered moments, archive/dismiss state, and Chronicle preservation review;
- keeps source ownership explicit: source modules own original changes, Moment Engine owns presentation state, and Chronicle owns saved prose only after explicit review.

See `docs/features/MOMENT_ENGINE_FOUNDATION.md`.

### Moment Scene Templates and Source Expansion — implemented broad-stroke continuation

- Adds additive scene-template fields to `platform_moments`;
- supports Caretaker, room, silent, memory, and companion templates;
- adds speaker label, primary object, ambient detail, and recommended action label;
- expands source seeding to Caretaker conversations and displayed Healing Home keepsakes;
- upgrades remembered Moments to group by scene type while preserving one-at-a-time arrival scenes and explicit Chronicle preservation review.

### Artifact and Room Interaction Layer — implemented broad-stroke continuation

- Displayed Healing Home keepsakes expose “Prepare as memory Moment” from the keepsake detail page;
- the interaction creates Moment Engine presentation state using the memory scene template;
- Chronicle preservation still routes through explicit review rather than automatic save;
- the interaction does not create Quests, Companion memory, or real-life achievement state.

### Chronicle Moment Preservation Polish — implemented broad-stroke continuation

- Moment-to-Chronicle proposals include scene template, room, source, provenance, and excluded-data context;
- Chronicle still owns saved prose only after explicit review and save;
- Moment Engine remains presentation/read-state owner, not the source-of-truth owner.

### Living Moment Presentation Polish — implemented milestone

- Adds `public/assets/moments.css`;
- renders `/moments/next` and Moment detail as fuller scene stages with template-specific atmosphere;
- moves provenance into a quieter secondary panel while keeping it visible before Chronicle review;
- includes reduced-motion and forced-colors safeguards.

### Remembered Moment Actions and Companion-ready Trace Expansion — implemented milestone

- Remembered Moment library includes scene-type anchors and card-level Chronicle review actions;
- displayed visitor-trace keepsakes such as robin feathers seed companion-ready Moment templates;
- library cards show source, room, object, status, and Chronicle handoff controls.

### Moment Expansion Loop — implemented 10-milestone pass

- Additional District Moment Submissions: Quests, Gather, Health, Source Review, Chronicle, Companion, and World progress now seed minimized Moment candidates through `MomentService`;
- Authored Scene Copy Packs: caretaker, room, silent, memory, and companion templates use authored copy helpers for warmer scene language;
- Moment Source Review Console: `/moments` summarizes contributing source modules without exposing private payloads;
- Moment Inbox / Tuning Controls: the library names source grouping, quiet defaults, and the one-arrival rule;
- Healing Home Living Rooms Pass: new candidates carry room keys, primary objects, and ambience hooks;
- Quest-to-Moment Loop: completions and resolutions become review-safe memory/silent scenes;
- Gather-to-Moment Loop: closeouts and outcome proposals become social Moments while guest/contact data stays in Gather;
- World Chapter Moment Layer: narrative progress can appear as quiet room-state ambience;
- Companion Presence Moment Layer: proposal state appears as companion-ready trace metadata, not private Companion memory;
- Moment Library Polish: scene grouping, source counts, direct Chronicle review, and clear empty states are now part of the Moment library surface.

### Continuity Controls Loop — implemented 10 broad-stroke pass

- Moment Controls and Preferences: Settings now names quiet Moment intensity, source grouping, arrival limits, and Chronicle suggestion boundaries;
- Living Room Reaction Layer: Moment room/object/ambient hooks are tied back to Healing Home room evidence and return paths;
- Source Review Decision Hub Polish: `/source-review` is framed as the central “what wants my decision?” surface across Companion, Gather, Chronicle, Quests, Healing Home, and Moments;
- Chronicle Memory Weaving: reflection proposals now name improved titles, tags, source context, post-save navigation, and explicit review before saved prose;
- Quest Momentum Dashboard: Quest management frames active, paused, completed, and source-originated commitments without productivity pressure;
- Gather Aftercare Loop: closeout now names optional follow-up, reflection, Quest creation, Moment preservation, and Source Inbox review;
- Companion Trust and Boundaries Pass: Companion explains what it used, what it did not use, what approval changes, and how dismissal/revalidation works;
- World Progress Continuity Pass: World detail connects chapters, approved facts, room changes, and Moments while preserving World ownership;
- Homecoming / Return Experience Upgrade: return now composes Moments, Quests, Worlds, Gather, Health-derived signals, drafts, and notices into one gentle re-entry;
- Cross-Module Privacy Audit Surface: Privacy now summarizes source owners, data boundaries, consented facts, and what never crosses modules.

### Gather + Beacon Massive Presence Pass — implemented broad-stroke round

- Gather Host Operating System: `/gather` now frames the full event lifecycle from planning through aftercare;
- Gather Event Lifecycle Map: event detail exposes agenda, day-of operations, closeout, Beacon sharing, and source ownership;
- Gather Command Mission Control: command center now names settings, capacity, RSVPs, waitlists, signups, check-in, announcements, delivery history, closeout, and aftercare in one host surface;
- Gather Agenda Presence: agenda pages now present personal planning, favorites, reminders, and event-management communication boundaries;
- Gather Day-of Operations Layer: day-of pages name front-desk lookup, manual fallback, Beacon/QR handoff, walk-ins, and Gather-owned attendance truth;
- Gather Aftercare Proposal Layer: event reflection/closeout now names optional Chronicle, Quest, Journey, World, Moment, and Source Review handoffs without automatic promotion;
- Beacon Mission Control: `/beacon` now frames short links, public pages, QR/action blocks, campaigns, domain routing, publishing safety, revisions, and privacy-aware engagement as one public toolkit;
- Beacon Domain Routing Console: Beacon dashboard names host-aware routing and stable verified-domain behavior;
- Beacon Public Trust Layer: campaign and page publishing surfaces now state what Beacon can expose and what remains source-owned;
- Beacon Public Presence Layer: public pages now name Beacon presentation ownership while keeping referenced event, RSVP, signup, attendance, and follow-up truth in source modules.

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

### Epic Ordinary Chapter Three — implemented vertical slice

- Chapter Three, **The Listening Wall**, begins only after the Eastern Room purpose is chosen;
- the player chooses which authored fictional truth the house should keep, with exact consequences shown before commitment;
- the choice durably completes a World objective, creates a fictional keepsake and story-history entry, and changes Caretaker trust with a visible reason;
- Healing Home composes the result as an account-scoped, source-labeled Library echo without creating or changing real-life records;
- begin and choice actions are CSRF-protected and idempotent, and the chapter has no productivity, streak, or approved-fact gate.

See `docs/product/EPIC_ORDINARY_CHAPTER_THREE.md`.

### Companion Help and Proposal Center — implemented vertical slice

- `/companion` now presents one coherent ask, review, approval, and execution workflow;
- visible state counts distinguish decisions needed, approved-but-unexecuted proposals, and source-owned records;
- the interface explains version-specific approval, dismissal without penalty, and destination revalidation;
- proposal cards expose destination, type, status, and version while retaining Quest and Chronicle execution boundaries;
- responsive and forced-color presentation is provided by `companion-center.css`.

See `docs/features/COMPANION_HELP_PROPOSAL_CENTER.md`.

### Welcome-Back Experience — implemented vertical slice

- `/return` now composes one optional continuation across active World state, secure drafts, unread notices, and Quest-owned occurrences;
- the page leads with “You do not have to catch up” and an immediate route to Hearth;
- older intentions retain resume, skip, dismiss, and reschedule controls while review is explicitly optional;
- source modules retain ownership and the return surface performs no silent source-record mutation;
- responsive and forced-color presentation is provided by `return-experience.css`.

See `docs/features/WELCOME_BACK_EXPERIENCE.md`.

### Complete Quest Management — implemented vertical slice

- `/quests/manage` groups active, paused, and archived commitments with direct edit and history paths;
- Quest details can be edited without rewriting occurrence history;
- the next available occurrence can be rescheduled through an account-scoped, CSRF-protected action;
- `/quests/{id}/history` exposes read-only scheduling, completion, skip, dismissal, and reschedule evidence;
- existing lifecycle, recurrence, step, milestone, resolution, and completion behavior remains source-owned by Quests.

See `docs/features/COMPLETE_QUEST_MANAGEMENT.md`.

### Complete Chronicle Entry Lifecycle — implemented vertical slice

- entry detail now composes editable content, tags, provenance, privacy boundaries, status, and lifecycle actions;
- editable entries support bounded correction, reversible archive/restore, and explicitly confirmed deletion;
- generated historical entries remain read-only and identify their source-ownership correction path;
- creation, update, archive, restore, and deletion actions produce account-scoped audit evidence;
- responsive and forced-color presentation is provided by `chronicle-management.css`.

See `docs/features/COMPLETE_CHRONICLE_LIFECYCLE.md`.

### Organization Operating Dashboard — implemented vertical slice

- the Organization home now opens with role-aware operating counts and an explicit capability summary;
- Gather events, Beacon links, membership, invitations, proposals, and activity are composed into one responsive workspace;
- source-owner labels make event and public-link ownership visible at the point of use;
- Owner, Admin, Creator, and Member controls remain capability-filtered and Organization-local;
- responsive and forced-color presentation is provided by `organization-dashboard.css`.

See `docs/features/ORGANIZATION_OPERATING_DASHBOARD.md`.

### Household Home Dashboard — implemented vertical slice

- the private Household home now opens with members, events, resources, and decision-needed counts;
- a role panel explains contextual Household capabilities and consent-first responsibility ownership;
- private Gather events, resources, proposals, invitations, members, activity, preferences, and recovery share one coherent workspace;
- Household coordination remains optional, private by default, and separate from personal Platform authority;
- responsive and forced-color presentation is provided by `household-dashboard.css`.

See `docs/features/HOUSEHOLD_HOME_DASHBOARD.md`.

### Gather Participant Journey — implemented vertical slice

- secure RSVP links now compose RSVP, signup, waitlist, attendance, and follow-up into one participant path;
- participants may claim or release signup commitments and see active or waitlisted state;
- RSVP cancellation releases related commitments and uses existing capacity/waitlist rules;
- check-in remains host-recorded Gather truth and post-event Chronicle reflection remains optional;
- responsive and forced-color presentation is provided by `gather-participant.css`.

See `docs/features/GATHER_PARTICIPANT_JOURNEY.md`.

### Beacon Page Builder and Public Experience — implemented vertical slice

- new Beacon pages begin as private drafts and route directly into edit and preview;
- the builder exposes bounded content, mobile preview, explicit visibility, and a sensitive-information publication warning;
- every page update creates durable, account-attributed revision evidence;
- unlisted and public pages render through a polished mobile-first public shell while private drafts fail closed;
- migration `097_beacon_page_revisions.sql` adds page revision history.

See `docs/features/BEACON_PAGE_BUILDER_PUBLIC_EXPERIENCE.md`.

### Discovery and Trust Completion — implemented vertical slices

- Global Search now groups authorized Quests, Chronicle, Worlds, Gather, Beacon, and Health results by source owner;
- Notifications now includes follow-up, campaign, and private Health categories with explicit preferences;
- Privacy and Consent now composes World grants, Companion context boundaries, and Health derived-sharing state;
- Audit activity exposes inspectable immutable context for consequential actions;
- Settings now acts as the account hub for preferences, accessibility, security, sessions, privacy, audit, and data controls.

See `docs/features/GLOBAL_SEARCH_COMPLETION.md`, `docs/features/NOTIFICATIONS_CENTER_COMPLETION.md`, `docs/features/PRIVACY_CONSENT_CENTER.md`, `docs/features/AUDIT_ACTIVITY_DETAIL.md`, and `docs/features/ACCOUNT_SETTINGS_HUB.md`.

### Worlds, Beacon, Gather, and Health Completion — implemented vertical slices

- World catalog/detail now shows requested subscriptions, content notices, data minimization, and permission consequences;
- Installed Worlds management now names what restart, uninstall, and delete-state actions do and do not touch;
- Beacon campaigns provide draft/active/paused/archived public call-to-action management without owning Gather truth;
- Gather closeout now supports host follow-up drafts and optional future Quest/Chronicle proposal flags;
- Health now includes a private 30-day trend summary and per-record revision history.

See `docs/features/WORLD_CATALOG_PERMISSION_PREVIEW.md`, `docs/features/INSTALLED_WORLDS_MANAGEMENT_POLISH.md`, `docs/features/BEACON_CAMPAIGNS.md`, `docs/features/GATHER_HOST_FOLLOWUP.md`, and `docs/features/HEALTH_RECORD_DETAIL_TRENDS.md`.

### Layout, Recurrence, Media, and Administration Completion — implemented vertical slices

- Hearth customization now has bounded widget ordering, visibility controls, restore defaults, and a source-owner preview;
- Quests now exposes a recurrence editor and clearer completion confirmation with bounded undo and World eligibility language;
- Companion memories can be corrected, disabled for future use, removed, and audited;
- Chronicle now has an explicit proposed-reflection review queue;
- Gather event detail and calendar/list views compose schedule, RSVP, signup, attendance, follow-up, privacy, and source ownership;
- Beacon pages now support bounded public blocks for text, links, contact, event, and QR/action content;
- Platform Media owns private metadata references without becoming District content ownership;
- authorized System Health administration reports migrations, checkpoint, worker/outbox state, failed jobs, and storage status without secrets.

See `docs/features/HEARTH_CUSTOMIZATION_COMPLETION.md`, `docs/features/QUEST_RECURRENCE_EDITOR.md`, `docs/features/QUEST_COMPLETION_CONFIRMATION_UNDO.md`, `docs/features/COMPANION_MEMORY_CONTROLS.md`, `docs/features/PROPOSED_REFLECTION_REVIEW.md`, `docs/features/GATHER_EVENT_DETAIL_COMPLETION.md`, `docs/features/GATHER_CALENDAR_LIST_VIEW.md`, `docs/features/BEACON_PUBLIC_PAGE_BLOCKS.md`, `docs/features/PLATFORM_MEDIA_FOUNDATION.md`, and `docs/features/SYSTEM_HEALTH_ADMINISTRATION.md`.

### Builds 138–147 — runtime coherence foundation

- runtime schema compatibility hardens mixed-collation UUID seams for Gather follow-ups and Beacon campaigns;
- System Health now includes Runtime Schema Compatibility, Collation / UUID Join Audit, Admin Release Readiness Console, and Worker / Mail Queue Operations Console surfaces;
- Hearth source-aware widgets render Organization, Household, and Trust summaries rather than only exposing customization controls;
- Notification sync, Beacon campaign joins, and Gather follow-up joins are covered by schema-level compatibility rather than emergency query casts;
- Build 147 is the current unauthenticated health checkpoint.

See `docs/features/BUILDS_138_147_RUNTIME_COHERENCE_FOUNDATION.md`.

### Builds 148–157 — core loop depth

- Platform Media can attach references to District-owned records without taking ownership;
- Quest recurrence updates rebuild future occurrences while preserving completed history;
- Quest detail timeline, Chronicle search/filtering, and source-created reflection proposals make action-to-memory paths inspectable;
- Companion memory provenance detail and proposal review polish show exact source, destination, consequence, editability, and approval version;
- Hearth can show a private non-diagnostic Health signal summary, and World reaction details now name permission and review state.

See `docs/features/BUILDS_148_157_CORE_LOOP_DEPTH.md`.

### Builds 158–167 — public trust and admin polish

- Notifications remain actionable with source links, read/unread/dismiss controls, and explanation details;
- Beacon pages now include public-preview safety, block reordering controls, and publishing checks before leaving private draft;
- Gather public event and participant management surfaces name what is public, what Gather owns, and which event communications are expected;
- Data controls now show Account Data Export Review and Account Closure Consequence Preview before export or closure actions;
- System Health identifies Build 167 and frames release readiness, worker/mail queue operations, storage, migrations, and runtime diagnostics without payload bodies or secrets.

See `docs/features/BUILDS_158_167_PUBLIC_TRUST_ADMIN_POLISH.md`.

### Builds 168–177 — onboarding, navigation, and everyday coherence

- first-run onboarding now explains the core loop, optional Health privacy review, Companion permissions, and Epic Ordinary as an optional story doorway;
- returning-user orientation names what changed, what may be stale, what is safe to ignore, and one manageable next step;
- Hearth now exposes a Today Command Strip for focus, next Quest, latest World reaction, pending reflection proposal, and unread notifications;
- cross-module breadcrumbs, unified guide/empty-state cards, and route-level error recovery make safe exits visible;
- Guide and Settings now organize capability areas by action, reflection, sharing, coordination, privacy, troubleshooting, and consequence;
- System Health identifies Build 177 and the `everyday-coherence-navigation` checkpoint.

See `docs/features/BUILDS_168_177_ONBOARDING_NAVIGATION_COHERENCE.md`.

### Builds 178–187 — Healing Home composition depth

- Healing Home now includes source-aware Health Garden and Gather Table rooms that link back to their owning modules without copying private payloads;
- the Home command center exposes source-aware rooms, room-note search, and Companion room-note consent;
- the Source Glossary now includes Health, Gather, Companion room-note consent, and explicit exclusions;
- global search can find account-owned Healing Home room notes while labeling them as private room-note results;
- Companion permissions now include `healing_home.room_notes`, and selected Healing Home context maps to that permission;
- System Health identifies Build 187 and the `healing-home-composition-depth` checkpoint.

See `docs/features/BUILDS_178_187_HEALING_HOME_COMPOSITION_DEPTH.md`.

### Builds 188–197 — actionable cross-module flow

- `/source-review` now provides a Hearth Source Inbox and Source Draft Review Center for Chronicle proposals, Companion proposals, Gather outcome/follow-up records, Healing Home room-note promotions, and unread source notifications;
- `/quests/create` can be opened as a Quest-from-Anywhere Draft Bridge with prefilled source context and persisted Quest provenance;
- `/chronicle/new` can be opened as a Chronicle-from-Anywhere Reflection Bridge with prefilled reflection body and consequence preview;
- Hearth now includes a Today Decision Strip upgrade that routes to the Source Inbox;
- Companion and Gather closeout surfaces now point into the same cross-module review flow;
- Healing Home room notes and Gather follow-ups can intentionally start Quest or Chronicle drafts without automatic promotion;
- System Health identifies Build 197 and the `actionable-cross-module-flow` checkpoint.

See `docs/features/BUILDS_188_197_ACTIONABLE_CROSS_MODULE_FLOW.md`.

### Builds 198–207 — Source Inbox maturity

- `/source-review` now includes total counts, bucket counts, source-owner filters, top-priority review, stable resume tokens, and read-only resume-later behavior;
- filtered empty states explain that no approval, dismissal, execution, publication, or read-state mutation occurred;
- Source Inbox cards include source-owner classes and safer review metadata;
- Hearth now displays Source Inbox count badges by Chronicle, Companion, Gather, and Healing Home ownership;
- System Health identifies Build 207 and the `source-inbox-maturity` checkpoint.

See `docs/features/BUILDS_198_207_SOURCE_INBOX_MATURITY.md`.

### Builds 208–217 — durable cross-module drafts

- Source Review room-note and Gather follow-up draft paths can now save durable `source_review.*` drafts through the existing secure `platform_form_drafts` mechanism;
- `/source-review/drafts/{id}/resume` shows a draft provenance timeline, source owner, source reference, expiry, and destination resume link;
- Recovery Center now gives Source Review drafts a direct resume action while retaining existing deletion controls;
- Source Inbox now includes durable draft items alongside live source-owned decisions;
- System Health identifies Build 217 and the `durable-cross-module-drafts` checkpoint.

See `docs/features/BUILDS_208_217_DURABLE_CROSS_MODULE_DRAFTS.md`.

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
097_beacon_page_revisions.sql
098_health_wellbeing_checkins.sql
099_discovery_trust_campaign_followup.sql
100_layout_recurrence_media_admin.sql
101_hearth_layout_widget_expansion.sql
102_runtime_schema_compatibility.sql
103_core_loop_media_timeline.sql
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

## Health domain rules

- Wellbeing observations are private by default and non-diagnostic.
- Health owns the observation, its private note, and its correction/deletion lifecycle.
- Derived sharing requires explicit consent and excludes feeling words and private notes.
- Health uses no streaks, rewards, shame, or medical recommendations.
- Account export and closure include Health-owned observations.

## Explicit current boundaries

- Gather public event pages now carry Organization branding when an event is Organization-linked, including public name, summary, contact email, and brand color.
- Gather signups are category-aware across food/potluck, shifts, equipment, supplies, setup, cleanup, transportation, childcare, accessibility support, and custom roles rather than a flat signup list.
- Gather signup slots persist organizer instructions, per-claim quantity caps, waitlist rules, overlap rules, and whether an attending RSVP is required before claiming.
- Gather participant management links show signup categories, descriptions, waitlist state, RSVP requirements, and quantity caps so guests can self-manage commitments without a Koravik account.
- Gather moderators with contextual manage capability can edit previously-created event details, modify signup needs, assign active RSVPs into signup commitments, and delete signup needs from the command center.
- Gather now has a dedicated `gather.css` visual layer for public event pages, command surfaces, signup categories, moderator forms, metrics, and responsive/forced-color states so new Gather features share one visual language.
- The site-wide Visual System now applies global UI/UX cleanup across authenticated pages: responsive app shell navigation, normalized page headers, cards, forms, checkboxes/radios, grids, tables, action groups, dark/high-contrast controls, and route-aware breadcrumbs.
- Gather’s public signup board is intentionally broader than SignupGenius-style sheets: RSVP truth, guest management, public preview, waitlists, commitments, day-of check-in, closeout, and aftercare all stay attached to the same event record.
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

The single workflow at `.github/workflows/validate.yml` must lint PHP, migrate an isolated MySQL database, start the application, and run `php tools/test.php`. The release suite verifies migration inventory, critical schema, security primitives, Organization and Household capabilities, Gather authorization boundaries, subdirectory routing, accessibility preferences, Platform Mail operations, workflow recovery, duplicate protection, session revocation, bounded workers, the Build 217 checkpoint, Hearth Daily Focus, Worlds Home ownership, reaction review, rendering, first-install initialization, Healing Home owned-room continuity, Healing Home room detail ownership, explicit Healing Home room presence, private Healing Home room notes, Health privacy, discovery/trust/campaign/follow-up contracts, layout/recurrence/media/administration completion contracts, Builds 138–147 runtime-coherence contracts, Builds 148–157 core-loop depth contracts, Builds 158–167 public trust/admin polish contracts, Builds 168–177 onboarding/navigation coherence contracts, Builds 178–187 Healing Home composition-depth contracts, Builds 188–197 actionable cross-module flow contracts, Builds 198–207 Source Inbox maturity contracts, and Builds 208–217 durable cross-module draft contracts.

## Next build

Continue by wiring additional District source submissions into Moment Engine and adding richer authored scene copy packs. Preserve evidence, not rewards; keep one-at-a-time arrival scenes; and keep Chronicle preservation review explicit.

## Build workflow

For every build: inspect current `main`, read affected authority, identify the player-visible outcome and visual home, implement one coherent vertical slice, validate it, update affected documentation, and land one cohesive milestone.
