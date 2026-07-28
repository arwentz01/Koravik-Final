# Companion Governance

**Status:** Canonical
**Version:** 1.0
**Effective build:** 014

## Purpose

Companion helps a person think, draft, and choose. It does not silently become an operator of District or Platform records.

## Ownership

- Companion owns proposals, draft reasoning, proposal lifecycle, and proposal provenance.
- Quests owns saved Quests and final Quest validation.
- Chronicle owns saved reflections and final reflection validation.
- Other Districts retain equivalent ownership of their records.
- Audit History records consequential proposal decisions and execution outcomes.

## Proposal contract

Every consequential proposal must identify:

- the proposed action;
- the owning module;
- source context used;
- affected records, when any;
- the expected consequence;
- human-readable reasoning;
- the proposal version being approved.

A proposal is visibly different from a saved record. Approval applies only to the reviewed version. Editing invalidates prior approval. Dismissal has no negative product consequence.

## Execution boundary

Companion may never directly create, edit, publish, send, or delete a consequential source record. After explicit approval, the owning module must revalidate identity, authorization, proposal state, proposal version, and current business rules before executing. Execution must be idempotent and auditable.

## Privacy

Companion uses only explicitly supplied or authorized context. Proposal events minimize private content. Proposal history remains account-scoped. Fictional, Companion-authored, and person-authored voices must remain distinguishable.

## Build boundaries

- Build 014 supports Quest proposals, editing, version-specific approval, dismissal, expiration metadata, and audit history. Approval does not yet create a Quest.
- Build 015 adds owner-revalidated Quest execution and Chronicle reflection proposals with explicit save-to-Chronicle approval.