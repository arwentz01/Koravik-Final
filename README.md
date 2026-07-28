# Koravik Final

Koravik is a human-centered life platform where useful real-world tools can
optionally influence persistent narrative Worlds.

This repository is the clean-start authority for the final Koravik
implementation. It does not inherit application code, database state, build
numbers, deployment assumptions, or implementation claims from Koravik v1,
Koravik v2, Koravik v3, prototypes, or related projects.

## Current status

Documentation foundation only. No product implementation has begun.

## Documentation

- [Documentation authority map](docs/README.md)
- [Foundational decisions](docs/FOUNDATIONAL_DECISIONS.md)
- [Canonical product documents](docs/canonical/)
- [Legacy reference documents](docs/archive/)

## Initial implementation direction

- PHP 8.3+
- Custom modular monolith
- MySQL or MariaDB through PDO
- Apache and shared-hosting compatible
- Database-backed outbox with finite cron processing
- Capability-based authorization
- Versioned migrations and platform events
- Structured, non-executable World packages
- Accessible shared interface from the beginning

The first implementation milestone must prove the defining Koravik loop:

1. A person securely owns one durable Account.
2. A District records a real-life action.
3. The committed change publishes a minimized platform event.
4. Epic Ordinary interprets the event.
5. Independent World State changes with an explainable reason.
6. The person can leave, return, and resume.
