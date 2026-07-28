# Documentation Authority

**Status:** Approved for Blueprint v1.0  
**Version:** 1.1

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
13. `IMPLEMENTATION_HANDOFF.md` for current authorized scope and execution order
14. repository code, migrations, and tests for deliberately implemented behavior

The implementation handoff may narrow current work but may not override higher-order product or architecture decisions. Historical implementation claims do not override this repository.

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
- `canonical/COMPANION_GOVERNANCE.md` when Companion proposals or execution are affected

### Project Zero

`project-zero/` records the completed discovery phase, North Star, design manifesto, anti-patterns, product experience, design system, build charter, and blueprint completion gate.

### Product Blueprint

`product/` defines interaction behavior, the application shell, components, user flows, screen inventory, Epic Ordinary direction, and `COMPANION_PROPOSALS.md` for proposal review, approval, execution, and reflection behavior.

### Engineering Blueprint

`engineering/` defines the technical architecture, database and migration conventions, platform-event catalog, API rules, security and privacy model, testing strategy, deployment standard, module ownership, and `COMPANION_EXECUTION.md` for owner-revalidated proposal execution.

### Decisions

`adr/ADR-REGISTER.md` records accepted decisions. Future material changes require a new ADR that supersedes an existing decision rather than silently rewriting history.

### Implementation authority

`IMPLEMENTATION_HANDOFF.md` is the entry point for implementation work. It lists mandatory reading, current architecture, completed checkpoints, acceptance criteria, and explicit exclusions.

## Document status

Authoritative documents use one of these statuses:

- Draft
- Review
- Approved
- Frozen
- Superseded
- Archived

Approved documents govern implementation. Frozen documents require explicit change control. Superseded and archived documents remain historical only.

## Change control

When implementation reveals a contradiction or flaw:

1. stop at the affected boundary;
2. identify the controlling document;
3. record a new ADR or deliberate document revision;
4. review product, security, migration, and compatibility consequences;
5. update the implementation handoff;
6. resume only when the direction is explicit.

## Archived documents

The `archive/` directory is non-authoritative.

- `ROADMAP_LEGACY.md` records historical build claims and must not guide Koravik-Final.
- `IMPLEMENTATION_HANDOFF_LEGACY.md` points to an earlier implementation and must not be used to begin work.

## Current state

Project Zero and Blueprint v1.0 remain authoritative. Builds 001 through 014 are merged, and Build 015 implements owner-revalidated Companion execution and Chronicle reflection proposals. Begin implementation work from `IMPLEMENTATION_HANDOFF.md` and read the focused Companion contracts whenever Companion scope is involved.