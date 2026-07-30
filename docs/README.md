# Documentation Authority

**Status:** Approved for Blueprint v1.0  
**Version:** 1.4

## Authority order

When documents disagree, use this order:

1. `FOUNDATIONAL_DECISIONS.md`
2. `canonical/CONSTITUTION.md`
3. `canonical/CHARTER.md`
4. accepted architecture decision records in `adr/`
5. `canonical/ARCHITECTURE.md`
6. `canonical/DOMAIN_MODEL.md`
7. `canonical/WORLD_ENGINE.md`
8. `canonical/EVENT_PHILOSOPHY.md`
9. `canonical/DEFINITION_OF_DONE.md`
10. focused canonical contracts, including `canonical/COMPANION_GOVERNANCE.md`
11. approved Product Blueprint documents in `product/`
12. approved Engineering Blueprint documents in `engineering/`
13. `IMPLEMENTATION_HANDOFF.md`
14. repository code, migrations, and tests for deliberately implemented behavior

The implementation handoff may narrow current work but may not override higher-order decisions. Historical implementation claims do not override this repository.

## Mandatory blueprint groups

### Foundational

- `FOUNDATIONAL_DECISIONS.md`
- `canonical/CONSTITUTION.md`
- `canonical/CHARTER.md`
- `canonical/ARCHITECTURE.md`
- `canonical/DOMAIN_MODEL.md`
- `canonical/WORLD_ENGINE.md`
- `canonical/EVENT_PHILOSOPHY.md`
- `canonical/DEFINITION_OF_DONE.md`
- `canonical/COMPANION_GOVERNANCE.md` when Companion scope is affected

### Project Zero

`project-zero/` records discovery, the North Star, design manifesto, anti-patterns, product experience, design system, build charter, and blueprint completion gate.

### Product Blueprint

`product/` defines interaction behavior, shell, components, user flows, screen inventory, Epic Ordinary direction, Companion proposals, consent-scoped Companion context and memory, Chronicle ownership, visual placement, installed World lifecycle, and first-use orientation.

Focused contracts include:

- `product/FIRST_USE_ORIENTATION.md`
- `product/COMPANION_PROPOSALS.md`
- `product/COMPANION_CONTEXT_AND_MEMORY.md`
- `product/CHRONICLE_OWNERSHIP.md`
- `product/VISUAL_INFORMATION_ARCHITECTURE.md`
- `product/ROUTE_VISUAL_INVENTORY.md`
- `product/EPIC_ORDINARY_CHAPTER_TWO.md`
- `product/WORLD_PROGRESS_AND_REACTIONS.md`
- `product/INSTALLED_WORLD_LIFECYCLE.md`

### Engineering Blueprint

`engineering/` defines architecture, database and migration conventions, events, API rules, security, testing, deployment, ownership, execution, lifecycle recovery, account-data handling, visual rendering, and World runtime boundaries.

Focused contracts include:

- `engineering/COMPANION_EXECUTION.md`
- `engineering/COMPANION_LIFECYCLE.md`
- `engineering/ACCOUNT_DATA_LIFECYCLE.md`
- `engineering/AUTHENTICATION_RECOVERY.md`
- `engineering/RELEASE_CANDIDATE_CHECKLIST.md`
- `engineering/VISUAL_SYSTEM.md`
- `engineering/EPIC_ORDINARY_RUNTIME.md`

### Decisions

`adr/ADR-REGISTER.md` records accepted decisions. Material changes require a new ADR or deliberate document revision rather than silent historical rewriting.

### Implementation authority

`IMPLEMENTATION_HANDOFF.md` is the entry point for implementation work. It lists mandatory reading, current architecture, completed checkpoints, migrations, and explicit boundaries.

## Document status

Authoritative documents use Draft, Review, Approved, Frozen, Superseded, or Archived status. Approved documents govern implementation. Frozen documents require explicit change control.

## Change control

When implementation reveals a contradiction or flaw:

1. stop at the affected boundary;
2. identify the controlling document;
3. record an ADR or deliberate revision;
4. review product, security, migration, and compatibility consequences;
5. update the implementation handoff;
6. resume only when direction is explicit.

## Repository standards

- `CODING_STANDARDS.md`
- `DATABASE_STANDARDS.md`
- `DEFINITION_OF_DONE.md`
- `DEPLOYMENT_GUIDELINES.md`
- `PUBLISHING_STANDARDS.md`

These standards govern current and future work. Where the existing implementation
does not yet provide an enforcement mechanism—especially automated tests,
migration checksums, and release automation—the gap remains explicit quality
debt and must not be represented as already satisfied.

## Archived documents

The `archive/` directory is non-authoritative. Legacy roadmaps and handoffs must not guide Koravik-Final.

## Current state

Project Zero and Blueprint v1.0 remain authoritative. Builds 001 through 117 are committed on `main`; Worlds Home and Reaction Review is the current implemented vertical product slice.
