# Koravik-Final Implementation Handoff

**Status:** Approved — Build 002 Complete  
**Version:** 1.2  
**Baseline date:** July 28, 2026  
**Authoritative branch:** `main`  
**Completed checkpoint:** `87e1354db20e0e9de9a7441462ba1dbc219701e5`

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

**Status:** Complete and merged to `main`.

Delivered secure sign-in, the shared shell and Hearth, seeded Quest completion, atomic `Quests.QuestCompleted.v1` publication, bounded idempotent outbox delivery, independent Epic Ordinary World State, visible explainability, audit history, shared-host routing, migration and seed tools, and end-to-end MySQL validation.

**Checkpoint:** `7c17d997869c2ef7059731c6ab360424c2182d4d`

## Build 002 — Personal Quest creation

**Status:** Complete and merged to `main`.

### Player-visible outcome

A signed-in person can now:

1. open a dedicated Quests area;
2. create a personal Quest using only a title;
3. optionally add notes;
4. receive clear inline validation without losing entered text;
5. see the saved Quest immediately in Quests and Hearth;
6. open and complete the user-created Quest;
7. receive the existing explainable Epic Ordinary reaction;
8. leave and later resume from durable state.

### Implemented boundaries

- Quests remains the sole owner of Quest records and validation.
- A Quest does not require a Household, Organization, World, category, reward, due date, recurrence, or project.
- Quest creation records a `quest.created` audit entry.
- Quest creation does not publish a Platform Event.
- Quest completion continues to atomically publish the approved minimized fact.
- Existing Build 001 tables were sufficient; no schema migration was needed.
- Navigation, empty states, form behavior, flash messages, and responsive presentation were extended without replacing the accepted shell.

### Validation

The Build 002 workflow passed and verified:

- PHP 8.3 syntax;
- clean MySQL migration and seed;
- eight-character seed password support;
- authentication and session persistence;
- personal Quest creation with title and optional notes;
- validation failure for an empty title with HTTP 422;
- durable Quest list and detail display;
- completion of the newly created Quest;
- Epic Ordinary reaction and explanation;
- idempotent worker execution after delivery.

**Checkpoint:** `87e1354db20e0e9de9a7441462ba1dbc219701e5`

## Deferred scope

The accepted product still does not include:

- Quest editing, archiving, deletion, recurrence, due guidance, projects, or assignments;
- generalized account administration or password recovery;
- Households or Organizations;
- broad District functionality;
- Companion execution;
- marketplace or creator publishing;
- multiple production Worlds;
- broad public APIs;
- comprehensive search, notifications, or media;
- mobile applications;
- host-specific deployment automation.

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

Build 002 is complete. The next repository work should define a narrow Build 003 outcome that deepens the useful personal Quest lifecycle or adds another approved product flow without weakening the accepted architecture.
