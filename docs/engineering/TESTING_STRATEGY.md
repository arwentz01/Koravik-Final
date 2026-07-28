# Testing Strategy

**Status:** Approved for Blueprint v1.0  
**Version:** 1.0  
**Owner:** Engineering Quality

## Objective

Testing proves that Koravik works as a coherent product, preserves domain boundaries, protects users, and remains deployable on its supported environment.

## Test layers

### Unit tests

Cover pure domain rules, value objects, authorization decisions, event transformations, package validation, recurrence rules, and other deterministic logic.

### Module integration tests

Cover application services with real repositories or realistic database fixtures, including transactions, constraints, ownership, audit, consent, and outbox writes.

### Contract tests

Cover module interfaces, event envelopes and schemas, World package contracts, APIs, migration expectations, and registered component behavior.

### Journey tests

Cover complete user outcomes through the application surface. The first required journey is:

Sign in → Hearth → Quest → Complete → Outbox → Epic Ordinary reaction → Explainability → Return and resume.

### Accessibility tests

Automated checks are necessary but insufficient. Required manual checks include keyboard-only operation, focus order, focus visibility, landmarks, headings, labels, error association, reduced motion, zoom and reflow, screen-reader announcements, and contrast.

### Security tests

Every consequential feature includes positive and negative authorization tests, CSRF behavior, validation, output safety, rate-limit behavior where applicable, sensitive-data minimization, and audit expectations.

### Migration tests

Migrations are tested from an empty database and from the previous supported schema version. Tests verify repeatability, failure behavior, data preservation, constraints, and migration bookkeeping.

### Operational tests

Cover health checks, cron-safe workers, bounded batches, retry and dead-letter behavior, backup verification, restoration procedures, logging, and failure without permanent processes.

## Test data

Tests use synthetic data only. Fixtures should be readable, minimal, deterministic, and owned by the module under test. Sensitive production data must never be copied into ordinary development or CI environments.

## Isolation and determinism

Tests control time, randomness, identifiers, external services, and queues. Tests must not depend on execution order or permanent local state.

## CI gates

Every proposed implementation change must pass:

- syntax and static checks available to the project;
- unit and module tests;
- architecture-boundary checks;
- migration checks when schema changes exist;
- relevant journey tests;
- accessibility checks for changed surfaces;
- security checks appropriate to the change.

A skipped required test is a failure unless the reason is explicit and approved.

## Build acceptance

Each build defines player-visible outcomes and production acceptance boundaries before coding. Completion requires:

1. documented scope;
2. passing automated tests;
3. manual interaction review;
4. responsive review;
5. accessibility review;
6. authorization and privacy review;
7. migration and rollback assessment;
8. deployment validation;
9. documentation and handoff updates.

## Regression policy

A defect receives a regression test at the lowest useful layer. Repeated failures should prompt a boundary, design, or tooling correction rather than a growing pile of brittle end-to-end tests.

## Performance

Performance tests focus on user-visible latency, query counts, bounded event processing, pagination, large-account behavior, and shared-host resource limits. Performance must not be optimized by weakening correctness, privacy, or accessibility.

## First-slice minimum suite

Build 001 must prove:

- secure account creation or seeded sign-in path;
- session and authorization behavior;
- Hearth rendering and empty state;
- Quest creation or approved seed, viewing, and completion;
- transactional outbox publication;
- idempotent World consumption;
- durable Epic Ordinary state change;
- explainability record;
- interruption and resume;
- keyboard and screen-reader viability;
- safe failure and retry.