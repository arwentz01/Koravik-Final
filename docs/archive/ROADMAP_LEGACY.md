# Koravik v3 Roadmap

**Status:** Current planning direction
**Updated:** Build 204E

The authoritative completed-build state and immediate next-build boundary are maintained in [`docs/platform/IMPLEMENTATION_HANDOFF_v1.md`](platform/IMPLEMENTATION_HANDOFF_v1.md). This roadmap describes projected sequencing; projected builds may be refined after acceptance findings, but only one build may be implemented at a time.

## Completed Foundation

- **Builds 1–203:** Custom-PHP foundation, identity, District and Platform capabilities, durable World runtime foundations, visible product surfaces, Daily Journey, Chronicle, and approval-bound Companion drafts.
- **Builds 204A–204D:** Canonical product model, Districts, Pillars, glossary, Worlds, Companion, and Creator Studio documentation.
- **Build 204E:** Canonical Charter, Constitution, Architecture, and Domain Model.

## First Product Loop

### Build 204 — World Management — Implemented

Make Worlds understandable and manageable through a player-facing registry, compatibility and permission disclosure, install/activate/suspend/resume behavior, deliberate restart, and preservation of independent World State.

### Build 205 — Platform Event Contract Proof — Implemented

Prove one versioned, minimized, consent-aware District fact and transactional outbox path with idempotent finite-worker delivery.

### Build 206 — Epic Ordinary Vertical Slice — Implemented

Prove the smallest complete life-to-story loop: a Quests-owned completion fact emits an approved Platform Event, Epic Ordinary applies a World-only reaction, and the player sees and later resumes the resulting story and NPC state.

### Build 207 — World Progress and Explainability — Implemented

Expose durable story position, World Quest state, NPC relationship dimensions, originating reasons, and strict separation between World memory and real-life records.

### Build 208 — Delayed Narrative Consequences — Implemented

Add bounded, shared-host-safe scheduled reactions with idempotency, retry, dead-letter handling, consent enforcement, and finite cron execution.

### Build 209 — Second District Adapter — Implemented

Prove reuse of the event architecture through a second owning District, initially favoring a minimized Gather attendance contract over sensitive Health data.

### Build 210 — Unified Application Shell and Design Tokens — Implemented

Establish one reusable application shell, canonical navigation, design tokens, accessibility behavior, and a compatibility layer for existing District components.

### Build 211 — District UI Convergence — Implemented

Migrate core and high-traffic District routes to canonical components without changing their business logic, authorization, routes, or ownership.

### Build 212 — User-Approved Chronicle Reflection — Implemented

Allow a World or Companion to propose a Chronicle draft while Chronicle remains the sole owner and saves only after explicit user approval.

## Installable Content Loop

### Build 213 — World Package Contract — Implemented

Validate structured manifests, compatibility, permissions, checksums, assets, content warnings, accessibility metadata, and the prohibition on arbitrary executable content.

### Build 214 — World Updates and State Migrations — Implemented

Provide semantic compatibility, versioned state transforms, validation, safe failure, migration history, and explicit release notes without losing progress.

### Build 215 — Marketplace Foundation — Implemented

Support discovery and installation of reviewed compatible releases with clear trust, permission, availability, and withdrawal states. Payments remain out of scope.

## Creator Loop

### Build 216 — Creator Studio Minimum Authoring Loop — Implemented

Author, validate, preview, package, privately install, and play a minimal structured World containing a branch, NPC relationship, World Quest, and approved event reaction.

### Build 217 — Creator Test Personas and Validation — Implemented

Add synthetic events, branch and loop analysis, permission simulation, accessibility checks, migration tests, package validation, and resettable synthetic state.

### Build 218 — Publishing and Review — Implemented

Implement auditable draft, validation, test, build, review, publication, monitoring, update, and withdrawal transitions.

## Companion and Daily-Life Loop

### Build 219 — Companion Memory and Consent Controls — Implemented

Make approved memory visible, sourced, correctable, deletable, revocable, classified, and separated between World and real-life context.

### Build 220 — Companion Approved Execution — Implemented

Execute approved proposals only through owning District services with consequence-aware confirmation and audit.

### Build 221 — Hearth Daily Composition — Implemented

Compose District-owned facts, World continuation, and Companion proposals into a useful individual home without requiring a Household, Organization, or World.

### Build 222 — Hearth Layouts, Widgets, and Acknowledgements — Implemented

Formalize Account-owned Hearth layouts, registered widget contracts, bounded placements and configuration, acknowledgement state, keyboard-safe customization, and restore-default behavior without changing source records.

## Release Rule

Every build must compile, pass registered and build-specific tests, preserve backward compatibility and ownership boundaries, update relevant documentation and the authoritative handoff, remain independently deployable, and identify both its player-visible outcome and its production acceptance boundary.
