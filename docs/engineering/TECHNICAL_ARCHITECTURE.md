# Technical Architecture

**Status:** Draft for Blueprint v1.0 review  
**Version:** 1.0  
**Owner:** Koravik Architecture  
**Last reviewed:** 2026-07-28

## Purpose

This document translates Koravik's canonical architecture into an implementation contract for Koravik-Final. It does not replace `docs/canonical/ARCHITECTURE.md`; it narrows that direction into concrete engineering boundaries for Build 001 and the builds that follow.

## Architectural style

Koravik-Final is a custom PHP 8.3+ modular monolith deployed on Apache-compatible shared hosting.

The platform must remain understandable as one application while preserving explicit module ownership, public contracts, and test boundaries strong enough to support future extraction only when justified.

The architecture must not introduce Laravel, Laravel conventions, framework-owned domain behavior, permanent queue workers, hidden cross-module writes, or arbitrary executable World packages.

## Governing principles

1. Product experience defines the required system behavior.
2. Modules own their domain state and rules.
3. Application services coordinate use cases and transactions.
4. Cross-module communication uses documented interfaces or versioned Platform Events.
5. Security, privacy, accessibility, observability, and recovery are part of the first implementation.
6. Shared-hosting constraints are architectural inputs, not afterthoughts.
7. Explainability is required for consequential automation and World reactions.
8. Simplicity is preferred over infrastructure prestige.

## Runtime topology

```text
Browser
  |
Apache / PHP entry point
  |
HTTP Kernel and Router
  |
Middleware pipeline
  |
Application Service
  |
Domain Module
  |
Repository / Platform Service
  |
MySQL or MariaDB, filesystem, mail, external adapters

Cron or bounded request-driven runner
  |
Outbox delivery, delayed consequences, retries, maintenance
```

The default deployment is one web application and one relational database. Background processing must use finite, restartable jobs invoked by cron or an explicitly bounded request-driven runner.

## Repository structure

The initial implementation should converge on this structure:

```text
/public
    index.php
    assets/
/src
    /Platform
        /Http
        /Identity
        /Authentication
        /Authorization
        /Consent
        /Audit
        /Events
        /Notifications
        /Settings
        /Database
        /Migrations
        /Support
    /Districts
        /Hearth
        /Quests
        /Chronicle
        /Health
        /Gather
        /Beacon
        /Companion
    /Worlds
        /Catalog
        /Installation
        /Runtime
        /State
        /Subscriptions
    /WorldPacks
        /EpicOrdinary
/templates
/config
/database/migrations
/storage
/tests
/tools
/docs
```

Directory names may be refined before Build 001, but module boundaries and ownership must remain explicit.

## Layer responsibilities

### HTTP entry points

Controllers or request handlers:

- parse and validate transport-level input;
- invoke an application service;
- translate results into responses;
- never contain domain rules;
- never write directly to repositories belonging to another module.

### Middleware

Middleware may provide:

- session establishment;
- CSRF protection;
- authentication checks;
- capability enforcement;
- request IDs and correlation IDs;
- rate limiting;
- locale and timezone context;
- security headers;
- structured request logging.

Middleware must not silently mutate domain state.

### Application services

Application services own use-case orchestration. They may:

- check authorization and consent;
- start and commit transactions;
- invoke domain services and repositories;
- create audit records;
- append outbox events;
- return presentation-safe result objects.

A user-visible action that changes multiple records must have one explicit transaction boundary.

### Domain modules

Each module owns:

- domain rules;
- canonical tables;
- entities and value objects;
- repositories;
- application services;
- internal events;
- public interfaces;
- tests;
- data retention and deletion behavior.

A module must not depend on another module's internal classes, tables, or undocumented behavior.

### Repositories

Repositories isolate persistence. They must:

- use prepared PDO statements;
- expose domain-oriented operations rather than generic table access;
- enforce module ownership;
- support transactions supplied by the application layer;
- avoid returning raw database rows beyond infrastructure boundaries.

### Platform services

Shared platform capabilities include identity, authentication, authorization, consent, audit, events, notifications, settings, media, search, migrations, and system administration.

A Platform service should exist only where the behavior is genuinely cross-cutting. District-specific behavior must not be moved into Platform merely for convenience.

## Initial module boundaries

### Identity

Owns the durable Account identity and account lifecycle. “Player” is a contextual role while experiencing a World, not a second identity model.

### Authentication

Owns credentials, sessions, password reset, sign-in throttling, and account recovery.

### Authorization

Owns capabilities, role bundles, policy evaluation, and denial explanations. Roles begin with Owner, Admin, Content Creator, and User, but authorization decisions must evaluate capabilities rather than hard-coded rank alone.

### Consent

Owns explicit permissions for sensitive event use, World subscriptions, Companion access, integrations, and revocation.

### Audit

Owns immutable security and consequential-action records. Audit data is not a substitute for domain history.

### Hearth

Owns Account-specific orientation layout and acknowledgement state. Hearth composes information from owning modules but does not own their source records.

### Quests

Owns tasks, projects, responsibilities, recurring actions, completion state, and the initial real-life action used by the first vertical slice.

### Worlds

Own World catalog metadata, installations, active selection, runtime interpretation, independent World State, subscriptions, and delayed narrative commitments.

### Epic Ordinary

Epic Ordinary is the reference World package and must use the same structured contracts available to future Worlds. It may not receive privileged direct access to District data.

## Dependency rules

Allowed dependency direction:

```text
Entry Points -> Application Services -> Domain Interfaces -> Infrastructure
```

Additional rules:

- Domain code must not depend on HTTP concepts.
- Districts may depend on approved Platform interfaces.
- Worlds may consume approved Platform Event contracts and World runtime interfaces.
- Platform must not depend on a specific World.
- Epic Ordinary must not be embedded inside Quests, Hearth, or the event bus.
- Templates receive view models, not repositories or database connections.
- Static helper access to global application state is prohibited except for narrowly approved bootstrap facilities.

## Request lifecycle

A normal authenticated request follows this sequence:

1. Generate or accept a correlation ID.
2. Establish secure session context.
3. Parse the route and request method.
4. Apply middleware and capability checks.
5. Validate input.
6. Invoke one application service.
7. Execute domain work inside an explicit transaction where required.
8. Persist the domain change and any outbox record atomically.
9. Commit.
10. Render a response or redirect using a presentation model.
11. Record structured operational telemetry without exposing sensitive payloads.

## First vertical slice transaction

Completing the reference Quest must atomically:

1. verify the Account may complete the Quest;
2. verify the Quest is eligible and not already completed for the same occurrence;
3. store the Quests-owned completion record;
4. append one minimized, versioned outbox event;
5. create an appropriate audit record when the action is consequential;
6. commit all writes together.

World interpretation occurs after commit through the outbox runner. The Quest completion must remain successful even when World processing is temporarily unavailable.

## Event processing

The event system uses a transactional outbox.

The outbox runner must:

- claim a bounded number of eligible records;
- avoid concurrent duplicate processing;
- deliver to declared consumers;
- record attempts and outcomes;
- retry transient failures with bounded backoff;
- dead-letter records after a defined maximum;
- support safe replay;
- preserve original event identity;
- never run indefinitely.

Consumers must maintain idempotency records or otherwise prove duplicate-safe behavior.

## World runtime

The World runtime evaluates structured content and rules, not arbitrary creator-supplied PHP or JavaScript.

A World package may define:

- manifest and compatibility metadata;
- declared event subscriptions;
- permissions and content warnings;
- story nodes and branches;
- dialogue;
- NPC relationship dimensions;
- World Quests and objectives;
- rewards;
- delayed consequences;
- presentation assets and accessibility metadata;
- versioned state migrations expressed through approved structured transforms.

World packages must not:

- execute arbitrary server code;
- query District tables;
- access events outside declared and approved subscriptions;
- write real-life records;
- bypass consent;
- hide consequential reactions from the person.

## Presentation architecture

The application shell is server-rendered by default with progressive enhancement.

Requirements:

- core journeys work without client-side routing;
- JavaScript enhances interactions but does not own canonical state;
- forms remain operable with standard HTTP submission;
- dynamic updates preserve focus and announce changes accessibly;
- responsive layout follows `docs/product/APPLICATION_SHELL.md`;
- component behavior follows `docs/product/COMPONENT_LIBRARY.md`.

## Configuration

Configuration must be environment-specific and validated during bootstrap.

Secrets must remain outside version control. Required settings include:

- application environment;
- base URL;
- database connection;
- session and cookie settings;
- mail transport;
- encryption keys;
- cron or worker token where needed;
- logging level;
- storage paths.

Production must fail closed when required secrets or security settings are absent.

## Error handling

Errors are classified as:

- validation errors;
- authorization or consent denials;
- domain conflicts;
- not-found outcomes;
- transient infrastructure failures;
- unexpected system failures.

User-facing errors must be plain-language and recoverable where possible. Internal logs must include request and correlation IDs but must not include passwords, tokens, private Chronicle text, raw health details, or unrestricted event payloads.

## Observability

Minimum observability includes:

- structured application logs;
- request and correlation IDs;
- migration history;
- outbox metrics and dead-letter visibility;
- failed sign-in and authorization telemetry;
- audit history for consequential actions;
- World reaction provenance;
- health checks that do not expose secrets.

## Security baseline

Before public deployment, the implementation must include:

- password hashing using current PHP password APIs;
- secure, HTTP-only, same-site session cookies;
- session rotation after authentication and privilege changes;
- CSRF protection for state-changing browser requests;
- prepared SQL statements;
- contextual output encoding;
- upload validation and non-executable storage;
- capability checks at the application boundary;
- rate limiting for authentication and sensitive actions;
- encryption or keyed protection for sensitive secrets where applicable;
- auditable consent and revocation;
- restrictive security headers.

The detailed security contract belongs in `SECURITY_MODEL.md`.

## Performance boundaries

The initial platform should favor correctness and predictable operation over premature caching.

Requirements:

- avoid unbounded queries and event batches;
- paginate growing collections;
- index ownership, status, due-time, and event-processing columns;
- cache only derived or safely reproducible data;
- keep Hearth composition bounded;
- lazy-load nonessential secondary content;
- test the first vertical slice under realistic shared-host constraints.

## Testing boundaries

Each build must include:

- unit tests for domain rules;
- integration tests for repositories and transactions;
- application tests for authorization, consent, and use cases;
- HTTP tests for primary routes and recovery states;
- event contract and idempotency tests;
- accessibility checks for the delivered journey;
- migration tests from a clean database and from the prior accepted schema.

## Build 001 technical scope

Build 001 may establish only the foundation needed to support the walking skeleton:

- bootstrap and configuration;
- database connection and migration runner;
- HTTP routing and middleware foundation;
- session and authentication foundation;
- capability authorization foundation;
- module registration;
- structured logging and error handling;
- test harness;
- shared application shell foundation;
- outbox persistence and finite runner skeleton.

Build 001 must not expand into broad District functionality, a general creator marketplace, social features, or speculative integrations.

## Architecture acceptance questions

Before implementation is accepted, reviewers must be able to answer:

1. Which module owns every changed record?
2. Where is the transaction boundary?
3. What public contract crosses the module boundary?
4. What capability and consent checks apply?
5. How is duplicate execution prevented?
6. How does the user understand the result?
7. How does the operation recover from interruption or infrastructure failure?
8. Can the system run safely on shared hosting without a permanent worker?
9. Does the implementation preserve accessibility and privacy?
10. Is the solution simpler than the alternatives while meeting the blueprint?
