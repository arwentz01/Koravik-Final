# Companion Execution Architecture

**Status:** Engineering contract
**Version:** 1.0
**Effective build:** 015

## Boundary

Companion stores and presents proposals. Destination modules execute approved proposals through owner-specific executors. Companion controllers never insert source-owned Quest or Chronicle records directly.

## Execution preconditions

The destination executor must lock and verify:

1. proposal belongs to the authenticated account;
2. proposal type matches the executor;
3. status is `approved`;
4. approved version equals current version;
5. proposal has not expired;
6. payload satisfies current destination validation;
7. no execution receipt already exists.

## Transaction contract

Destination record creation, execution receipt creation, proposal transition to `executed`, execution provenance, and audit insertion commit in one database transaction. A repeat request checks the receipt first and returns the existing destination record ID.

## Ownership implementations

- `Districts\Quests\QuestProposalExecutor` owns `quest.create` execution.
- `Platform\Experience\ChronicleProposalExecutor` owns `chronicle.reflection.create` execution until Chronicle becomes a separately packaged District.
- `Platform\Companion\ReflectionProposalService` owns reflection draft proposal creation only.

## Data minimization

Execution receipts contain proposal identity, account identity, approved version, destination module, destination record ID, and timestamp. They do not copy Quest notes or Chronicle body text.

## Failure behavior

An executor throws a human-readable validation error and leaves the proposal unexecuted when preconditions fail. Source records must never be partially created. Historical approvals and audit history remain append-only.

## Events

The current implementation records proposal lifecycle through relational state and audit history. When proposal lifecycle events are added to the transactional outbox, payloads must be limited to proposal ID, proposal type, destination module, status, version, and destination record ID when executed. Private proposal text is excluded by default.