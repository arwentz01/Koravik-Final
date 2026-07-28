# Platform Event Catalog

**Status:** Draft for Blueprint v1.0 review  
**Version:** 1.0  
**Owner:** Koravik Architecture  
**Last reviewed:** 2026-07-28

## Purpose

This catalog defines the initial public Platform Event contracts for Koravik-Final. It supplements `docs/canonical/EVENT_PHILOSOPHY.md` and narrows the allowed event surface for the first implementation.

An event is a completed, committed fact owned by its publishing module. It is not a command to another module and does not grant consumers permission to access the source record.

## Event naming

Canonical event names use:

```text
<Owner>.<FactName>.v<MajorVersion>
```

Examples:

```text
Quests.QuestCompleted.v1
Gather.AttendanceConfirmed.v1
Chronicle.EntryCreated.v1
```

The version suffix is part of the contract. Minor compatible clarifications may be documented without changing the name, but any incompatible payload or semantic change requires a new major version.

## Required envelope

Every Platform Event includes:

```json
{
  "event_id": "globally-unique-id",
  "event_name": "Quests.QuestCompleted.v1",
  "schema_version": 1,
  "owner": "Quests",
  "occurred_at": "UTC timestamp",
  "recorded_at": "UTC timestamp",
  "actor": {
    "type": "account",
    "id": "opaque-account-reference"
  },
  "subject": {
    "type": "quest_occurrence",
    "id": "opaque-subject-reference"
  },
  "correlation_id": "request-or-workflow-id",
  "causation_id": "optional-source-event-id",
  "privacy": "standard|sensitive|derived-sensitive",
  "payload": {}
}
```

Actor and subject fields may be omitted only when they are genuinely not applicable. Internal numeric database IDs must not be exposed through creator-facing contracts.

## Contract rules

All events must:

- represent a fact that has already committed;
- have one owning publisher;
- use minimized payloads;
- carry stable identity and versioning;
- be safe for duplicate delivery;
- declare privacy classification;
- include enough context for authorized interpretation without requiring source-table access;
- avoid prose fields containing private user content unless the contract explicitly requires and protects them;
- remain immutable after publication.

Consumers must:

- declare supported event names and versions;
- verify authorization and consent at delivery time;
- process idempotently;
- record provenance for consequential reactions;
- tolerate events that produce no reaction;
- never write to the publisher's records.

## Privacy classes

### Standard

A low-sensitivity operational fact that may be delivered to approved first-party consumers under normal account permissions.

### Sensitive

A fact tied to private domains or content. Delivery requires explicit contract, purpose, consent, minimization, and audit.

### Derived-sensitive

A minimized condition derived from sensitive data without exposing the source detail. Worlds should generally receive derived-sensitive facts instead of raw Health or private Chronicle records.

## Initial authoritative events

### Quests.QuestCompleted.v1

**Status:** Required for the first vertical slice  
**Owner:** Quests  
**Privacy:** Standard unless the Quest is classified more restrictively  
**Meaning:** An eligible Quest occurrence was successfully completed by an Account.

Payload:

```json
{
  "quest_public_id": "opaque-id",
  "occurrence_public_id": "opaque-id",
  "completion_public_id": "opaque-id",
  "quest_kind": "personal|household|organization|world_prompted",
  "completion_source": "user|approved_companion|authorized_integration",
  "completed_at": "UTC timestamp",
  "world_signal": {
    "code": "optional-approved-semantic-code"
  }
}
```

Rules:

- `world_signal` is optional and must use an approved semantic code, never arbitrary creator-controlled text.
- The event must not expose the Quest title, notes, household details, organization details, or private description by default.
- A World receives this event only through a declared subscription and approved consent.
- Duplicate delivery must not duplicate World rewards or relationship changes.

Initial Epic Ordinary interpretation:

- match one authored rule against the approved semantic code or reference Quest contract;
- create one explainable World reaction;
- update independent World State;
- preserve the source event ID as provenance.

### Quests.QuestReopened.v1

**Status:** Deferred until correction behavior is implemented  
**Owner:** Quests  
**Privacy:** Matches the original completion

Meaning: A previously completed Quest occurrence was intentionally reopened or its completion was reversed by an authorized action.

Consumers must not assume every prior consequence can or should be erased. World rules must explicitly define correction behavior.

### Chronicle.EntryCreated.v1

**Status:** Deferred  
**Owner:** Chronicle  
**Privacy:** Sensitive

Meaning: An Account intentionally saved a Chronicle entry.

Default payload must not include title or body text. Approved consumers may receive a derived signal such as entry type, reflection completion, or user-approved tag.

### Chronicle.ReflectionConditionMet.v1

**Status:** Preferred future World-facing Chronicle contract  
**Owner:** Chronicle  
**Privacy:** Derived-sensitive

Meaning: A user-approved reflection condition was satisfied without exposing the entry content.

Example payload:

```json
{
  "condition_code": "reflection.completed",
  "occurred_on": "local-date",
  "consent_grant_id": "opaque-id"
}
```

### Gather.AttendanceConfirmed.v1

**Status:** Candidate second District proof  
**Owner:** Gather  
**Privacy:** Standard or sensitive depending on event visibility

Meaning: An Account's attendance at a Gather event was confirmed according to the event's participation rules.

Default payload must not reveal event title, exact location, attendee list, or private organization membership.

### Gather.EventHosted.v1

**Status:** Deferred  
**Owner:** Gather  
**Privacy:** Standard or sensitive

Meaning: An Account completed the host responsibility for an eligible event.

### Health.ConditionMet.v1

**Status:** Deferred and consent-gated  
**Owner:** Health  
**Privacy:** Derived-sensitive

Meaning: An approved health-related condition was met. This event intentionally avoids raw measurements, diagnoses, medication names, free text, or device payloads.

Example payload:

```json
{
  "condition_code": "self_care.hydration_goal_met",
  "period": "day",
  "effective_date": "local-date",
  "consent_grant_id": "opaque-id"
}
```

Worlds must never subscribe to unrestricted Health records.

### World.ReactionApplied.v1

**Status:** Required for explainability and diagnostics  
**Owner:** World Runtime  
**Privacy:** Inherits the strictest classification of source and reaction

Meaning: A World Runtime successfully applied one authored reaction to independent World State.

Payload:

```json
{
  "world_installation_public_id": "opaque-id",
  "world_package": "epic-ordinary",
  "world_package_version": "semantic-version",
  "rule_id": "stable-authored-rule-id",
  "reaction_type": "story_progress|relationship_change|flag_set|quest_progress|reward|delayed_consequence",
  "source_event_id": "globally-unique-id",
  "explanation_code": "stable-explanation-code",
  "applied_at": "UTC timestamp"
}
```

This event must not expose private source payloads to unrelated consumers.

### World.DelayedConsequenceScheduled.v1

**Status:** Deferred  
**Owner:** World Runtime

Meaning: An authored, durable consequence was scheduled from an eligible source event.

### World.DelayedConsequenceApplied.v1

**Status:** Deferred  
**Owner:** World Runtime

Meaning: A scheduled narrative consequence became eligible and was applied idempotently.

### Companion.ProposalApproved.v1

**Status:** Deferred  
**Owner:** Companion

Meaning: An Account explicitly approved a Companion proposal. This event records approval, not the owning District's eventual completion of the action.

The owning District must still execute and publish its own completed fact.

### Companion.ProposalRejected.v1

**Status:** Deferred  
**Owner:** Companion

Meaning: An Account declined or dismissed a proposal. Consumers must not punish or pressure the person for this choice.

## Events prohibited from initial implementation

Do not create generic or ambiguous contracts such as:

```text
Platform.RecordChanged
User.DidSomething
World.UpdateRequested
Companion.ActionNeeded
System.NotificationCreated
```

Do not publish commands disguised as events:

```text
IncreaseCaretakerRelationship
CompleteWorldQuest
WriteChronicleEntry
SendUserReminder
```

Do not publish raw database-change streams or every routine state mutation.

## Subscription contract

Each World package declares subscriptions in its manifest.

A subscription includes:

- event name and supported major version;
- purpose statement;
- required privacy class;
- required user consent;
- rule IDs that may evaluate the event;
- whether inactive processing is allowed;
- retention need;
- explanation text or code shown to the person.

Installation must show requested subscriptions in plain language. Consent must be revocable without uninstalling the World unless the World cannot function without the permission, in which case the consequence must be explicit.

## Delivery authorization

Before delivery, the Platform verifies:

1. the consumer is registered;
2. the event version is supported;
3. the subscription is declared;
4. the Account has granted required consent;
5. the World installation is eligible;
6. inactive processing is explicitly permitted when applicable;
7. the payload is minimized for that consumer;
8. revocation or deletion rules do not prohibit delivery.

Authorization is evaluated at delivery time, not assumed from installation history.

## Idempotency

Every consumer must use `event_id` plus a stable consumer identifier as an idempotency boundary.

For World rules, the effective key should include:

```text
world_installation + event_id + rule_id + reaction_version
```

A retry may return the prior successful result but must not apply the consequence twice.

## Ordering

Global ordering is not guaranteed.

Where ordering matters, consumers may use:

- occurrence timestamps;
- causation chains;
- Account- or subject-scoped sequence values introduced by an accepted contract;
- explicit eligibility checks against current domain or World state.

Consumers must not rely on database auto-increment IDs as a public ordering guarantee.

## Failure and retry

Failures are classified as:

- transient infrastructure failure;
- unsupported version;
- authorization or consent denial;
- invalid payload;
- consumer defect;
- permanently ineligible reaction.

Transient failures may retry with bounded backoff. Invalid contracts and unsupported versions should dead-letter with visible diagnostics. Consent denial is a final non-delivery outcome, not an error to retry indefinitely.

## Replay

Authorized operators may replay events for a specific consumer after a defect is corrected.

Replay must:

- preserve the original event ID and timestamps;
- create a new delivery attempt record;
- respect current consent and retention rules;
- remain idempotent;
- never reconstruct a payload from source data that the consumer is no longer permitted to access.

## Retention

Outbox and delivery retention must balance diagnostics, privacy, and storage limits.

Before production, each event contract must define:

- minimum operational retention;
- whether payload redaction is required after delivery;
- whether event metadata survives account deletion;
- how provenance remains explainable after source-record deletion;
- dead-letter retention and purge behavior.

## First vertical slice acceptance

The initial event proof is complete only when:

1. completing a Quest and appending `Quests.QuestCompleted.v1` occur in one transaction;
2. a failed or rolled-back completion publishes no event;
3. the outbox runner processes a bounded batch;
4. Epic Ordinary receives only its declared minimized contract;
5. the World consumer is idempotent;
6. one World State change is applied and attributed to the source event;
7. the person sees a plain-language explanation of why the World reacted;
8. temporary World processing failure does not undo the Quest completion;
9. retry eventually produces one reaction, not duplicates;
10. revocation prevents future delivery.

## Catalog change process

A new public Platform Event requires:

- identified owning module;
- completed-fact semantics;
- payload schema;
- privacy classification;
- consent and retention rules;
- idempotency guidance;
- at least one legitimate consumer;
- tests for publication, validation, and duplicate delivery;
- review against the canonical event philosophy.

Incompatible changes require a new versioned event name and a migration plan for consumers.
