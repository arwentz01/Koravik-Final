# Architecture Decision Register

**Status:** Accepted for Blueprint v1.0  
**Version:** 1.0  
**Owner:** Architecture Review

This register records the initial decisions frozen for implementation. Future changes require a new ADR that supersedes the affected decision.

## ADR-001 — Documentation-first authority

**Decision:** Product and engineering blueprints precede implementation. When code and approved blueprint disagree, implementation is corrected or the blueprint is deliberately revised before work continues.

**Consequence:** Code cannot silently redefine product behavior or architecture.

## ADR-002 — Custom PHP modular monolith

**Decision:** Koravik uses PHP 8.3+ as a custom modular monolith. Laravel and Laravel-specific conventions are retired. Modules retain explicit ownership and may be extracted only when evidence justifies it.

**Consequence:** The platform remains shared-host compatible and avoids premature distributed-system complexity.

## ADR-003 — District ownership of truth

**Decision:** Each District owns its domain records and rules. Hearth composes; it does not own source facts. Cross-module references and presentation do not transfer ownership.

**Consequence:** Modules do not write directly to one another's canonical tables.

## ADR-004 — Transactional platform events

**Decision:** Cross-module and World reactions use minimized, versioned facts recorded through a transactional outbox after the owning change succeeds.

**Consequence:** Consumers are idempotent, delivery is retryable, and failed source transactions never become narrative facts.

## ADR-005 — Independent World State

**Decision:** Every installed World owns isolated state for story progress, NPC relationships, flags, inventory, rewards, and delayed consequences. Worlds interpret approved facts but do not alter District records.

**Consequence:** World switching preserves independent progress and prevents fictional systems from owning real-life truth.

## ADR-006 — Companion proposes; humans approve

**Decision:** Companion may explain, summarize, suggest, and draft. Consequential actions require explicit approval and are executed by the owning module with audit.

**Consequence:** AI assistance cannot silently act, create commitments, or change authoritative records.

## ADR-007 — Server-rendered accessible baseline

**Decision:** Server-rendered HTML is the baseline. JavaScript progressively enhances approved interactions. Core journeys remain usable when enhancement fails.

**Consequence:** Accessibility, resilience, and shared-host compatibility shape the interface from Build 001.

## ADR-008 — Capability-based authorization

**Decision:** Roles bundle capabilities, while authorization evaluates actor, ownership, group context, visibility, consent, and resource state at the application-service boundary.

**Consequence:** Interface visibility is never treated as security.

## ADR-009 — Shared-host-safe background processing

**Decision:** Outbox delivery, delayed consequences, notifications, and maintenance use bounded, idempotent, cron-safe commands. No permanent worker is required for correctness.

**Consequence:** Work is finite, lock-safe, observable, and resumable.

## ADR-010 — First proof is the life-to-story loop

**Decision:** Build 001 proves sign-in, Hearth orientation, one Quests-owned completion, transactional event publication, Epic Ordinary interpretation, independent explainable World State, and safe resume.

**Consequence:** Broad feature expansion is blocked until this complete vertical slice works beautifully.

## Decision procedure

A future ADR records status, context, decision, alternatives, consequences, affected documents, migration or compatibility impact, and superseded decisions. Accepted ADRs enter the documentation authority order above ordinary technical guidance but below the Constitution, Charter, and explicit foundational decisions.