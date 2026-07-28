# Koravik-Final Implementation Handoff

**Status:** Build 015 complete and merged  
**Version:** 1.15  
**Baseline date:** July 28, 2026  
**Authoritative branch:** `main`

## Repository authority

This handoff applies only to `arwentz01/Koravik-Final`. Do not import code, schemas, routes, build numbers, deployment assumptions, or framework conventions from earlier Koravik repositories or unrelated projects.

## Mandatory reading order

Before implementation, read `README.md`, `docs/README.md`, `docs/FOUNDATIONAL_DECISIONS.md`, all canonical documents, Project Zero, product and engineering documents, the ADR register, and this handoff. Follow the authority order in `docs/README.md` when documents disagree.

For Companion work, additionally read:

- `docs/canonical/COMPANION_GOVERNANCE.md`;
- `docs/product/COMPANION_PROPOSALS.md`;
- `docs/engineering/COMPANION_EXECUTION.md`;
- Companion flows in `docs/product/USER_FLOWS.md`.

## Architecture baseline

Koravik-Final is a PHP 8.3+ custom modular monolith using MySQL or MariaDB through PDO and an Apache-compatible shared-hosting model.

- Districts own real-life truth.
- Hearth composes but does not own source records.
- Worlds interpret approved minimized facts into independent World State.
- Companion owns proposals, not destination records.
- Approved Companion proposals execute only through destination-module revalidation.
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

Explicit Quest types, ordered required and optional steps, step lifecycle, Project and Journey milestones, meaningful progress, completion guards, audit history, minimized completion facts, and preserved Pillar, Chronicle, and World reactions.

**Checkpoint:** `20f37f1e2ea81552c4f975ebbe3cdf46e73bee3a`

### Build 007 — World catalog, lifecycle, and fact permissions

World catalog metadata, informed detail presentation, shared World navigation, install/suspend/resume/uninstall-with-retention lifecycle, revocable fact permissions, consumer enforcement, audit history, migration backfill, and permission/retention validation.

**Checkpoint:** `5177ce80c454f441916d925b55416b946ee08391`

### Build 008 — Return, resume, and Quest triage

Durable visit state, meaningful-absence detection, calm welcome-back summary, stale-item groupings, occurrence resume/skip/dismiss/reschedule actions, preserved recurrence, minimized return facts, audit history, and nonpunitive Epic Ordinary acknowledgement.

**Checkpoint:** `a730fc40ee5e57b19303fc66b5f692ff45e1681c`

### Build 009 — Notifications center and attention preferences

Relational notification records and preferences, explainable source attribution, direct context links, read/unread/dismiss lifecycle, restrained shell indicator, category suppression, idempotency, and full regression validation.

**Checkpoint:** `10ba3526ef07cac0f457ab4ffae9f8d607b2168c`

### Build 010 — Global search and ownership-aware results

Authenticated global Search, grouped owner-aware results, account-scoped Quest and Chronicle queries, World catalog search, bounded snippets, literal wildcard handling, and cross-account isolation.

**Checkpoint:** `266847ada00efa7d224ad323f5e9a67af69e4862`

### Build 011 — Privacy, consent, and audit activity

Shared Privacy and Consent center, source/recipient/purpose/last-use explanations, grant and revoke controls, enforcement through World permissions, append-only consent audit records, human-readable Audit Activity, and retained historical source and World state.

**Checkpoint:** `a54c78ff1cd10291fed2357d7e2744b92584e903`

### Build 012 — Account settings, accessibility, and data controls

Relational account settings, display-name management, appearance, reduced motion, increased contrast, time-zone and date-format preferences, consequence-grouped links, honest unavailable-data-control language, validation, and audit history.

**Checkpoint:** `53be2842ba13ff02bb5a92c4df02edf48e458d24`

### Build 013 — Hearth customization and bounded composition

Account-owned Hearth layout preferences, bounded optional Pillar/Chronicle/World placements, show/hide, keyboard-safe ordering, preview, restore defaults, required orientation and next-action regions, live source-owned composition, and audit history.

**Checkpoint:** `311bf229f9459211fbd998473113b0ce14b49b8b`

### Build 014 — Companion proposals and human approval

A player can ask Companion for help, receive a bounded Quest proposal, understand source context and reasoning, edit it, approve one specific version, or dismiss it without changing a District record.

Delivered:

- account-scoped relational Companion proposal records;
- proposal types, statuses, versioning, expiration metadata, and provenance;
- explicit owning module, expected consequence, reasoning, and source context;
- initial `quest.create` proposal flow;
- editing that increments version and invalidates prior approval;
- version-specific approval and dismissal without penalty;
- visible separation between proposals and saved records;
- proposal creation, approval, and dismissal audit history;
- canonical Companion governance and product-contract documentation;
- browser and MySQL proof that approval alone creates no Quest.

**Checkpoint:** `f2376556d264be65c1f607aed3f277bb33af44e1`

### Build 015 — Approved Quest execution and Chronicle reflection proposals

Approved proposals may become source-owned records only through destination-module execution. Quests revalidates and creates approved Quest proposals; Chronicle revalidates and saves explicitly approved reflection proposals.

Delivered:

- Quests-owned `QuestProposalExecutor`;
- Chronicle-owned `ChronicleProposalExecutor`;
- editable `chronicle.reflection.create` proposals;
- source-context, destination, privacy-consequence, and Companion-draft voice labeling;
- explicit **Create Quest** and **Save to Chronicle** actions after approval;
- owner revalidation of account, proposal type, status, version, expiration, and payload;
- transactional destination record, execution receipt, proposal transition, provenance, and audit commit;
- idempotent repeat execution returning the original destination record;
- result links to the created Quest or Chronicle;
- canonical, product, engineering, and documentation-authority updates;
- successful migration, PHP lint, Quest execution, Chronicle execution, idempotency, browser, Settings, Privacy, and worker-regression validation.

**Checkpoint:** `e4d25085316b82db9d2f04c5f48bba93f64cb227`

## Current database migrations

Production deployments through Build 015 must apply all migrations, including:

```text
011_companion_proposals
012_companion_execution
```

Run:

```bash
php tools/migrate.php
```

## Explicit current boundaries

- Companion does not autonomously monitor behavior or create unsolicited consequential actions.
- Companion does not directly own or mutate Quest or Chronicle source records.
- Approval and execution remain separate user actions.
- External AI-provider integration is not part of Builds 014 or 015.
- Proposal lifecycle outbox events remain deferred; relational state and audit history are authoritative for the implemented lifecycle.
- Household, Organization, messaging, email sending, and other high-consequence proposal types remain deferred.

## Build workflow

For every build: inspect current `main`, read affected authority, state the player-visible outcome and boundary, implement one coherent vertical slice, validate it, update all affected documentation, and merge one cohesive milestone.

## Change control

When implementation reveals a blueprint flaw, stop at the affected boundary and resolve it through an explicit document revision or ADR before continuing.