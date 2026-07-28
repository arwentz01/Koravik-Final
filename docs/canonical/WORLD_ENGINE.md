# Koravik World Engine

## Status

Canonical product-vision document.

This document defines the experience that Koravik's architecture and implementation must serve. When an implementation plan, domain model, district feature, or user interface conflicts with this document, the conflict must be resolved explicitly before development continues.

## Purpose

Koravik is one connected platform in which a person's real life and narrative Worlds continuously influence one another.

It is not a collection of unrelated productivity applications. It is not merely a task manager with game mechanics layered on top. It is not a conventional game with a few wellness features attached.

Koravik makes the ordinary epic by allowing meaningful actions in a person's life to become part of persistent, responsive stories.

## The Player Persists

A person has one Koravik identity.

The Player remains the same person while moving between Worlds. Their Platform identity, preferences, permissions, Journey, relationships, achievements, and shared history persist across Koravik.

A World may present a World-specific role, persona, title, inventory, or narrative interpretation, but it must not replace or duplicate Platform identity.

## Worlds

A World is a persistent narrative experience running on Koravik's shared World Runtime.

Examples may include:

- Epic Ordinary
- fantasy
- dinosaurs
- aliens or science fiction
- mythology
- mystery
- horror
- historical settings
- educational experiences
- community-created Worlds

A World may define:

- overarching story arcs
- chapters
- quests and objectives
- NPCs
- locations
- dialogue and choices
- relationships
- narrative flags and conditions
- rewards and World-specific items
- delayed triggers
- scheduled events
- World-specific presentation and atmosphere

Worlds use shared Platform contracts. A World must not create its own authentication, duplicate the Player, or bypass Platform security, privacy, authorization, event, audit, or persistence rules.

## Epic Ordinary

Epic Ordinary is Koravik's default first-party World.

It is the base story that welcomes a new Player into Koravik. It includes the Caretaker and the established Epic Ordinary story, tone, characters, relationships, chapters, and quests.

Epic Ordinary is not merely a tutorial and should never feel like a software onboarding checklist. It is a complete, continuing World grounded in the Player's real life.

It should feel like home. When a Player returns after spending time in another World, Epic Ordinary resumes where they left it and may acknowledge their return through the Caretaker or other appropriate narrative behavior.

Epic Ordinary should use the same World contracts available to creator-built Worlds. It may be first-party and installed by default, but it should not depend on privileged hard-coded narrative behavior that other Worlds cannot use.

## Independent World Progress

Every Player has independent progress in every World they enter.

For example:

- Epic Ordinary: Chapter 10
- Fantasy: Chapter 1
- Dinosaur World: not started
- Alien Colony: Chapter 4

Entering Fantasy for the first time begins that World's story at its own beginning. It does not reset Epic Ordinary, erase the Player's identity, or create a second Koravik account.

Returning to Epic Ordinary resumes its saved narrative state, including the current chapter, quests, choices, NPC relationships, inventory, flags, and delayed consequences.

World progress must therefore be durable, isolated by Player and World, and compatible with future World revisions.

## Story Structure

Worlds may organize narrative content through structures such as:

```text
World
  Story Arc
    Chapter
      Quest
        Objective
          Task or qualifying event
```

This is not a rigid rule that every World must use every layer. It is the common narrative vocabulary.

A Quest is a meaningful undertaking within a World. A Quest may advance through explicit tasks, but it may also advance because a qualifying event occurred elsewhere in Koravik.

Tasks are one possible progression mechanism. They are not the center of the World Engine.

## Persistent NPCs and Relationships

NPCs are persistent characters, not disposable quest dispensers.

A World may allow an NPC to remember:

- conversations
- promises
- choices
- completed or abandoned quests
- acts of care or neglect
- participation in events
- relationship history
- elapsed time
- facts intentionally made available through Platform and World contracts

Relationships may influence dialogue, quests, chapters, introductions, rewards, locations, future events, and other narrative opportunities.

Consequences do not need to be immediate. A choice or relationship change may trigger an event days or months later. The World must be able to schedule, preserve, and evaluate delayed narrative consequences without requiring long-running processes that are incompatible with the hosting environment.

## Meaningful Choice

Narrative choices should matter.

Worlds should support, where appropriate:

- genuine branching
- alternate paths
- hidden chapters
- relationship-gated content
- conditional dialogue
- delayed consequences
- optional discoveries
- persistent narrative flags
- multiple valid outcomes

Not every choice must create a permanent fork, but choices must not be presented as meaningful when the system always ignores them.

## Districts Are Part of the World

Hearth, Beacon, Gather, Health, Chronicle, Arena, Marketplace, Creator Studio, and future Districts exist on the same Platform and participate in the same event-driven life.

They are not simply tools coexisting beside the World Engine.

A District provides focused capabilities and owns its domain data, but meaningful actions within that District may affect quests, NPC relationships, story arcs, rewards, Journey, Chronicle, and future narrative triggers.

Examples:

- Logging water in Health may satisfy a quest objective assigned by an NPC.
- Writing a reflection in Chronicle may complete a task or unlock later dialogue.
- Planning or hosting an event in Gather may advance a community story arc or affect a relationship pillar.
- Participating in an organization through Beacon may influence reputation, leadership, belonging, or NPC relationships.
- Completing a household responsibility in Hearth may advance Epic Ordinary without Hearth containing narrative logic itself.

Districts publish truthful facts about what occurred. Worlds interpret those facts according to their authored rules.

## Cross-World Rewards and Invitations

Koravik may award items or discoveries associated with a different World.

For example, Epic Ordinary may reveal a fossil fragment that has no use there but becomes meaningful in a Dinosaur World. A fantasy reward may be recognized by another compatible World.

Cross-World rewards are invitations to explore. They should create curiosity and discovery rather than coercion.

A World must not require another World to complete its primary story unless the dependency is clearly disclosed and intentionally accepted. Creators must not be allowed to create hidden paywalls, abusive dependency chains, or misleading progression requirements.

## Creator Studio

Creator Studio is the authoring environment for Worlds and the experiences they contain.

Creators should be able—and encouraged—to build, test, revise, publish, and maintain Worlds without modifying Koravik's application code.

Creator Studio should ultimately support authoring and validation of:

- World metadata and presentation
- story arcs and chapters
- quests, objectives, and tasks
- NPCs and relationships
- dialogue and choices
- event subscriptions and conditions
- immediate and delayed triggers
- rewards, items, and cross-World compatibility
- locations and media
- narrative flags and state transitions
- versioning and migration of published World content

Creator freedom must operate within Platform contracts for security, privacy, accessibility, moderation, performance, and data ownership.

## Future External Signals

Future integrations such as Google Calendar, Apple Calendar, health platforms, or other user-authorized services may provide additional events to Koravik.

These integrations should not merely duplicate an external calendar or tracker. With explicit permission, their events may help generate tasks, progress quests, shape story, schedule narrative responses, or provide useful context.

External data must remain permissioned, auditable, revocable, and limited to the minimum information required for the chosen experience.

## Design Test

Before approving a feature, ask:

1. Does it preserve the Player's identity and continuity?
2. Does it respect independent World progress?
3. Does it allow District actions and Worlds to interact through contracts rather than direct coupling?
4. Does it make the World more responsive to meaningful life events?
5. Does it strengthen agency, curiosity, relationships, memory, or story?
6. Can creators use the same capability safely?
7. Does it feel like Koravik, or like an unrelated application attached to Koravik?

A necessary administrative or operational feature may not itself be narrative. That is acceptable. But product-facing capabilities must not be designed as isolated applications when they are intended to participate in the living Platform.

## Long-Term Vision

Koravik is one Platform capable of hosting many persistent Worlds.

Some Worlds may be grounded in ordinary life. Some may be fantastical, educational, communal, playful, therapeutic, reflective, or experimental. The same person can inhabit all of them without losing identity or progress.

The World responds because the Player lives.

That is the heart of Koravik.