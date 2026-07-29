# Koravik-Final Implementation Handoff

**Status:** Build 035 implemented on `main`  
**Version:** 1.35  
**Baseline date:** July 28, 2026  
**Authoritative branch:** `main`

## Repository authority

This handoff applies only to `arwentz01/Koravik-Final`. Do not import code, schemas, routes, build numbers, deployment assumptions, or framework conventions from earlier Koravik repositories or unrelated projects.

## Mandatory reading order

Before implementation, read `README.md`, `docs/README.md`, `docs/FOUNDATIONAL_DECISIONS.md`, all canonical documents, Project Zero, affected Product and Engineering Blueprint contracts, the ADR register, and this handoff. The documentation authority order in `docs/README.md` controls conflicts.

## Architecture baseline

Koravik-Final is a PHP 8.3+ custom modular monolith using MySQL or MariaDB through PDO and an Apache-compatible shared-hosting model.

- Districts own real-life truth.
- Hearth composes but does not own source records.
- Healing Home and Journey orient, acknowledge, and connect experiences without taking ownership from Quests, Chronicle, Worlds, Companion, Beacon, or Gather.
- Worlds interpret approved minimized facts into independent fictional World State.
- World objectives, choices, keepsakes, relationships, reactions, and lifecycle history remain fictional and account-scoped.
- Companion owns proposals and approved Companion memory, not destination records.
- Chronicle owns saved personal and approved reflections.
- Search and Notifications are non-owning utilities.
- Platform events describe committed facts and use a transactional outbox.
- Authorization is capability-based and contextual.
- Background work is finite, idempotent, lock-safe, and cron compatible.

## Completed implementation arc

### Builds 001–005 — Foundation and first vertical slice

Secure authentication, Hearth, personal Quests, recurrence, Pillars, Chronicle completion moments, transactional outbox, bounded worker, Epic Ordinary reactions, World State, relationship continuity, and audit history.

### Builds 006–013 — Core platform breadth

Structured Quests, World catalog and consent, return and resume, Notifications, Search, Privacy and Audit, Account Settings, and bounded Hearth customization.

### Builds 014–020 — Companion, Chronicle, account data, and security

Versioned Companion proposals and execution, approved context and memory, Chronicle ownership and lifecycle, account export and closure, authentication recovery, password change, session invalidation, and security audit history.

### Builds 021–025 — Visual release candidate and World lifecycle

Unified authenticated shell, visual information architecture, appearance accessibility controls, Epic Ordinary Chapter Two, World progress and explainable reactions, installed World management, suspend/uninstall/restart/delete boundaries, and durable lifecycle history.

### Build 026 — Registration and first-use orientation

Secure account registration and bounded first-use orientation foundation.

### Builds 027–028 — Living Quests and Healing Home

Quest purpose, next meaningful step, provenance, non-binary resolution history, `/home`, initial rooms, atmosphere, Quest focus, Chronicle memory, World reaction, and visible future-room foundations.

### Builds 029–030 — Reflection and relationships

Idempotent Home changes, account-scoped keepsakes, Caretaker relationship records, approved shared memories, qualitative relationship stages, and readable relationship history without score bars or punishment mechanics.

### Build 031 — Story invitations

Epic Ordinary invitations support accept, decline, and snooze. Only explicit acceptance creates a real-life Quest, with durable `story` provenance.

### Build 032 — Healing Home expansion

Garden and Library open as presentation spaces. Keepsakes support account-scoped, source-idempotent room placement. Remaining rooms stay visible extension points.

### Build 033 — Relationship conversations

The Caretaker supports gratitude, repair, disagreement, and quiet companionship with readable account-scoped conversation history and no correct dialogue path.

### Build 034 — Beacon Quest integration contract

Beacon event activity may create consent-gated Quest proposals for preparation, attendance, volunteering, or follow-up. Beacon retains event ownership; Quests owns accepted commitments.

### Build 035 — Gather Quest integration contract

Gather collaboration may create consent-gated Quest proposals with Gather provenance. Cooperative invitation persistence establishes future contribution boundaries without implementing Household or Organization ownership.

See `docs/builds/BUILD_031_035_JOURNEY_ARC.md` for the current arc contract.

## Current player loop

`World or supporting domain proposes → player accepts, declines, or snoozes → Quests owns the accepted commitment → real action and reflection occur → Healing Home and relationships may acknowledge approved outcomes.`

## Current database migrations

Production deployment must apply all migrations in `database/migrations`, including the current sequence:

```text
018_epic_ordinary_chapter_two
019_world_progress_reactions
020_world_lifecycle
021_first_use_registration
022_living_quests_healing_home
023_reflection_relationships
024_journey_arc_invitations_rooms_conversations
```

Run:

```bash
php tools/migrate.php
```

## Current routes added by the Journey arc

- `/home` and `/healing-home`
- `/journey`
- `/journey/invitations/{id}/accept`
- `/journey/invitations/{id}/decline`
- `/journey/invitations/{id}/snooze`
- `/journey/caretaker/converse`
- `/journey/source-proposals`
- `/journey/source-proposals/{id}/accept`
- `/journey/source-proposals/{id}/decline`

## Explicit current boundaries

- Companion does not autonomously monitor behavior or scan private records.
- Companion does not directly own or mutate Quest or Chronicle source records.
- Approval and execution remain separate actions.
- External AI-provider integration remains deferred.
- Household, Organization, messaging, email sending, and full collaborative authorization remain deferred.
- Cooperative Quest invitations are foundations only and do not imply shared-account access.
- Provider-specific authentication email delivery remains an adapter concern.
- Passkeys, social login, and multifactor authentication remain deferred.
- Epic Ordinary receives only currently permitted minimized facts.
- World reactions do not expose Quest notes, Chronicle prose, Companion memory, account secrets, or unrelated private records.
- Fictional World objectives and invitations never become duplicate real-life Quests without explicit consent.
- Declining or snoozing an invitation has no relationship or Home penalty.
- Beacon and Gather retain ownership of their source records; Quests owns accepted commitments.
- Restart and deletion affect only selected account-specific World State.
- Shared World catalog and package definitions are never deleted through player lifecycle controls.

## Validation

Build 035 adds `.github/workflows/build-035.yml`, which lints PHP and checks migration, consent, provenance, source-domain, and agency boundaries. A successful GitHub Actions run must be confirmed before treating the checkpoint as release-ready.

## Build workflow

For every build: inspect current `main`, read affected authority, identify the player-visible outcome and visual home, implement one coherent vertical slice, validate it, update affected documentation, and land one cohesive milestone.

## Change control

When implementation reveals a blueprint flaw, stop at the affected boundary and resolve it through an explicit ADR or deliberate document revision before continuing.