# Koravik-Final Implementation Handoff

**Status:** Build 021 complete and merged  
**Version:** 1.21  
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
- `docs/engineering/COMPANION_EXECUTION.md`;
- `docs/engineering/COMPANION_LIFECYCLE.md`;
- `docs/engineering/ACCOUNT_DATA_LIFECYCLE.md`;
- `docs/engineering/AUTHENTICATION_RECOVERY.md`;
- `docs/engineering/RELEASE_CANDIDATE_CHECKLIST.md`.

## Architecture baseline

Koravik-Final is a PHP 8.3+ custom modular monolith using MySQL or MariaDB through PDO and an Apache-compatible shared-hosting model.

- Districts own real-life truth.
- Hearth composes but does not own source records.
- Worlds interpret approved minimized facts into independent World State.
- Companion owns proposals and approved Companion memory, not destination records.
- Companion context requires explicit permission and selection.
- Approved proposals execute only through destination-module revalidation.
- Chronicle owns saved personal and approved reflections.
- Search and Notifications are non-owning utilities.
- Account export and closure coordinate owner-specific handlers.
- Authentication recovery stores only hashed, expiring, single-use tokens.
- Authenticated pages receive one shared application shell and visual hierarchy.
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

Delivered:

- generic recovery responses that do not enumerate accounts;
- 256-bit single-use recovery tokens stored only as SHA-256 hashes;
- thirty-minute token expiry;
- bounded recovery requests;
- queued authentication-delivery abstraction;
- password reset and password-change flows;
- durable session versions that invalidate older sessions;
- session regeneration after successful authentication;
- failed-attempt tracking and fifteen-minute lockout after five failures;
- authentication audit records;
- Security settings and Forgot Password visual entry points;
- migration, syntax, reset, session-version, login, and lockout validation.

**Checkpoint:** `043288b5eeedf7e1ea60ef7392089b4c2e998a4e`

### Build 021 — Release candidate shell and visual information architecture

Delivered:

- one shared authenticated application shell;
- stable primary places: Hearth, Quests, Chronicle, Worlds, and Companion;
- distinct Search and Notifications utilities;
- account-menu homes for the Koravik Guide, Settings, Privacy, Audit Activity, Security, and Data Controls;
- semantic active-location states;
- responsive mobile navigation preserving desktop hierarchy;
- in-product `/guide` capability and ownership map;
- approved Visual Information Architecture contract;
- Release Candidate Checklist;
- removal of piecemeal primary-navigation link injection in favor of the shared shell;
- syntax, migration, hierarchy, documentation-contract, and authentication regression validation.

**Checkpoint:** `ff9396f90ee923acb4f4579189d72e4a52684ffa`

## Current database migrations

Production deployments through Build 021 must apply all migrations, including:

```text
011_companion_proposals
012_companion_execution
013_companion_lifecycle
014_companion_context_memory
015_chronicle_ownership
016_data_export_account_closure
017_auth_recovery
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

Every new capability must identify its visual home before implementation. It must be classified as one of:

1. a primary owner destination;
2. a non-owning utility;
3. an account or trust control; or
4. contextual content linking to its owner.

Hearth may preview and orient, but it may not become an infinite dashboard or duplicate full source lifecycle controls.

## Explicit current boundaries

- Companion does not autonomously monitor behavior or scan private records.
- Companion does not directly own or mutate Quest or Chronicle source records.
- Approval and execution remain separate actions.
- External AI-provider integration remains deferred.
- Household, Organization, messaging, email sending, and other collaborative domains remain deferred.
- Provider-specific authentication email delivery remains an adapter concern.
- Passkeys, social login, and multifactor authentication remain deferred.
- Export files expire and exclude authentication secrets.
- Account closure uses a cancellation window and owner-specific processing.

## Build workflow

For every build: inspect current `main`, read affected authority, identify the player-visible outcome and visual home, implement one coherent vertical slice, validate it, update all affected documentation, and merge one cohesive milestone.

## Change control

When implementation reveals a blueprint flaw, stop at the affected boundary and resolve it through an explicit document revision or ADR before continuing.