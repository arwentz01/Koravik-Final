# Koravik-Final Implementation Handoff

**Status:** Approved — Build 001 Authorized  
**Version:** 1.0  
**Baseline date:** July 28, 2026  
**Authoritative branch:** `main`

## Repository authority

This handoff applies only to `arwentz01/Koravik-Final`.

Do not import or infer code, schemas, migrations, routes, build numbers, completion claims, deployment assumptions, or framework conventions from Koravik v1, v2, v3, prototypes, Chuckleberry, Cross Current, Laravel Master, or prior repositories.

Historical material may explain product discovery but is not implementation authority.

## Mandatory reading order

Before proposing or writing implementation code, read:

1. `README.md`
2. `docs/README.md`
3. `docs/FOUNDATIONAL_DECISIONS.md`
4. all files under `docs/canonical/`
5. `docs/project-zero/README.md` and the Project Zero documents it indexes
6. all files under `docs/product/`
7. all files under `docs/engineering/`
8. `docs/adr/ADR-REGISTER.md`
9. this handoff

When documents disagree, follow the authority order in `docs/README.md`. Stop and resolve material contradictions before implementation.

## Architecture baseline

Koravik-Final is a PHP 8.3+ custom modular monolith using MySQL or MariaDB through PDO and an Apache-compatible shared-hosting deployment model.

Key boundaries:

- Districts own real-life truth.
- Hearth composes but does not own source records.
- Worlds interpret approved minimized facts into independent World State.
- Companion proposes; humans approve consequential actions.
- Platform events describe committed facts and use a transactional outbox.
- Authorization is capability-based and contextual.
- Server-rendered accessible HTML is the baseline.
- Background work is finite, idempotent, lock-safe, and cron compatible.

## Blueprint v1.0 disposition

The Project Zero product definition, product interaction specifications, application shell, component contracts, user flows, screen catalog, Epic Ordinary art bible, technical architecture, database conventions, event catalog, API specification, security model, testing strategy, deployment standard, module ownership map, and initial ADR register are approved as the implementation baseline.

The blueprint is authoritative. Implementation may clarify details but may not silently redefine the product or architecture.

## Build 001 — Foundation and first vertical slice

### Mission

Create the smallest working version of Koravik that already feels like Koravik.

### Required journey

1. A person signs in securely.
2. The shared application shell loads.
3. Hearth provides a calm daily orientation surface.
4. The person opens one Quests-owned action.
5. The person completes the action.
6. The source transaction records the completion and `Quests.QuestCompleted.v1` in the outbox atomically.
7. A bounded worker delivers the event idempotently.
8. Epic Ordinary interprets the approved fact.
9. Independent World State changes.
10. The person sees the reaction and why it occurred.
11. The person returns to Hearth.
12. The person can leave and later resume without losing context.

### Build 001 in scope

- minimal application bootstrap and configuration;
- database connection and migration runner;
- Account, authentication, session, and authorization foundation;
- shared shell and design tokens required for the journey;
- minimal Hearth composition;
- minimal Quests model and completion service;
- transactional outbox and finite delivery command;
- minimal World installation/runtime/state support for Epic Ordinary;
- explainability record and visible reaction;
- audit and structured logging required by the flow;
- automated tests and acceptance checks for the full journey.

### Explicitly out of scope

- broad District implementation;
- Households and Organizations beyond abstractions required for future compatibility;
- marketplace payments;
- arbitrary creator code;
- mobile applications;
- broad public API;
- generalized AI execution;
- comprehensive search, notifications, or media systems;
- multiple production Worlds;
- engagement metrics, streak pressure, or social feeds.

## Acceptance criteria

Build 001 is accepted only when:

- the required journey works end to end;
- module ownership and database boundaries are preserved;
- event publication is atomic and consumption is idempotent;
- World State is independent and explainable;
- no consequential Companion action bypass exists;
- authentication, authorization, CSRF, validation, audit, and privacy checks pass;
- keyboard, focus, responsive, reduced-motion, and screen-reader expectations are reviewed;
- migrations work from an empty database;
- finite worker execution is shared-host safe;
- tests defined by the testing strategy pass;
- relevant documentation is updated;
- no implementation claim is inherited from another repository.

## Build workflow

For every build:

1. inspect current `main`;
2. read this handoff and affected authority documents;
3. state the player-visible outcome and technical boundary;
4. produce an implementation plan;
5. implement one coherent vertical slice;
6. run relevant tests and manual reviews;
7. update documentation and this handoff;
8. commit a cohesive milestone.

## Change control

When implementation reveals a blueprint flaw, stop at the affected boundary. Record an ADR or update the authoritative document, review the consequences, and resume only after the direction is explicit.

## Authorization

Blueprint v1.0 has passed the documentation completion gate. Build 001 is authorized from the current `main` baseline.

The next repository work should be implementation planning for Build 001, not additional speculative blueprint expansion.