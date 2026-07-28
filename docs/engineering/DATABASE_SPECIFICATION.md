# Database and Migration Specification

**Status:** Draft for Blueprint v1.0 review  
**Version:** 1.0  
**Owner:** Koravik Architecture  
**Last reviewed:** 2026-07-28

## Purpose

This document defines the relational database and migration conventions for Koravik-Final. It supplements the canonical architecture and exists to prevent schema drift, hidden ownership, unsafe migrations, and inconsistent persistence patterns.

## Supported database

Koravik-Final supports MySQL or MariaDB through PDO.

Required baseline:

- InnoDB tables;
- `utf8mb4` character set;
- one compatible collation across related tables;
- UTC storage for timestamps;
- prepared statements;
- transactional DDL assumptions verified against the deployed database version;
- no manual production schema changes outside the migration system.

## Ownership and naming

Every table has exactly one owning module.

Table names use a clear module prefix:

```text
platform_accounts
platform_sessions
platform_capabilities
platform_role_capabilities
platform_audit_entries
platform_event_outbox
platform_event_deliveries
platform_migrations
hearth_layouts
quest_definitions
quest_occurrences
quest_completions
world_catalog_entries
world_installations
world_states
world_event_subscriptions
world_reactions
world_delayed_consequences
```

A module must not write directly to another module's tables. Cross-module references must be explicit and must not transfer ownership.

## Identifier strategy

Canonical persisted entities use opaque identifiers.

Initial implementation rules:

- use unsigned `BIGINT` auto-increment primary keys for internal relational efficiency unless an accepted ADR selects another strategy;
- expose separate opaque public identifiers where predictable numeric IDs would create privacy, enumeration, or integration concerns;
- never use mutable names, slugs, or email addresses as primary keys;
- event IDs and correlation IDs use globally unique string identifiers suitable for safe replay and cross-system tracing;
- identifier type must remain consistent across foreign keys.

Public identifiers must be unique and indexed.

## Common columns

Most mutable entity tables should include:

```text
id
public_id where externally addressable
created_at
updated_at
```

Add only when the domain requires them:

```text
deleted_at
created_by_account_id
updated_by_account_id
version
status
```

Timestamps are stored in UTC with sufficient precision for ordering and diagnostics. Presentation converts to the user's timezone.

## Nullability

Columns are `NOT NULL` unless absence is a meaningful, documented domain state.

Do not use empty strings, zero values, magic dates, or sentinel IDs to represent missing data.

## Enumerated states

For stable, tightly bounded states, use a database-safe string or constrained value documented in the module contract.

Avoid native database `ENUM` when it would make deployment or rollback unnecessarily rigid. State transitions belong in domain code and must be tested.

## Foreign keys

Use foreign keys for true ownership and stable lifecycle relationships.

Rules:

- index every foreign key;
- use `RESTRICT` by default for protected records;
- use `CASCADE` only when the child has no meaning outside the parent;
- use `SET NULL` only when the child legitimately survives without the reference;
- document exceptions where operational or retention requirements prohibit a foreign key;
- never rely on polymorphic type-and-ID pairs without an accepted reference contract.

## Unique constraints

Use database uniqueness to enforce invariants that must survive concurrency.

Examples:

- one Account email identity after normalization;
- one public ID per entity type;
- one completion per Quest occurrence and Account where the model requires it;
- one event delivery record per event and consumer;
- one active installation record per Account and World package version where applicable;
- one migration version entry.

Application checks improve error messages but do not replace database constraints.

## Indexing

Indexes must be driven by accepted query paths.

At minimum, index:

- ownership foreign keys;
- public identifiers;
- status and lifecycle columns used for filtering;
- due times for scheduled work;
- outbox availability, processing state, and retry time;
- event type and occurrence time where operationally queried;
- World installation and Account scope;
- unique idempotency keys.

Avoid speculative indexes. Each nontrivial index should have a known query or constraint purpose.

## Money, measurements, and structured values

Monetary values, when introduced, must use integer minor units plus an explicit currency code. Floating-point storage is prohibited for money.

Measurements must store a normalized numeric value, unit, and provenance where required by the owning domain.

JSON may be used for bounded, versioned, non-relational payloads such as event envelopes, World state fragments, or configuration snapshots. JSON must not become a substitute for owned relational models or queryable invariants.

## Sensitive data

Sensitive records require explicit classification and retention rules.

Requirements:

- store only data required by the owning use case;
- separate authentication secrets from profile data;
- never store plaintext passwords, reset tokens, API secrets, or session tokens;
- hash or encrypt secrets using approved platform services;
- minimize event payloads and avoid copying sensitive source records into outbox tables;
- do not expose private Chronicle or Health text through generic search or analytics tables;
- record consent and revocation independently of World state.

## Soft deletion

Soft deletion is not the default.

Use it only when the domain requires restoration, legal retention, synchronization, or historical continuity. A soft-deleted row must have documented behavior for:

- normal queries;
- uniqueness;
- relationships;
- restoration;
- final erasure;
- audit and event publication.

Security-sensitive credentials and expired sessions should generally be revoked or deleted rather than merely hidden.

## Audit versus history

Audit records describe who performed a consequential action and when. Domain history describes how an owned record changed over time. Event outbox rows support delivery.

These are different concerns and must not be collapsed into one catch-all table.

## Transaction boundaries

A transaction must include every write required to make one domain action truthful.

For the reference Quest completion:

```text
BEGIN
  lock or verify eligible Quest occurrence
  insert Quest completion
  update occurrence state where needed
  insert minimized outbox event
  insert audit entry where required
COMMIT
```

World reactions occur in a separate transaction after the source transaction commits.

Avoid long transactions, user interaction inside a transaction, network calls inside a transaction, and broad table locks.

## Concurrency control

Use the least complex mechanism that preserves invariants:

- unique constraints for duplicate prevention;
- atomic conditional updates for state transitions;
- row locks for narrowly scoped contested records;
- optimistic version columns for user-edited records where collision feedback is valuable;
- idempotency keys for retried commands and event consumers.

Concurrency behavior must be tested, not assumed.

## Event outbox tables

The outbox model must support:

- globally unique event ID;
- canonical event name and schema version;
- owner module;
- occurred-at timestamp;
- actor and subject references where appropriate;
- correlation and causation IDs;
- privacy classification;
- minimized payload;
- availability time;
- processing state;
- attempt count;
- last error summary;
- created and processed timestamps.

Consumer delivery or idempotency state must be recorded separately so one failed consumer does not erase successful delivery to another.

Raw secrets or unrestricted source records must never be placed in the outbox.

## World state persistence

World State is independent per Account and World installation.

It may include versioned structured state for:

- current story position;
- narrative flags;
- NPC relationship dimensions;
- World Quest progress;
- inventory or rewards;
- pending choices;
- delayed consequence references;
- package and schema version.

World State must include enough provenance to explain meaningful reactions without duplicating sensitive District data.

World package updates must not mutate state without a versioned migration path and recoverable failure behavior.

## Migration system

All schema and required reference-data changes use ordered, versioned migrations stored in the repository.

Each migration includes:

- immutable version identifier;
- descriptive name;
- `up` behavior;
- explicit rollback behavior where safe, or a documented irreversible classification;
- compatibility notes;
- expected runtime characteristics;
- validation checks when needed.

Applied migrations are recorded in `platform_migrations` with version, checksum, applied time, duration, and deployment identifier where available.

Editing an applied migration is prohibited. Corrections require a new migration.

## Migration naming

Use sortable identifiers, for example:

```text
202607280001_create_platform_accounts.php
202607280002_create_authentication_sessions.php
202607280003_create_event_outbox.php
```

The exact runner format may be refined, but ordering must be deterministic and independent of filesystem ambiguity.

## Safe migration rules

Migrations must be compatible with shared-host deployment and bounded execution.

Required practices:

- create tables and indexes explicitly;
- avoid unbounded data rewrites in one request;
- split large backfills into resumable batches;
- make additive changes before destructive removals;
- deploy code that tolerates both old and new schema during multi-step transitions when necessary;
- verify column and index existence before recovery-oriented reruns where the runner supports it;
- fail loudly and stop subsequent migrations after an error;
- preserve a database backup or provider snapshot before destructive production migrations.

## Destructive changes

Dropping or narrowing data requires:

1. an accepted reason;
2. retention and export review;
3. code no longer reading or writing the field;
4. migration compatibility period where appropriate;
5. backup and rollback plan;
6. explicit production acceptance.

Destructive changes must never be smuggled into unrelated builds.

## Seed and reference data

Production-required reference data must be versioned and idempotent.

Development fixtures and test data must remain separate from production migrations.

The Owner account must not be created with a hard-coded password or committed secret. Initial ownership must use a secure installer, one-time bootstrap token, or equivalent approved mechanism.

## Backup and restore

Before production authorization, Koravik must document and test:

- database backup procedure;
- restoration into an isolated environment;
- migration reapplication after restore;
- preservation of public IDs and event IDs;
- handling of outbox records restored to an earlier point;
- recovery of uploaded assets referenced by database records.

A backup that has never been restored is not considered validated.

## Testing requirements

Database tests must cover:

- clean installation through all migrations;
- upgrade from the previous accepted schema;
- failed migration behavior;
- foreign key and uniqueness invariants;
- transaction rollback;
- concurrent duplicate prevention;
- event outbox atomicity;
- idempotent consumer persistence;
- World state isolation;
- supported MySQL or MariaDB compatibility.

## Build 001 database scope

Build 001 may introduce only the tables required for:

- Accounts and authentication foundation;
- capabilities and role assignment;
- sessions and recovery tokens;
- audit foundation;
- migration tracking;
- event outbox and delivery tracking;
- configuration or installation metadata essential to bootstrap.

Quest and World tables should be introduced in the build that implements their actual vertical-slice behavior unless the accepted Build 001 plan proves they are required sooner.

## Review checklist

Before accepting a schema change, confirm:

1. The owning module is explicit.
2. The table and columns use canonical naming.
3. Nullability represents real domain meaning.
4. Constraints enforce concurrency-sensitive invariants.
5. Sensitive data is minimized and classified.
6. Query paths have appropriate indexes.
7. The transaction boundary is documented.
8. Migration execution is bounded and recoverable.
9. Upgrade and rollback behavior are tested.
10. The change does not create direct cross-module persistence access.
