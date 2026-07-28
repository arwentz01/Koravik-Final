# Companion Governance

**Status:** Canonical
**Version:** 1.2
**Effective build:** 017

## Purpose

Companion helps a person think, draft, and choose. It does not silently become an operator of District or Platform records and does not inspect private context without an explicit permission and selection.

## Ownership

- Companion owns proposals, draft reasoning, proposal lifecycle, proposal provenance, approved Companion memory, and records of minimized context use.
- Quests owns saved Quests and final Quest validation.
- Chronicle owns saved reflections and final reflection validation.
- Other Districts retain equivalent ownership of their records.
- Audit History records consequential proposal, consent, memory, and execution outcomes.

## Proposal contract

Every consequential proposal identifies the proposed action, owning module, source context, affected records when any, expected consequence, reasoning, and proposal version.

A proposal is visibly different from a saved record. Approval applies only to the reviewed version. Editing or renewal invalidates prior approval. Dismissal has no negative product consequence.

## Lifecycle and recovery

Proposals may be draft, awaiting approval, approved, expired, executing, executed, failed, or dismissed.

- expiration prevents execution;
- renewal increments the version and requires fresh approval;
- clarification does not alter a destination record;
- failure preserves the proposal and records bounded, human-readable context;
- retry requires destination revalidation and existing-receipt checks.

Lifecycle events minimize private content and carry proposal identity, type, version, destination, and outcome.

## Execution boundary

Companion may never directly create, edit, publish, send, or delete a consequential source record. After explicit approval, the owning module revalidates identity, authorization, proposal type, state, approved version, expiration, payload constraints, current business rules, and any execution receipt.

Execution must occur through destination-owned code, be idempotent, create a durable receipt, mark execution only after source commit, preserve failed proposals, append audit evidence, and return the resulting record link.

## Chronicle boundary

A Companion-authored reflection remains a draft until the person reviews, edits, approves, and explicitly chooses **Save to Chronicle**. Chronicle performs final validation and owns the saved entry. Companion-authored draft voice remains distinguishable from the approved Chronicle record.

## Context and memory

Companion uses only explicitly supplied or authorized, minimized context.

- permissions do not authorize background scanning;
- selected Quest and Chronicle context are one-time unless explicitly stated otherwise;
- Pillar and accessibility context should be derived and bounded;
- memory requires explicit approval and may be edited, disabled, or deleted;
- memory remains separate from Chronicle, Quests, Account identity, and World State;
- revocation stops future use but does not rewrite historical proposals or audit evidence.

## Implemented build boundaries

- Build 014: Quest proposals, editing, version-specific approval, dismissal, expiration metadata, and audit history without execution.
- Build 015: destination-owned idempotent Quest and Chronicle execution, receipts, result links, and audit history.
- Build 016: expiration enforcement, renewal, clarification, failure context, read-only activity, and minimized lifecycle events.
- Build 017: consent-scoped selected context, context-use records, approved Companion memories, provenance, disable, and deletion controls.
