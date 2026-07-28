# Koravik Architecture

**Version:** 3.0
**Status:** Canonical Technical Direction
**Rewritten:** Build 204E

---

## 1. Architectural Summary

Koravik v3 is a custom PHP 8.3+ modular monolith designed for shared-hosting compatibility, clear domain ownership, secure evolution, and future extensibility.

Laravel is retired. Laravel conventions, Artisan, Eloquent, Blade, Laravel migrations, and Laravel-specific dependencies must not be introduced.

The architecture should remain simpler than a distributed system while preserving boundaries strong enough to support future extraction if justified.

## 2. Architectural Goals

The architecture must support:

- secure Account identity;
- role and capability authorization;
- independent District ownership;
- optional Households and Organizations;
- installable Worlds;
- independent World State;
- versioned Platform Events;
- Companion access controls;
- Creator Studio packages;
- safe migrations;
- auditability;
- accessibility;
- shared-host deployment;
- maintainable automated testing.

## 3. Major Layers

```text
HTTP / CLI Entry Points
        ↓
Application Services
        ↓
Domain Modules
        ↓
Repositories and Platform Services
        ↓
Database, Files, External Integrations
```

### 3.1 Entry Points

Entry points receive requests and hand work to application services. They should not contain domain logic.

### 3.2 Application Services

Application services coordinate use cases, authorization, transactions, domain services, event publication, and response models.

### 3.3 Domain Modules

Domain modules contain rules and owned models for Districts and Platform concerns.

### 3.4 Repositories

Repositories isolate persistence behavior and enforce ownership boundaries.

### 3.5 Infrastructure

Infrastructure includes database access, files, mail, queues, notifications, external APIs, caching, search, and deployment-specific adapters.

## 4. Canonical Modules

### Platform Modules

- Identity
- Authentication
- Authorization
- Consent
- Settings
- Notifications
- Audit
- Event Bus
- Media
- Search
- Installation
- Migration
- System Administration

### District Modules

- Hearth
- Chronicle
- Beacon
- Gather
- Health
- Quests
- Companion
- Creator Studio
- Marketplace
- Arena

### Group Modules

- Households
- Organizations

### World Modules

- World Catalog
- World Installation
- World Runtime
- World State
- World Migration
- Event Subscription
- NPC and Story Runtime

## 5. Ownership

Each module owns:

- its domain rules;
- its canonical tables;
- its repositories;
- its application services;
- its internal events;
- its tests;
- its public contracts.

A module should not write directly to another module's tables.

Cross-module changes should occur through:

- application service calls;
- documented internal interfaces;
- versioned Platform Events;
- approved shared Platform services.

## 6. Database Architecture

Koravik uses MySQL or MariaDB through PDO.

Requirements include:

- `utf8mb4` character set;
- compatible collation across related tables;
- InnoDB;
- consistent identifier strategy;
- explicit foreign keys where operationally safe;
- indexed foreign keys;
- UTC storage for timestamps;
- transaction boundaries for multi-record changes;
- soft deletion only where justified;
- migration version tracking;
- no manual production schema drift.

District tables should use clear prefixes or schemas where supported by project conventions.

Examples:

```text
platform_users
platform_roles
platform_permissions
hearth_layouts
chronicle_entries
beacon_pages
gather_events
health_entries
quest_definitions
companion_memories
world_installations
world_states
creator_projects
```

Final naming should follow the accepted database standard.

## 7. Foreign Keys

Foreign keys should enforce real ownership and lifecycle relationships.

Avoid a foreign key where:

- the reference is intentionally external;
- retention rules differ;
- the relation is polymorphic and governed through an approved reference model;
- operational constraints make enforcement unsafe.

Foreign key behavior must be explicit:

- `RESTRICT` for protected records;
- `CASCADE` only when true ownership exists;
- `SET NULL` for optional surviving references.

## 8. Time

All stored timestamps should use UTC.

Presentation converts to the user's timezone.

Recurring schedules should preserve timezone intent separately from calculated UTC instances.

## 9. Identity and Authorization

Authorization should be capability-based. Roles provide capability bundles.

Canonical hierarchy:

```text
Owner
→ Admin
→ Content Creator
→ User
```

Authorization must consider:

- Account role;
- group role;
- resource ownership;
- resource visibility;
- consent;
- District rules;
- World permission;
- action consequence.

## 10. Households and Organizations

Households and Organizations are optional contexts. A user may belong to zero or more supported groups.

Membership records should include:

- group identifier;
- user identifier;
- role;
- status;
- invitation state;
- created and updated timestamps;
- audit information.

Group membership must not replace Account ownership.

## 11. Platform Event Architecture

Platform Events provide controlled asynchronous or decoupled integration.

Each event contract should include:

- event name;
- version;
- producer;
- timestamp;
- actor;
- subject;
- correlation identifier;
- minimal payload;
- privacy classification;
- schema.

Example:

```json
{
  "event": "quest.completed",
  "version": 1,
  "producer": "Quests",
  "occurred_at": "2026-07-27T15:00:00Z",
  "actor_id": "user-reference",
  "subject_id": "quest-reference",
  "correlation_id": "request-reference",
  "payload": {
    "category": "learning"
  }
}
```

Events announce change. They do not replace canonical reads where current accuracy is required.

## 12. Event Delivery

Initial delivery may be transactional and database-backed.

The architecture should support:

- outbox records;
- retry state;
- idempotent consumers;
- dead-letter handling;
- replay where allowed;
- audit history;
- event version compatibility.

## 13. Worlds

World packages are installed content.

The runtime must separate:

- package definition;
- installation record;
- activation;
- user-specific World State;
- World event subscriptions;
- state migration.

Worlds must not write to District tables. Worlds react through approved runtime services.

## 14. World State

World State should be scoped by:

- Account;
- World;
- installation;
- package version;
- schema version.

State may be stored relationally, as validated JSON, or through a hybrid model. Regardless of storage, schemas must be versioned and validated.

## 15. Companion Architecture

Companion should use a mediated access layer.

```text
Companion Request
→ Authorization and Consent
→ Scoped Context Retrieval
→ Model Interaction
→ Proposed Result
→ Approval Gate
→ Owning Service Execution
→ Audit
```

Companion must not receive unrestricted database access. Sensitive context should be minimized and classified.

## 16. Creator Studio Architecture

Creator Studio should store structured content definitions. Ordinary packages must not execute arbitrary server-side code.

Content should be validated against versioned schemas.

Build output should include:

- manifest;
- definitions;
- assets;
- permissions;
- compatibility;
- migrations;
- tests;
- checksums.

## 17. Media

Media services should handle:

- upload validation;
- allowed types;
- size limits;
- safe filenames;
- storage abstraction;
- metadata;
- access control;
- virus or threat scanning where available;
- image transformation;
- deletion;
- retention;
- ownership.

Public media and private media must remain distinct.

## 18. Notifications

Notifications are a shared Platform service. Districts request notifications through a standard interface.

The service owns:

- channels;
- delivery preferences;
- scheduling;
- retries;
- templates;
- delivery state;
- quiet hours;
- user opt-outs.

## 19. Audit

Audit records should capture:

- actor;
- action;
- target;
- timestamp;
- result;
- source;
- correlation identifier;
- privilege context.

Audit records should avoid storing secrets or unnecessary sensitive content.

## 20. Migrations

Migrations must be:

- ordered;
- immutable after release;
- idempotent where practical;
- recorded;
- testable;
- safe for supported hosting;
- capable of reporting failure clearly.

The system administration interface may provide controlled migration execution, but source-controlled migrations remain authoritative.

## 21. Error Handling

Errors should:

- preserve useful context internally;
- avoid exposing secrets;
- use stable response structures;
- log correlation identifiers;
- distinguish validation, authorization, conflict, not-found, and system failures;
- fail safely.

## 22. Security

Baseline requirements:

- secure sessions;
- password hashing through supported PHP APIs;
- CSRF protection;
- output escaping;
- prepared statements;
- rate limiting where appropriate;
- upload controls;
- content security policy where practical;
- secure cookie settings;
- privilege separation;
- audit;
- secret isolation;
- dependency review.

## 23. Testing

Testing should include:

- unit tests for domain rules;
- service tests for use cases;
- repository tests;
- migration tests;
- authorization tests;
- event contract tests;
- World package validation;
- Companion approval tests;
- security tests;
- smoke tests;
- deployment checks.

Critical paths must remain covered even when UI changes.

## 24. Deployment

Koravik must remain deployable to Bluehost shared hosting using PHP 8.3+, Apache, MySQL or MariaDB, Git-based release workflows, and source-controlled migrations.

Deployment should support:

- release directories;
- atomic current-release switching where available;
- environment-specific configuration;
- health checks;
- rollback;
- migration status;
- log access;
- writable directory validation.

## 25. Configuration

Secrets and environment-specific configuration must remain outside source control.

Configuration should distinguish:

- required values;
- optional values;
- safe defaults;
- production-only requirements;
- validation failures.

## 26. Architectural Decision Records

Significant deviations require an ADR.

Examples:

- introducing a new datastore;
- extracting a service;
- changing identifier strategy;
- allowing executable extensions;
- changing event transport;
- changing authentication model;
- changing deployment architecture.

## 27. Prohibited Drift

Do not:

- reintroduce Laravel;
- place business logic in route files;
- let Districts write directly to one another's tables;
- let Worlds bypass event permissions;
- give Companion unrestricted data access;
- use manual production-only schema changes;
- duplicate Platform services inside Districts;
- treat Households as mandatory;
- combine World State with Platform truth.
