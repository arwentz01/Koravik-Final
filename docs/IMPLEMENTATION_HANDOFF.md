# Koravik-Final Implementation Handoff

**Status:** Build 025 complete and merged  
**Version:** 1.25  
**Baseline date:** July 28, 2026  
**Authoritative branch:** `main`

## Repository authority

This handoff applies only to `arwentz01/Koravik-Final`. Do not import code, schemas, routes, build numbers, deployment assumptions, or framework conventions from earlier Koravik repositories or unrelated projects.

## Mandatory reading order

Before implementation, read `README.md`, `docs/README.md`, `docs/FOUNDATIONAL_DECISIONS.md`, all canonical documents, Project Zero, affected product and engineering contracts, the ADR register, and this handoff.

Focused contracts now include:

- `docs/canonical/COMPANION_GOVERNANCE.md`;
- `docs/product/COMPANION_PROPOSALS.md`;
- `docs/product/COMPANION_CONTEXT_AND_MEMORY.md`;
- `docs/product/CHRONICLE_OWNERSHIP.md`;
- `docs/product/VISUAL_INFORMATION_ARCHITECTURE.md`;
- `docs/product/ROUTE_VISUAL_INVENTORY.md`;
- `docs/product/EPIC_ORDINARY_CHAPTER_TWO.md`;
- `docs/product/WORLD_PROGRESS_AND_REACTIONS.md`;
- `docs/product/INSTALLED_WORLD_LIFECYCLE.md`;
- `docs/engineering/COMPANION_EXECUTION.md`;
- `docs/engineering/COMPANION_LIFECYCLE.md`;
- `docs/engineering/ACCOUNT_DATA_LIFECYCLE.md`;
- `docs/engineering/AUTHENTICATION_RECOVERY.md`;
- `docs/engineering/RELEASE_CANDIDATE_CHECKLIST.md`;
- `docs/engineering/VISUAL_SYSTEM.md`;
- `docs/engineering/EPIC_ORDINARY_RUNTIME.md`.

## Architecture baseline

Koravik-Final is a PHP 8.3+ custom modular monolith using MySQL or MariaDB through PDO and an Apache-compatible shared-hosting model.

- Districts own real-life truth.
- Hearth composes but does not own source records.
- Worlds interpret approved minimized facts into independent World State.
- World objectives, choices, keepsakes, relationships, reactions, and lifecycle history remain fictional and account-scoped.
- World lifecycle operations affect only the selected installation and never mutate District truth, another World, or shared catalog/package definitions.
- Companion owns proposals and approved Companion memory, not destination records.
- Chronicle owns saved personal and approved reflections.
- Search and Notifications are non-owning utilities.
- Account export and closure coordinate owner-specific handlers.
- Authentication recovery stores only hashed, expiring, single-use tokens.
- Authenticated pages receive one shared application shell and visual system.
- Appearance, reduced-motion, and increased-contrast preferences affect rendering.
- Platform events describe committed facts and use a transactional outbox.
- Authorization is capability-based and contextual.
- Background work is finite, idempotent, lock-safe, and cron compatible.

## Completed builds

### Builds 001–005 — Foundation and first vertical slice

Secure authentication, Hearth, personal Quests, recurrence, Pillars, Chronicle completion moments, transactional outbox, bounded worker, Epic Ordinary reactions, World State, relationship continuity, and audit history.

### Builds 006–013 — Core platform breadth

Structured Quests, World catalog and consent, return and resume, Notifications, Search, Privacy and Audit, Account Settings, and bounded Hearth customization.

### Builds 014–017 — Companion agency and consent

Versioned proposals, explicit approval, destination-owned Quest and Chronicle execution, execution receipts, lifecycle recovery and events, selected context, and approved revocable Companion memory.

### Build 018 — Chronicle ownership and lifecycle

Direct personal entries, provenance, tags, editing, archive, restore, eligible deletion, and read-only protection for generated historical entries.

**Checkpoint:** `3f73749d0a2f89fbd139ebe77f8e106a06f0476a`

### Build 019 — Data export, account closure, and retention

Account-scoped export manifests, secret exclusions, seven-day export expiry, staged cancellable closure, owner-specific processing, credential revocation, identity anonymization, and retention ledger.

**Checkpoint:** `36b32f917c00a2dad1d4a91ce61ad293fcf200f5`

### Build 020 — Authentication recovery and session security

Generic recovery responses, hashed single-use recovery tokens, thirty-minute expiry, bounded requests, queued delivery abstraction, password reset and change, session-version invalidation, session regeneration, failed-attempt lockout, audit history, and security entry points.

**Checkpoint:** `043288b5eeedf7e1ea60ef7392089b4c2e998a4e`

### Build 021 — Release candidate shell and visual information architecture

One shared authenticated shell; stable primary places; distinct utilities; account and trust controls; semantic active states; responsive navigation; `/guide`; approved visual information architecture; and release-candidate checklist.

**Checkpoint:** `ff9396f90ee923acb4f4579189d72e4a52684ffa`

### Build 022 — Visual system consolidation and route-state audit

Shared visual-system normalization; saved appearance, reduced-motion, and increased-contrast enforcement; contextual location navigation; shared template vocabulary; route visual inventory; and validation.

**Checkpoint:** `298ab019944d65f148d70d948f22159a0192afb8`

### Build 023 — Epic Ordinary Chapter Two and World experience home

`/worlds/epic-ordinary/play`, Chapter Two **The Eastern Room**, World-owned objectives and keepsakes, transactional narrative progression, idempotent choices, and proof that fictional progress does not mutate real-life Quests.

**Checkpoint:** `381959e1c4536b6ae8236448a6f9f89b0682377c`

### Build 024 — World progress, reactions, and story history

Delivered:

- `/worlds/epic-ordinary/progress`;
- account-scoped chapter, scene, objective, choice, relationship, keepsake, reaction, permission, and package presentation;
- `/worlds/epic-ordinary/reactions/{id}` explainability;
- minimized fact, rule, interpretation-time, and deliberate-exclusion fields;
- durable readable World story history;
- cross-account failure and private-data exclusion validation.

**Checkpoint:** `be8bec7ac12126c944222c806a8c0ca0e283de37`

### Build 025 — Installed Worlds and safe lifecycle controls

Delivered:

- `/worlds/installed` and `/worlds/{world-key}/manage`;
- activate/resume while preserving other Worlds;
- suspend while retaining state;
- uninstall with retained recoverable state;
- exact-confirmation restart scoped to one World;
- exact-confirmation deletion of eligible account-specific World State;
- installed and available package-version foundation;
- durable lifecycle revisions and consequence history;
- proof that repeated operations are safe and real-life Quest records remain unchanged.

**Checkpoint:** `afff879f6abe52a795392aa70c61f7c2098fd717`

## Current database migrations

Production deployments through Build 025 must apply all migrations, including:

```text
011_companion_proposals
012_companion_execution
013_companion_lifecycle
014_companion_context_memory
015_chronicle_ownership
016_data_export_account_closure
017_auth_recovery
018_epic_ordinary_chapter_two
019_world_progress_reactions
020_world_lifecycle
```

Run:

```bash
php tools/migrate.php
```

Account closure processing remains bounded:

```bash
php tools/process_account_closures.php 5
```

## Visual-home rule

Every new capability must identify its visual home before implementation. Hearth may preview and orient, but it may not duplicate full source lifecycle controls.

## Explicit current boundaries

- Companion does not autonomously monitor behavior or scan private records.
- Companion does not directly own or mutate Quest or Chronicle source records.
- Approval and execution remain separate actions.
- External AI-provider integration remains deferred.
- Household, Organization, messaging, email sending, and other collaborative domains remain deferred.
- Provider-specific authentication email delivery remains an adapter concern.
- Passkeys, social login, and multifactor authentication remain deferred.
- Epic Ordinary receives only currently permitted minimized facts.
- World reactions do not expose Quest notes, Chronicle prose, Companion memory, account secrets, or unrelated private records.
- Fictional World objectives never become duplicate real-life Quests.
- Restart and deletion affect only the selected account-specific World State.
- Shared World catalog and package definitions are never deleted through player lifecycle controls.

## Build workflow

For every build: inspect current `main`, read affected authority, identify the player-visible outcome and visual home, implement one coherent vertical slice, validate it, update all affected documentation, and merge one cohesive milestone.

## Change control

When implementation reveals a blueprint flaw, stop at the affected boundary and resolve it through an explicit document revision or ADR before continuing.