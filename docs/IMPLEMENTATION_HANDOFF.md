# Koravik-Final Implementation Handoff

**Status:** Healing Home Visual Foundation vertical product slice in the current working tree
**Version:** 2.20
**Baseline date:** July 30, 2026
**Authoritative branch:** `main`

## Repository authority

This handoff applies only to `arwentz01/Koravik-Final`. Do not import code, schemas, routes, build numbers, deployment assumptions, or framework conventions from earlier Koravik repositories or unrelated projects.

Before implementation, read `README.md`, `docs/README.md`, `docs/FOUNDATIONAL_DECISIONS.md`, all canonical documents, affected Product and Engineering Blueprint contracts, the ADR register, and this handoff. The documentation authority order in `docs/README.md` controls conflicts.

## Architecture baseline

Koravik-Final is a PHP 8.3+ custom modular monolith using MySQL or MariaDB through PDO and an Apache-compatible shared-hosting model.

- Personal participation does not require an Organization or Household.
- Organizations are optional contextual operating spaces.
- Platform account roles and Organization membership roles are separate.
- Districts retain ownership of their domain truth.
- Hearth composes but does not absorb Organization records.
- Beacon owns domains, links, pages, and QR definitions even when an Organization owns the resource context.
- Gather owns event truth even when an Organization owns the event context.
- Authorization is capability-based and contextual.
- Background work is finite, idempotent, lock-safe, and cron compatible.

## Completed implementation arc

### Builds 001–057

Platform identity and security, Hearth, Quests, Chronicle, Companion, Notifications, Search, Privacy and Audit, account-data lifecycle, Epic Ordinary and World lifecycle, first-use orientation, Journey experiences, Beacon/Gather foundation, Platform Mail, Gather operational tooling, agendas, reminders, day-of operations, closeout, Beacon domains, and consent-gated outcomes.

### Builds 058–062 — Stabilization and Beacon completion

- reminder cancellation and unsubscribe;
- approved-outcome application ledger;
- Beacon domain administration;
- Beacon link management and revision history;
- secure-context camera QR scanning with manual fallback.

See `docs/builds/BUILD_058_062_STABILIZATION_BEACON_COMPLETION.md`.

### Builds 063–067 — Organization foundation

- Build 063: optional Organization identity and profile lifecycle;
- Build 064: contextual Owner, Admin, Creator, and Member roles with invitations and membership controls;
- Build 065: Organization-owned Gather events and contextual command-center authorization;
- Build 066: Organization-owned Beacon links with stable platform fallback;
- Build 067: Organization operating home with members, invitations, events, links, activity, and capability-aware actions.

See `docs/builds/BUILD_063_067_ORGANIZATION_FOUNDATION.md`.

### Builds 068–077 — Organization operations

- contextual Gather authorization across day-of, communication, workflow, lifecycle, and closeout;
- Platform Mail invitation delivery with resend and revoke;
- Organization settings, lifecycle, audit, and recovery;
- verified Beacon domain selection and Beacon-owned public presence;
- internal teams with non-escalating team roles;
- consent-first Organization Quest proposals;
- composed operational coordination and Build 077 stabilization.

See `docs/builds/BUILD_068_077_ORGANIZATION_OPERATIONS.md`.

### Builds 078–087 — Household foundation

- Build 077 PHP 8.3 acceptance and release checks;
- optional Household identity, preferences, and lifecycle;
- contextual Owner, Admin, and Member roles with secure invitations;
- consent-first one-time and recurring responsibility proposals;
- private Household resources;
- Household-owned private Gather events with Gather-owned truth;
- Household home composition, notifications, audit, and recovery;
- Build 087 stabilization.

See `docs/builds/BUILD_078_087_HOUSEHOLD_FOUNDATION.md`.

### Builds 088–097 — Release verification

- unified local and CI release test runner;
- isolated migration and critical-schema verification;
- authentication, CSRF, Organization, Household, and Gather authorization tests;
- live subdirectory routing and accessibility smoke checks;
- Platform Mail, lifecycle recovery, and bounded-worker checks;
- MySQL-backed continuous integration and Build 097 release gate.

See `docs/builds/BUILD_088_097_RELEASE_VERIFICATION.md`.

### Builds 098–107 — Accessibility personalization

- durable reading and interaction preferences with safe defaults;
- text scale, readable typeface, relaxed spacing, and narrow reading width;
- emphasized links and enhanced keyboard focus;
- dedicated settings, preview, reset, audit, and global visual-system integration;
- automated persistence, validation, reset, CSS-contract, and Build 107 health checks.

See `docs/builds/BUILD_098_107_ACCESSIBILITY_PERSONALIZATION.md`.

### Builds 108–117 — Workflow resilience

- accessible form-error summaries and safe value preservation;
- expiring account-owned drafts with credential-field exclusion;
- database-backed duplicate-submit protection;
- tracked, hashed, revocable sessions with current-session protection;
- unified recovery center for unfinished work and operational recovery;
- executable resilience tests and Build 117 stabilization.

See `docs/builds/BUILD_108_117_WORKFLOW_RESILIENCE.md`.

### Hearth Daily Focus — complete vertical slice

- Account-local daily intention and up to three ordered Quest references;
- ownership and availability validation inside one Hearth transaction;
- complete empty, editor, validation, success, revision, clear, and failure states;
- responsive Hearth composition and JavaScript-independent editor;
- Account export and closure lifecycle coverage;
- service, authorization, rendering, limit, and browser-journey verification.

See `docs/features/HEARTH_DAILY_FOCUS.md`.

### Worlds Home and Reaction Review — implemented vertical slice

- story-first `/worlds` composition with active chapter, scene, continuation,
  World State, permissions, and lifecycle paths;
- durable account-scoped review state for explainable World reactions;
- new, reviewed, empty, unavailable, and success interface states;
- idempotent Epic Ordinary first-install initialization;
- Account export coverage, audit evidence, ownership tests, and responsive
  browser verification.

See `docs/features/WORLDS_HOME_AND_REACTION_REVIEW.md`.

### Healing Home Visual Foundation — implemented vertical slice

- illustrated `/home` and `/healing-home` room composition with Quest Board,
  Fireplace, Journal Table, Keepsake Shelf, relationship memory, Companion
  Chair, and visible unopened rooms;
- account-scoped materialization of owned World changes and Caretaker
  continuity through existing Journey persistence;
- durable return presentation through `last_returned_at` without guilt,
  punishment, streak framing, or source ownership drift;
- accessible room labels, meaningful illustration alternative text,
  responsive single-column reflow, and source-owner links for full workflows.

See `docs/features/HEALING_HOME_VISUAL_FOUNDATION.md`.

## Current migrations

Production deployment must apply every file in `database/migrations`, including:

```text
037_platform_mail_operations.sql
038_042_gather_lifecycle_beacon_domains.sql
043_047_lifecycle_stabilization_beacon_management.sql
048_052_organization_foundation.sql
053_062_organization_operations.sql
063_072_household_foundation.sql
073_082_accessibility_personalization.sql
083_092_workflow_resilience.sql
093_hearth_daily_focus.sql
094_hearth_daily_focus_lifecycle.sql
095_world_reaction_reviews.sql
```

Run:

```bash
php tools/migrate.php
```

## Background workers

```bash
php tools/mail-worker.php 20
php tools/gather-reminder-worker.php 100
php tools/worker.php 10
```

## Organization routes

- `GET|POST /organizations`
- `GET /organizations/{id}`
- `GET /organizations/invitations/{token}`
- `POST /organizations/{id}/invitations`
- `POST /organizations/{id}/members/{membershipId}/role`
- `POST /organizations/{id}/members/{membershipId}/remove`
- `POST /organizations/{id}/ownership/{membershipId}`
- `POST /organizations/{id}/events`
- `POST /organizations/{id}/links`

## Household routes

- `GET|POST /households`
- `GET /households/{id}`
- `GET /households/invitations/{token}`
- `POST /households/{id}/settings`
- `POST /households/{id}/lifecycle/{state}`
- `POST /households/{id}/invitations`
- `POST /households/{id}/members/{membershipId}/role`
- `POST /households/{id}/members/{membershipId}/remove`
- `POST /households/{id}/ownership/{membershipId}`
- `POST /households/{id}/leave`
- `POST /households/{id}/resources`
- `POST /households/{id}/events`
- `POST /households/{id}/quest-proposals`
- `POST /households/quest-proposals/{proposalId}/{accepted|declined}`

## Hearth Daily Focus routes

- `GET /hearth/focus`
- `POST /hearth/focus`
- `POST /hearth/focus/clear`

## Worlds Home routes

- `GET /worlds`
- `POST /worlds/reactions/{reactionId}/review`

## Organization rules

- Organization membership is optional.
- Creating an Organization creates one active Owner membership for the creator.
- Organization role never grants a platform-wide role.
- The final Owner cannot be removed by ordinary membership controls.
- Ownership transfer is atomic and leaves one active Owner.
- Organization-owned resources survive member departure.
- Personal records do not transfer into Organizations.
- Organization invitations are email-bound, expiring, and token-hashed.
- Organization-created Gather and Beacon resources retain their District ownership boundaries.

## Beacon domain rules

- `krvk.nl` remains the default verified platform short-link domain.
- `https://krvk.nl/` permanently redirects to `https://koravik.com/`.
- Custom hostnames require verification before activation.
- Organization-owned Beacon records keep stable UUID identity and a platform fallback.
- DNS and certificate provisioning remain hosting responsibilities.

## Explicit current boundaries

- Organization-owned Gather management is capability-based; participant self-service and personal outcome consent remain separate.
- Organization invitations depend on configured Platform Mail workers and never expose stored raw tokens.
- Archive and suspension are recoverable; destructive Organization deletion is intentionally not implemented.
- Organization domain selection accepts only verified Organization or platform domains.
- Team roles do not grant Organization-wide or Platform-wide capabilities.
- Organization Quest coordination is proposal-only until the recipient explicitly accepts.
- Household remains separate, independent, private by default, and optional.
- Household responsibility proposals create no Quest until the recipient accepts.
- Household Gather records remain owned by Gather, and Household resources are not public Beacon content.
- Payments and external calendar synchronization remain deferred unless separately approved.

## Validation

The single workflow at `.github/workflows/validate.yml` must lint PHP, migrate an isolated MySQL database, start the application, and run `php tools/test.php`. The release suite verifies migration inventory, critical schema, security primitives, Organization and Household capabilities, Gather authorization boundaries, subdirectory routing, accessibility preferences, Platform Mail operations, workflow recovery, duplicate protection, session revocation, bounded workers, the Build 117 checkpoint, Hearth Daily Focus, Worlds Home ownership, reaction review, rendering, first-install initialization, and Healing Home owned-room continuity.

## Next build

Continue the forward-facing product phase with another complete vertical slice. Prioritize cohesive District screens, Organization or Household dashboards, Epic Ordinary continuation, and responsive interaction polish; add backend work only when the selected visible workflow requires it.

## Build workflow

For every build: inspect current `main`, read affected authority, identify the player-visible outcome and visual home, implement one coherent vertical slice, validate it, update affected documentation, and land one cohesive milestone.
