# Koravik-Final Implementation Handoff

**Status:** Build 019 complete and merged  
**Version:** 1.19  
**Baseline date:** July 28, 2026  
**Authoritative branch:** `main`

## Repository authority

This handoff applies only to `arwentz01/Koravik-Final`. Do not import code, schemas, routes, build numbers, deployment assumptions, or framework conventions from earlier Koravik repositories or unrelated projects.

## Mandatory reading order

Before implementation, read `README.md`, `docs/README.md`, `docs/FOUNDATIONAL_DECISIONS.md`, all canonical documents, Project Zero, product and engineering documents, the ADR register, and this handoff. Follow the authority order in `docs/README.md` when documents disagree.

For affected areas, additionally read:

- `docs/canonical/COMPANION_GOVERNANCE.md`;
- `docs/product/COMPANION_PROPOSALS.md`;
- `docs/product/COMPANION_CONTEXT_AND_MEMORY.md`;
- `docs/product/CHRONICLE_OWNERSHIP.md`;
- `docs/engineering/COMPANION_EXECUTION.md`;
- `docs/engineering/COMPANION_LIFECYCLE.md`;
- `docs/engineering/ACCOUNT_DATA_LIFECYCLE.md`;
- relevant flows in `docs/product/USER_FLOWS.md`.

## Architecture baseline

Koravik-Final is a PHP 8.3+ custom modular monolith using MySQL or MariaDB through PDO and an Apache-compatible shared-hosting model.

- Districts own real-life truth.
- Hearth composes but does not own source records.
- Worlds interpret approved minimized facts into independent World State.
- Companion owns proposals and approved Companion memory, not destination records.
- Companion context requires explicit permission and selection.
- Approved proposals execute only through destination-module revalidation.
- Chronicle owns saved personal and approved reflections.
- Account export and closure coordinate owner-specific handlers rather than bypassing ownership.
- Platform events describe committed facts and use a transactional outbox.
- Authorization is capability-based and contextual.
- Server-rendered accessible HTML is the baseline.
- Background work is finite, idempotent, lock-safe, and cron compatible.

## Completed builds

### Builds 001–005 — Foundation and first vertical slice

Secure authentication, shared shell, Hearth, personal Quest creation and completion, recurrence, Pillars, Chronicle completion moments, transactional outbox, bounded worker, Epic Ordinary reactions, World State, relationship continuity, and audit history.

### Build 006 — Structured Quests

Explicit Quest types, ordered required and optional steps, milestones, progress, completion guards, audit history, minimized completion facts, and preserved downstream reactions.

**Checkpoint:** `20f37f1e2ea81552c4f975ebbe3cdf46e73bee3a`

### Build 007 — World catalog and consent

World metadata, installation lifecycle, retained state, revocable fact permissions, consumer enforcement, and audit history.

**Checkpoint:** `5177ce80c454f441916d925b55416b946ee08391`

### Build 008 — Return and resume

Meaningful-absence detection, calm return summary, stale occurrence triage, preserved recurrence, minimized return facts, and nonpunitive World acknowledgement.

**Checkpoint:** `a730fc40ee5e57b19303fc66b5f692ff45e1681c`

### Build 009 — Notifications

Explainable in-app notifications, preferences, source links, read/unread/dismiss lifecycle, restrained shell indicator, and idempotent delivery.

**Checkpoint:** `10ba3526ef07cac0f457ab4ffae9f8d607b2168c`

### Build 010 — Search

Account-scoped Quest and Chronicle search, World catalog search, grouped ownership labels, bounded snippets, literal wildcard handling, and cross-account isolation.

**Checkpoint:** `266847ada00efa7d224ad323f5e9a67af69e4862`

### Build 011 — Privacy and audit

Privacy and Consent center, source/recipient/purpose explanations, grant and revoke controls, permission enforcement, and human-readable append-only Audit Activity.

**Checkpoint:** `a54c78ff1cd10291fed2357d7e2744b92584e903`

### Build 012 — Account settings

Display name, appearance, reduced motion, increased contrast, time zone, date format, consequence-grouped links, validation, and audit history.

**Checkpoint:** `53be2842ba13ff02bb5a92c4df02edf48e458d24`

### Build 013 — Hearth customization

Bounded optional Pillar, Chronicle, and World placements; show/hide; keyboard-safe ordering; preview; restore defaults; and required orientation and next-action regions.

**Checkpoint:** `311bf229f9459211fbd998473113b0ce14b49b8b`

### Build 014 — Companion proposals

Account-scoped Quest proposals, source context, reasoning, destination, consequence, editing, version-specific approval, dismissal, expiration metadata, and proof that approval alone creates no Quest.

**Checkpoint:** `f2376556d264be65c1f607aed3f277bb33af44e1`

### Build 015 — Destination-owned proposal execution

Quests-owned and Chronicle-owned execution, reflection proposals, explicit execution actions, destination revalidation, receipts, idempotency, provenance, result links, and audit history.

**Checkpoint:** `e4d25085316b82db9d2f04c5f48bba93f64cb227`

### Build 016 — Companion lifecycle, recovery, and events

Delivered:

- enforced expiration for awaiting and approved proposals;
- renewal with a new version and cleared approval;
- proposal clarification without destination mutation;
- bounded execution failure code and human-readable failure context;
- attempt and last-attempt tracking;
- read-only Companion activity;
- minimized `Companion.ProposalRevised`, `Companion.ProposalClarified`, and `Companion.ProposalExecutionFailed` events;
- migration, lint, MySQL lifecycle, and event validation.

**Checkpoint:** `78b65369c4a187e156393ca5c21d1f8d5c613eb1`

### Build 017 — Consent-scoped Companion context and memory

Delivered:

- explicit context permissions for selected Quests, selected Chronicle entries, Pillar summaries, accessibility preferences, and approved memory;
- minimized one-time context-use records;
- approved Companion memory with provenance;
- active, disabled, and deleted memory states;
- context and memory control surfaces;
- no background scanning or automatic memory creation;
- migration, lint, permission, context-use, memory, and isolation validation.

**Checkpoint:** `eddd252127686fcd15a42f9a36936465159f496c`

### Build 018 — Chronicle ownership and lifecycle

Delivered:

- direct personal Chronicle entries;
- provenance and editability metadata;
- tags;
- editing for player-authored and player-approved entries;
- archive, restore, and eligible deletion;
- read-only protection for generated historical entries;
- detail and management surfaces;
- audit history and MySQL lifecycle validation.

**Checkpoint:** `3f73749d0a2f89fbd139ebe77f8e106a06f0476a`

### Build 019 — Data export, account closure, and retention

Delivered:

- account-scoped export requests with JSON payload and manifest;
- exclusion of credentials, secrets, encryption material, and other accounts’ data;
- seven-day export expiry;
- exact confirmation phrase and seven-day closure cancellation window;
- bounded `tools/process_account_closures.php` processor;
- owner-specific Quests, Chronicle, Companion, Worlds, and Platform closure steps;
- credential revocation and durable identity anonymization;
- retention ledger and closure audit records;
- migration, lint, export, cancellation, closure processing, and owner-step validation.

**Checkpoint:** `36b32f917c00a2dad1d4a91ce61ad293fcf200f5`

## Current database migrations

Production deployments through Build 019 must apply all migrations, including:

```text
011_companion_proposals
012_companion_execution
013_companion_lifecycle
014_companion_context_memory
015_chronicle_ownership
016_data_export_account_closure
```

Run:

```bash
php tools/migrate.php
```

Account closure processing should run through a bounded cron-compatible command:

```bash
php tools/process_account_closures.php 5
```

## Explicit current boundaries

- Companion does not autonomously monitor behavior or scan private records.
- Companion does not directly own or mutate Quest or Chronicle source records.
- Approval and execution remain separate actions.
- Companion memory is explicit, revocable, and separate from Chronicle and World State.
- External AI-provider integration remains deferred.
- Household, Organization, messaging, email sending, and other high-consequence proposal types remain deferred.
- Chronicle-generated historical entries are corrected through their source or compensating facts, not rewritten as personal prose.
- Export files expire and exclude authentication secrets.
- Account closure uses a cancellation window and owner-specific processing; shared definitions and unrelated records are retained.

## Build workflow

For every build: inspect current `main`, read affected authority, state the player-visible outcome and boundary, implement one coherent vertical slice, validate it, update all affected documentation, and merge one cohesive milestone.

## Change control

When implementation reveals a blueprint flaw, stop at the affected boundary and resolve it through an explicit document revision or ADR before continuing.
