# Koravik-Final Implementation Handoff

**Status:** Approved — Build 001 Complete  
**Version:** 1.1  
**Baseline date:** July 28, 2026  
**Authoritative branch:** `main`  
**Completed checkpoint:** `7c17d997869c2ef7059731c6ab360424c2182d4d`

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

### Status

**Complete and merged to `main`.**

### Delivered journey

1. A person signs in through password-hashed credentials and a rotated secure session.
2. The shared application shell loads with accessible navigation and focus behavior.
3. Hearth provides a calm daily orientation surface.
4. The person opens one Quests-owned action.
5. The person completes the action.
6. The source transaction records the completion, audit entry, and `Quests.QuestCompleted.v1` outbox event atomically.
7. A finite worker claims and delivers the event with retry and dead-letter behavior.
8. Epic Ordinary interprets only the approved minimized fact.
9. Independent World State and a durable reaction are recorded.
10. The person sees the reaction and its provenance.
11. The person returns to Hearth and can later resume from stored state.

### Implemented foundation

- environment-aware PHP bootstrap and autoloading;
- PDO connection with native prepared statements and explicit transactions;
- versioned MySQL/MariaDB migration runner;
- safe seed command requiring explicit credentials;
- session authentication, logout, CSRF, and security headers;
- responsive server-rendered shell and visual tokens;
- minimal Hearth and Quests surfaces;
- atomic Quest completion and outbox publication;
- bounded outbox worker with retries, dead-letter state, and idempotent receipts;
- Epic Ordinary installation, state, reaction, and explainability records;
- audit record for the completed action;
- Apache front controller and health endpoint;
- GitHub Actions validation against an empty MySQL database.

### Validation

The Build 001 workflow passed on the accepted head commit and verified:

- PHP 8.3 syntax for every PHP file;
- migration from an empty MySQL 8.4 database;
- safe account, Quest, and World seeding;
- health response;
- sign-in and session persistence;
- Hearth orientation;
- Quest detail and completion;
- visible Epic Ordinary reaction and explanation;
- idempotent worker execution after delivery.

## Deferred scope

Build 001 intentionally did not implement:

- broad District functionality;
- Households or Organizations;
- generalized account administration or password recovery;
- Companion execution;
- marketplace or creator publishing;
- multiple production Worlds;
- broad public APIs;
- comprehensive search, notifications, or media;
- mobile applications;
- deployment automation for a specific production host.

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

## Next authorization

Build 001 is complete. The next repository work should define and approve the narrow player-visible outcome for Build 002 before implementation begins. Build 002 must extend the working product rather than replacing or bypassing the accepted vertical slice.
