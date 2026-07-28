# Module Specifications

**Status:** Approved for Blueprint v1.0  
**Version:** 1.0  
**Owner:** Platform Architecture

## Purpose

This document fixes the initial module ownership map. It does not require every module to be implemented in Build 001.

## Platform modules

### Identity
Owns Accounts, profiles, account lifecycle, durable identity, and account-level settings references.

### Authentication
Owns credentials, sessions, login, logout, recovery, verification, and authentication events.

### Authorization
Owns roles, capabilities, assignments, policy evaluation interfaces, and privileged-action boundaries.

### Consent and Privacy
Owns consent records, privacy classifications, revocation, export and deletion coordination, and data-use disclosures.

### Audit
Owns append-oriented audit records, correlation, operator review, and retention controls.

### Event Bus
Owns the platform-event envelope, transactional outbox, delivery attempts, consumer registration, retry, replay, and dead-letter behavior.

### Notifications
Owns delivery preferences, notification records, channels, acknowledgement state, and bounded dispatch.

### Search
Owns cross-module search orchestration and indexing contracts, not source records.

### Media
Owns upload metadata, storage references, transformations, authorization hooks, and safe delivery.

### Installation and Migration
Owns application migration state, package installation orchestration, compatibility validation, and version history.

## District modules

### Hearth
Owns personal orientation layouts, placements, acknowledgement state, and composition rules. It reads registered summaries from other modules but does not own their facts.

### Quests
Owns tasks, responsibilities, projects, recurrence, assignments, progress, completion, and completion events. Household or Organization association does not transfer task ownership.

### Chronicle
Owns journals, reflections, memories, authored history, visibility, and approved drafts. It is not an automatic event feed.

### Health
Owns health and wellbeing records and workflows. It publishes minimized or derived facts only under approved privacy contracts.

### Gather
Owns events, invitations, attendance, participation, hosting, and event-related social coordination.

### Beacon
Owns public presentation, outreach, campaigns, organization-facing pages, and external communication surfaces.

### Companion
Owns proposal, explanation, summarization, and approved-memory records. It does not own actions executed in other Districts.

### Creator Studio
Owns authoring projects, validation, previews, package builds, creator tests, and publication workflow.

### Marketplace
Owns discovery, review status, availability, compatibility presentation, installation entry points, withdrawal, and future commercial metadata. Payments are deferred.

### Arena
Reserved for approved challenge or competition experiences. It must not become a generic engagement or ranking layer.

## Group modules

### Households
Owns households, membership, household roles, invitations, and household-level preferences. Household participation is optional.

### Organizations
Owns organizations, teams, membership, group roles, and organization governance. Beacon presents organizations publicly but does not own them.

## World modules

### World Catalog
Owns World identity, metadata, releases, compatibility, warnings, and declared permissions.

### World Installation
Owns Account installation state, activation, suspension, restart choices, granted permissions, and installation history.

### World Runtime
Owns rule evaluation, narrative transitions, eligibility, delayed consequences, and deterministic execution boundaries.

### World State
Owns story position, flags, NPC relationships, World Quests, inventory, rewards, and explainability records for one installation.

### World Migration
Owns versioned state transformations, validation, failure handling, and migration history.

### Event Subscription
Owns declared World subscriptions, consent checks, payload minimization, compatibility, and delivery authorization.

## Cross-module rules

- A module writes only its own canonical records.
- References do not transfer ownership.
- Composition does not transfer ownership.
- Platform events describe committed facts, not commands.
- Consequential cross-module changes use documented application services or approved events.
- Shared tables require an explicit Platform owner.
- Companion and Worlds never bypass owning modules.
- Optional systems must remain optional to core daily use.

## First vertical slice ownership

Build 001 uses:

- Authentication for sign-in and session;
- Authorization for Account access;
- Hearth for orientation;
- Quests for one completable action;
- Event Bus for `Quests.QuestCompleted.v1`;
- World Installation and World Runtime for Epic Ordinary;
- World State for the durable reaction and explanation;
- Audit for consequential traces.

All other modules remain outside Build 001 unless needed as minimal Platform support.