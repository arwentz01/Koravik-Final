# Koravik-Final Implementation Handoff

**Status:** Build 013 complete and merged  
**Version:** 1.13  
**Baseline date:** July 28, 2026  
**Authoritative branch:** `main`

## Repository authority

This handoff applies only to `arwentz01/Koravik-Final`. Do not import code, schemas, routes, build numbers, deployment assumptions, or framework conventions from earlier Koravik repositories or unrelated projects.

## Mandatory reading order

Before implementation, read `README.md`, `docs/README.md`, `docs/FOUNDATIONAL_DECISIONS.md`, all canonical documents, Project Zero, product and engineering documents, the ADR register, and this handoff. Follow the authority order in `docs/README.md` when documents disagree.

## Architecture baseline

Koravik-Final is a PHP 8.3+ custom modular monolith using MySQL or MariaDB through PDO and an Apache-compatible shared-hosting model.

- Districts own real-life truth.
- Hearth composes but does not own source records.
- Worlds interpret approved minimized facts into independent World State.
- Platform events describe committed facts and use a transactional outbox.
- Authorization is capability-based and contextual.
- Server-rendered accessible HTML is the baseline.
- Background work is finite, idempotent, lock-safe, and cron compatible.

## Completed builds

### Build 001 — Foundation

Secure sign-in, shared shell, Hearth, Quest completion, transactional outbox, bounded worker, Epic Ordinary reaction and World State, audit history, migrations, seeding, and MySQL CI.

### Build 002 — Personal Quest creation

Account-owned Quest creation with title and optional notes, validation, durable listing, Hearth integration, completion, and World reaction.

### Build 003 — Quest lifecycle and recurrence

Relational recurrence rules, multiple weekdays, every-X intervals, generated occurrences, occurrence-based completion, pause/resume/archive lifecycle, and bounded cron-compatible generation.

### Build 004 — Pillars, Chronicle, and Hearth integration

Optional Quest-to-Pillar meaning, event-driven contribution ledger, automatic Chronicle moments, optional reflections, richer Hearth composition, completion summaries, bounded undo, reversal events, and module-neutral composite event consumers.

### Build 005 — Epic Ordinary continuation

Persistent World navigation, Chapter One, durable Caretaker support-style choice, NPC relationship state and provenance, Quest-completion-driven trust changes, fresh-install initialization, responsive presentation, and MySQL validation.

**Checkpoint:** `a21b4392ff12927e3e92ca41c91218e73e3d0740`

### Build 006 — Structured Quests, steps, and milestones

A Quest may be a single action, habit, project, journey, responsibility, or World objective. Structured Quest types may contain ordered required or optional steps, show meaningful progress, reach milestones, and prevent completion while required steps remain.

Delivered:

- explicit relational Quest types;
- ordered required and optional Quest steps;
- pending, completed, reopened, and skipped step states;
- automatic 25%, 50%, and 100% milestones for Projects and Journeys;
- progress presentation and required-step completion guard;
- audit history for step changes;
- minimized Quest type in completion events;
- preserved Pillar, Chronicle, and World reactions;
- successful browser and MySQL validation.

**Checkpoint:** `20f37f1e2ea81552c4f975ebbe3cdf46e73bee3a`

### Build 007 — World catalog, lifecycle, and fact permissions

A Player can review available Worlds, understand content and accessibility information, install or resume Epic Ordinary, suspend or uninstall it while retaining state, and explicitly grant or revoke future access to minimized Quest-completion facts.

Delivered:

- relational World catalog metadata;
- informed World detail presentation;
- shared World navigation;
- install, suspend, resume, and uninstall-with-retention lifecycle;
- revocable `quest.completed` fact permission;
- Epic Ordinary enforcement of active installation and granted permission;
- audit history for lifecycle and permission changes;
- migration backfill and seed compatibility for existing installations;
- successful browser, MySQL, permission-enforcement, retention, and structured-Quest regression validation.

**Checkpoint:** `5177ce80c454f441916d925b55416b946ee08391`

### Build 008 — Return, resume, and Quest triage

A Player returning after a meaningful absence receives a calm, bounded welcome-back summary and can decide what still matters without being presented with an overdue backlog or punishment mechanics.

Delivered:

- durable account visit and return state;
- seven-day meaningful-absence detection;
- stale, still-relevant, upcoming, completed, and archived groupings;
- resume, skip, dismiss, and reschedule decisions for individual Quest occurrences;
- preserved recurrence rules when one occurrence is skipped or dismissed;
- minimized occurrence lifecycle events and `Platform.PlayerReturned.v1`;
- audit history for return and triage decisions;
- revocable Epic Ordinary return acknowledgement without Quest details;
- restored and regression-tested Build 007 World catalog routing;
- successful browser, MySQL, World, structured-Quest, and idempotent worker validation.

**Checkpoint:** `a730fc40ee5e57b19303fc66b5f692ff45e1681c`

### Build 009 — Notifications center and attention preferences

A Player has one bounded, explainable in-app center for meaningful source-owned changes without allowing notifications to become source truth or engagement pressure.

Delivered:

- relational notification records and per-category preferences;
- event-driven World-reaction and welcome-back notifications;
- source attribution, direct context links, and plain-language delivery reasons;
- read, unread, dismiss, and mark-all-read lifecycle;
- restrained capped shell indicator;
- category preference controls that affect future notices without rewriting source history;
- idempotent source-event uniqueness and synchronization fallback for committed outcomes;
- successful browser, MySQL, preference-suppression, World, structured-Quest, return, and worker-idempotency validation.

**Checkpoint:** `10ba3526ef07cac0f457ab4ffae9f8d607b2168c`

### Build 010 — Global search and ownership-aware results

A Player can search Koravik from one shared surface and find authorized Quests, Chronicle moments, and Worlds without Search becoming a duplicate data owner.

Delivered:

- authenticated global Search route and shared shell entry;
- initial guidance, grouped results, and no-results presentation;
- account-scoped Quest and Chronicle queries;
- searchable World catalog metadata with installation status;
- bounded privacy-conscious snippets and direct owner links;
- explicit owning-module labels on every result group;
- literal handling of SQL wildcard characters;
- no duplicate search index or parallel source-of-truth tables;
- successful browser, MySQL, cross-account-isolation, wildcard, World, structured-Quest, notifications, return, and worker-idempotency validation.

**Checkpoint:** `266847ada00efa7d224ad323f5e9a67af69e4862`

### Build 011 — Privacy, consent, and audit activity

A Player can review what future facts each installed World may receive, understand the source and purpose of each grant, see when it was last used, revoke or restore permission, and inspect a human-readable read-only audit history.

Delivered:

- shared Privacy and Consent center;
- source, recipient, purpose, status, last-use, and revocation-effect presentation;
- grant and revoke controls for approved World fact categories;
- enforcement through existing World consumer permissions;
- append-only consent audit records;
- human-readable Audit Activity surface with technical context;
- account-scoped authorization;
- retained source records, Chronicle entries, World State, reactions, and audit evidence after revocation;
- successful browser, MySQL, permission-enforcement, audit, Search, notifications, Worlds, structured-Quest, and worker-idempotency validation.

**Checkpoint:** `a54c78ff1cd10291fed2357d7e2744b92584e903`

### Build 012 — Account settings, accessibility, and data controls

A Player can manage account identity, low-risk appearance and accessibility preferences, time and date presentation, and reach consequence-grouped notification, privacy, audit, and data controls from one Settings surface.

Delivered:

- relational account settings with upgrade backfill;
- display-name management;
- system, light, and dark appearance preferences;
- reduced-motion and increased-contrast preferences;
- supported time-zone and date-format preferences;
- direct links to notification preferences, Privacy and Consent, and Audit Activity;
- honest presentation of unavailable account export and deletion execution;
- transactional validation and `settings.updated` audit history;
- successful migration, browser, validation, Settings, Privacy, Search, notifications, Worlds, and worker-idempotency regression validation.

**Checkpoint:** `53be2842ba13ff02bb5a92c4df02edf48e458d24`

### Build 013 — Hearth customization and bounded composition

A Player can choose which optional supporting sections appear on Hearth, reorder them with keyboard-safe controls, preview the result, and restore defaults while required orientation and next-action regions remain fixed.

Delivered:

- relational account-owned Hearth layout preferences;
- bounded optional placements for Pillar support, Chronicle, and active World continuation;
- show and hide controls;
- collision-safe move-up and move-down ordering;
- preview guidance and restore-defaults behavior;
- required greeting and `What matters now` regions that cannot be removed;
- live composition from source-owned records without copying Quest, Chronicle, Pillar, or World truth;
- `hearth.layout.updated` and `hearth.layout.reset` audit history;
- successful migration, browser, hide, reorder, reset, Settings, Privacy, Search, notifications, and worker-idempotency regression validation.

**Checkpoint:** `311bf229f9459211fbd998473113b0ce14b49b8b`

## Build workflow

For every build: inspect current `main`, read affected authority, state the player-visible outcome and boundary, implement one coherent vertical slice, validate it, update documentation, and merge one cohesive milestone.

## Change control

When implementation reveals a blueprint flaw, stop at the affected boundary and resolve it through an explicit document revision or ADR before continuing.