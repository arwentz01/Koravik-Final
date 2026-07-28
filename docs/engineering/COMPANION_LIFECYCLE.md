# Companion Lifecycle and Recovery

**Status:** Approved
**Effective build:** 016

Companion proposals expire, recover, and report failure without weakening destination ownership.

## Lifecycle

Supported states are `draft`, `awaiting_approval`, `approved`, `expired`, `executing`, `executed`, `failed`, and `dismissed`.

Approval is version-specific. Expiration or failure requires renewed review. Renewal increments the proposal version and clears prior approval. A retry never grants indefinite authority.

## Recovery

Clarification is attached to the proposal and does not mutate destination records. Execution failures use a bounded code and human-readable message. Destination modules revalidate account, type, version, expiration, payload, authorization, and any existing execution receipt.

## Events

Lifecycle events contain proposal identity, type, version, destination, and outcome. They must not copy private proposal bodies unless a named consumer contract requires them.

Implemented events include `Companion.ProposalRevised`, `Companion.ProposalClarified`, and `Companion.ProposalExecutionFailed` version 1.
