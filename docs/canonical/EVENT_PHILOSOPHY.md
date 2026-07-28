# Koravik Event Philosophy

## Status

Canonical product and architecture alignment document.

This document explains why Koravik is event-driven and how Platform services, Districts, Worlds, quests, relationships, Journey, and future integrations interact without becoming tightly coupled.

## Core Principle

Meaningful actions produce facts.

Facts are published as Platform Events.

Authorized consumers respond according to their own responsibilities.

```text
Person acts
  District records its domain change
    Transaction commits
      Platform Event is published
        World Runtime evaluates narrative rules
        Journey evaluates meaningful milestones
        Chronicle may preserve an authored memory
        Other authorized consumers may respond
```

The Event Bus is not merely technical plumbing. It is the mechanism that allows a person's life, Koravik's Districts, and persistent Worlds to influence one another safely.

## Events Describe Completed Facts

An event states what happened, not what another component should do.

Preferred:

- `Health.WaterLogged`
- `Chronicle.EntryCreated`
- `Gather.EventPlanned`
- `Gather.EventHosted`
- `Beacon.VolunteerRoleAccepted`
- `Hearth.TaskCompleted`

Avoid command-like events such as:

- `CompleteQuestNow`
- `IncreaseCaretakerRelationship`
- `WriteJourneyEntry`

The publishing component owns the fact. Consumers own their interpretations and reactions.

Events must be emitted only after the underlying state change commits successfully. A failed or rolled-back action must not become part of the Player's narrative history.

## District Ownership

Each District owns its domain behavior and persistence.

Examples:

- Health owns health and wellness records.
- Gather owns events, invitations, hosting, and participation.
- Beacon owns organizations, membership, campaigns, and outreach.
- Chronicle owns journals, reflections, memories, and authored history.
- Hearth owns personal and household life capabilities assigned to that District.

A District must not contain World-specific story logic merely because one of its events may affect a story.

Health records that water was logged. It does not decide that an Epic Ordinary quest advanced.

Gather records that an event was planned or hosted. It does not directly modify an NPC relationship.

Beacon records participation or leadership facts. It does not determine a fantasy guild reputation.

This preserves both domain ownership and creative flexibility.

## World Runtime Response

The World Runtime listens for events through explicit, versioned subscriptions and evaluates authored narrative rules.

A World rule may determine that an event:

- advances a quest objective
- completes a task
- changes an NPC relationship
- sets a narrative flag
- unlocks dialogue
- opens a chapter
- schedules a delayed consequence
- grants a reward
- creates a future task or invitation
- has no effect

The same Platform Event may be interpreted differently by different Worlds.

For example, `Health.WaterLogged` may:

- advance a self-care objective in Epic Ordinary
- restore a resource in a survival World
- satisfy no rule in a mystery World

The source event remains the same truthful fact.

## Active and Inactive Worlds

World progress is independent, but Worlds remain persistent.

The active World determines the primary narrative experience presented to the Player. However, an inactive World may still need to preserve an explicitly authored delayed consequence, scheduled event, or cross-World reward.

Inactive World processing must be bounded and intentional. A World must not silently subscribe to every detail of a Player's life. Subscriptions must be declared, permission-aware, and limited to the events needed by the experience.

When a Player returns to a World, it should resume from its stored state and present any eligible responses in a coherent way rather than dumping a stream of raw notifications.

## Quests and Objectives

Quests are progressed by qualifying facts, not only by clicking task checkboxes.

An objective may be satisfied by:

- an explicit task completion
- a threshold or sequence of events
- a District action
- a choice in dialogue
- a relationship state
- elapsed time
- a scheduled trigger
- an authorized external integration event

Example:

```text
Caretaker assigns: "Take a moment to reflect."
  Chronicle.EntryCreated
    World rule validates the entry against the objective
      Objective completed
        Caretaker dialogue becomes available
```

Chronicle does not know the entry was part of a quest. The World does not write the journal entry. The Event Bus connects the completed fact to the authored rule.

## Relationships and Pillars

Relationships are persistent narrative state influenced by meaningful events and choices.

Planning an event, helping an organization, caring for health, completing a household responsibility, or recording a reflection may affect a relationship when a World explicitly defines that connection.

The relationship response should be contextual and authored. Koravik should not assume that every logged action deserves points or that every relationship can be reduced to a visible score.

A relationship rule may evaluate:

- what happened
- who was involved
- the relevant World and chapter
- prior choices
- current relationship state
- repetition or pattern
- timing
- consent and privacy boundaries

Relationships may then influence later dialogue, chapters, opportunities, rewards, or delayed events.

## Journey and Chronicle

Journey and Chronicle are related but not interchangeable.

Journey is a Platform capability that recognizes meaningful milestones across Koravik. It should admit durable facts that help represent the Player's broader path through the Platform.

Chronicle is a District that supports journals, reflections, memories, and authored history.

Not every Platform Event belongs in Journey, and not every event should create a Chronicle entry. Raw event volume must not become a noisy activity feed.

Worlds may reference permitted Journey milestones or Chronicle content through explicit contracts. Sensitive Chronicle content must never be exposed to a World, creator, NPC, or AI merely because an entry exists.

## Privacy, Consent, and Data Minimization

Narrative responsiveness must never become surveillance.

Every event category must have a clear owner, purpose, privacy classification, and retention policy.

Worlds and creators may only subscribe to event types exposed through approved contracts. They must not receive unrestricted payloads or direct database access.

Sensitive domains, particularly Health and private Chronicle content, require additional safeguards such as:

- explicit opt-in
- limited payloads or derived signals
- revocation
- auditability
- clear user-facing explanations
- protection from creator-authored misuse
- no undisclosed advertising or profiling use

A World often needs the fact that an objective condition was met, not the underlying private record.

## Idempotency and Reliability

Event consumers must be idempotent. Replaying an event must not duplicate rewards, relationship changes, tasks, Journey milestones, or narrative consequences.

Events require stable identifiers, schema versions, timestamps, actor and subject context where appropriate, and traceable causation or correlation identifiers.

The implementation must support Bluehost shared hosting. It must not depend on permanent workers. Event delivery and delayed processing may use transactional storage, an outbox, scheduled execution, and safe retry mechanisms compatible with cron or request-driven processing.

## Delayed Consequences

Koravik must support consequences that occur well after the initiating event.

A decision made today may affect an NPC conversation months later. A relationship milestone may unlock a chapter after a season changes. An event hosted in Gather may create a follow-up quest at an appropriate future time.

Delayed consequences should be stored as durable, inspectable narrative commitments rather than held in memory.

They must support:

- due time or eligibility conditions
- Player and World scope
- source event and authored rule references
- cancellation or invalidation rules
- idempotent execution
- version compatibility
- audit and troubleshooting visibility

## Cross-World Rewards

A World may issue a reward associated with another World or with a shared compatibility contract.

The originating World publishes or records the grant through Platform-controlled reward services. The receiving World decides how an eligible reward is revealed or used.

Cross-World rewards should invite exploration without corrupting independent World progress. They must be secure, non-duplicating, versioned, and understandable to the Player.

## Future Calendar and External Integrations

Future Google Calendar, Apple Calendar, health platform, or other integrations may become event sources after explicit authorization.

Examples may include:

- an upcoming commitment suggesting a preparation task
- a completed appointment satisfying a self-care objective
- a birthday or anniversary making story content eligible
- an event series informing a new chapter or recurring quest

External integrations should publish normalized Platform Events or derived signals rather than leak provider-specific data throughout Koravik.

The Player must be able to control what is connected, what kinds of events may be used, and which Worlds may respond.

## Anti-Patterns

Do not:

- make Districts call World-specific services directly
- embed Caretaker logic inside Health, Gather, Beacon, Chronicle, or Hearth
- treat every database change as narratively meaningful
- expose raw private records to creator-built Worlds
- require every quest to be a checklist
- turn relationships into automatic points for routine actions
- allow events before transaction commit
- grant rewards without idempotency
- build delayed consequences around a permanent worker
- make inactive Worlds silently observe everything
- reduce Chronicle to an automatic activity log
- reduce Journey to event storage

## Architecture Review Questions

Before accepting a new interaction, ask:

1. Which component owns the original fact?
2. What event is published after commit?
3. What is the minimum safe payload?
4. Which consumers are authorized to respond?
5. Is the World response authored, contextual, and idempotent?
6. Does the interaction preserve District ownership?
7. Does it respect independent World progress?
8. Could the same capability be used safely by creator-built Worlds?
9. How is a delayed response persisted and executed?
10. Can the Player understand and control the use of sensitive data?

## Summary

Koravik does not gamify life by attaching points to isolated actions.

Koravik recognizes meaningful events and allows persistent Worlds to respond.

The Districts record life. The Platform carries facts. The World interprets them. The story changes because the Player lived.