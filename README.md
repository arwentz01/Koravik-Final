# Koravik Final

> An operating system for intentional living.

Koravik is a human-centered life platform where useful real-world tools can optionally influence persistent narrative Worlds.

This repository is the clean-start authority for the final Koravik implementation. It does not inherit application code, database state, build numbers, deployment assumptions, or implementation claims from Koravik v1, Koravik v2, Koravik v3, prototypes, or related projects.

## Current status

**Blueprint v1.0 complete. Build 001 authorized.**

Project Zero and the product and engineering blueprint are complete. The next work is implementation of the first approved vertical slice, not further speculative product definition.

## Documentation entry points

- [Documentation authority map](docs/README.md)
- [Foundational decisions](docs/FOUNDATIONAL_DECISIONS.md)
- [Canonical product documents](docs/canonical/)
- [Project Zero](docs/project-zero/README.md)
- [Product Blueprint](docs/product/)
- [Engineering Blueprint](docs/engineering/)
- [Architecture Decision Register](docs/adr/ADR-REGISTER.md)
- [Implementation Handoff](docs/IMPLEMENTATION_HANDOFF.md)
- [Legacy references](docs/archive/)

Before writing code, read the mandatory documents in the order specified by the implementation handoff.

## Implementation direction

- PHP 8.3+
- Custom modular monolith
- MySQL or MariaDB through PDO
- Apache and shared-hosting compatible
- Server-rendered accessible interface with progressive enhancement
- Database-backed transactional outbox with finite cron processing
- Capability-based contextual authorization
- Versioned migrations and platform events
- Structured, non-executable World packages
- Visible privacy, consent, audit, and explainability

## Build 001 mission

Create the smallest working version of Koravik that already feels like Koravik.

The first implementation milestone must prove:

1. A person signs in securely.
2. The shared application shell and Hearth provide orientation.
3. Quests records one real-life action.
4. The committed change publishes `Quests.QuestCompleted.v1` through the transactional outbox.
5. Epic Ordinary interprets the approved fact.
6. Independent World State changes with an explainable reason.
7. The person returns to Hearth, leaves, and later resumes safely.

## Rule zero

If approved blueprint and implementation disagree, the blueprint is correct until it is deliberately revised through the documented change process.

The blueprint is a product. The software is its implementation.