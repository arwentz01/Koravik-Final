# Koravik-Final Implementation Handoff

**Status:** Build 005 in validation  
**Version:** 1.5  
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

**Checkpoint:** `0a0eb9f24bc2b9dec3b346497872c37d05ce0a11`

## Build 005 — Epic Ordinary continuation

### Player-visible outcome

A Player can enter Epic Ordinary, meet the Caretaker through durable dialogue, choose how support should feel, and see that choice and completed real-life Quests shape the ongoing relationship.

### Technical boundaries

- Epic Ordinary uses the same World contracts expected of future Worlds.
- World reactions consume minimized Platform events rather than reading Quest-owned records.
- NPC relationship changes have durable, explainable provenance.
- Meaningful choices are persisted and are not presented as disposable UI.
- World progress remains isolated by account and installation.

### Included

- World navigation and persistent World home;
- Chapter One, The First Light;
- Caretaker NPC relationship and history;
- durable support-style choice;
- Quest-completion-driven trust changes;
- fresh-install initialization;
- accessible responsive presentation;
- end-to-end MySQL validation.

## Build workflow

For every build: inspect current `main`, read affected authority, state the player-visible outcome and boundary, implement one coherent vertical slice, validate it, update documentation, and merge one cohesive milestone.

## Change control

When implementation reveals a blueprint flaw, stop at the affected boundary and resolve it through an explicit document revision or ADR before continuing.
