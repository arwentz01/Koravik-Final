# Companion Governance

**Status:** Canonical
**Version:** 1.1
**Effective build:** 015

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

Companion may never directly create, edit, publish, send, or delete a consequential source record. After explicit approval, the owning module must revalidate identity, authorization, proposal type, proposal state, approved version, expiration, payload constraints, and current business rules before executing.

Execution must:

- occur through code owned by the destination module;
- be idempotent by proposal identity;
- create a durable proposal-to-record receipt;
- mark the proposal executed only after the source record commits;
- preserve the proposal when execution fails;
- create an append-only audit record;
- return a link to the resulting source-owned record.

## Chronicle boundary

A Companion-authored reflection remains a draft until the person reviews, edits, approves, and explicitly chooses **Save to Chronicle**. Chronicle performs final validation and owns the saved entry. The interface must label Companion-authored draft voice separately from the person’s approved Chronicle record.

## Privacy

Companion uses only explicitly supplied or authorized context. Proposal events minimize private content. Proposal history remains account-scoped. Fictional, Companion-authored, and person-authored voices remain distinguishable.

## Implemented build boundaries

- Build 014: Quest proposals, editing, version-specific approval, dismissal, expiration metadata, and audit history without execution.
- Build 015: Quests-owned idempotent execution, Chronicle reflection proposals, explicit save-to-Chronicle execution, execution receipts, result links, and audit history.