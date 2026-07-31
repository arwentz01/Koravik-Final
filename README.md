# Koravik Final

> An operating system for intentional living.

Koravik is a human-centered life platform where useful real-world tools can optionally influence persistent narrative Worlds.

This repository is the clean-start authority for the final Koravik implementation. It does not inherit application code, database state, build numbers, deployment assumptions, or implementation claims from Koravik v1, Koravik v2, Koravik v3, prototypes, or related projects.

## Current status

**Blueprint v1.0 complete. Healing Home Deepening in the current working tree.**

The platform now proves secure identity, Quests-owned real-life action, Chronicle preservation, Companion proposals and consent, explainable persistent World State, installed World lifecycle, and a bounded first-use journey that lets a new person begin without forced commitments.

The current implementation includes secure personal participation, Worlds and Companion consent, Gather and Beacon operations, optional Organization operating spaces, private optional Households, accessibility personalization, workflow recovery, Hearth Daily Focus, a story-first Worlds Home with durable reaction review, navigable Healing Home rooms, explicit room presence, private room notes, an Eastern Room that opens from Epic Ordinary Chapter Two, a bounded Caretaker conversation inside Healing Home relationship memory, a stateful Healing Home room map, Fireplace reaction explainability, a provenance-aware Keepsake Shelf, a Journal Table bridge into Chronicle, and an expanded Healing Home with Workshop, Library, Guest Room, Garden tending, atmosphere, timeline, intention-label, return-scene, privacy/explainability flows, symbolic room identities, arrival-scene intrigue, natural room-to-room movement, richer door states, a house pulse, room-specific depth, ambient empty states, source-thread explainability, house resonance paths, room practices, a non-prescriptive house guide, Today in the House, a room directory, a source glossary, house invitations, thresholds, a house atlas, room lore, house constellations, a boundary ledger, and wayfinding.

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

## Run locally

1. Copy `.env.example` to `.env` and configure the database.
2. Run `php tools/migrate.php`.
3. Set `SEED_EMAIL`, `SEED_PASSWORD`, and optionally `SEED_DISPLAY_NAME`.
4. Run `php tools/seed.php`.
5. Point Apache at `public/`, or run `php -S 127.0.0.1:8080 -t public public/index.php`.
6. Run `php tools/worker.php 10` from cron for bounded outbox delivery.

## Current product loop

1. A person creates an account or signs in securely.
2. First use explains Koravik without forcing a Quest, World, or Companion memory.
3. Hearth provides one calm point of orientation.
4. District-owned real-life actions may publish minimized committed facts.
5. Authorized Worlds interpret those facts into independent, explainable fiction.
6. Chronicle preserves meaningful moments without becoming a scorecard.
7. The person can leave and later resume safely.

## Rule zero

If approved blueprint and implementation disagree, the blueprint is correct until it is deliberately revised through the documented change process.

The blueprint is a product. The software is its implementation.
