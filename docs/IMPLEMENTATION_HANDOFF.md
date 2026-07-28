# Koravik-Final Implementation Handoff

**Status:** Build 006 complete and merged  
**Version:** 1.6  
**Baseline date:** July 28, 2026  
**Authoritative branch:** `main`

## Repository authority

This handoff applies only to `arwentz01/Koravik-Final`. Do not import code, schemas, routes, build numbers, deployment assumptions, or framework conventions from earlier Koravik repositories or unrelated projects.

## Mandatory reading order

Before implementation, read `README.md`, `docs/README.md`, `docs/FOUNDATIONAL_DECISIONS.md`, all canonical documents, Project Zero, product and engineering documents, the ADR register, and this handoff. Follow the authority order in `docs/README.md` when documents disagree.

## Architecture baseline

Koravik-Final is a PHP 8.3+ custom modular monolith using MySQL or MariaDB through PDO and an Apache-compatible shared-hosting model.

- Districts own real-life truth.
- Hearth composes but does not own source records.
- Worlds interpret approved minimized facts into independent World State.
- Platform events describe committed facts and use a transactional outbox.
- Authorization is capability-based and contextual.
- Server-rendered accessible HTML is the baseline.
- Background work is finite, idempotent, lock-safe, and cron compatible.

## Completed builds

### Build 001 — Foundation

Secure sign-in, shared shell, Hearth, Quest completion, transactional outbox, bounded worker, Epic Ordinary reaction and World State, audit history, migrations, seeding, and MySQL CI.

### Build 002 — Personal Quest creation

Account-owned Quest creation with title and optional notes, validation, durable listing, Hearth integration, completion, and World reaction.

### Build 003 — Quest lifecycle and recurrence

Relational recurrence rules, multiple weekdays, every-X intervals, generated occurrences, occurrence-based completion, pause/resume/archive lifecycle, and bounded cron-compatible generation.

### Build 004 — Pillars, Chronicle, and Hearth integration

Optional Quest-to-Pillar meaning, event-driven contribution ledger, automatic Chronicle moments, optional reflections, richer Hearth composition, completion summaries, bounded undo, reversal events, and module-neutral composite event consumers.

### Build 005 — Epic Ordinary continuation

Persistent World navigation, Chapter One, durable Caretaker support-style choice, NPC relationship state and provenance, Quest-completion-driven trust changes, fresh-install initialization, responsive presentation, and MySQL validation.

**Checkpoint:** `a21b4392ff12927e3e92ca41c91218e73e3d0740`

### Build 006 — Structured Quests, steps, and milestones

A Quest may be a single action, habit, project, journey, responsibility, or World objective. Structured Quest types may contain ordered required or optional steps, show meaningful progress, reach milestones, and prevent completion while required steps remain.

Delivered:

- explicit relational Quest types;
- ordered required and optional Quest steps;
- pending, completed, reopened, and skipped step states;
- automatic 25%, 50%, and 100% milestones for Projects and Journeys;
- progress presentation and required-step completion guard;
- audit history for step changes;
- minimized Quest type in completion events;
- preserved Pillar, Chronicle, and World reactions;
- successful browser and MySQL validation.

**Checkpoint:** `20f37f1e2ea81552c4f975ebbe3cdf46e73bee3a`

## Build workflow

For every build: inspect current `main`, read affected authority, state the player-visible outcome and boundary, implement one coherent vertical slice, validate it, update documentation, and merge one cohesive milestone.

## Change control

When implementation reveals a blueprint flaw, stop at the affected boundary and resolve it through an explicit document revision or ADR before continuing.
