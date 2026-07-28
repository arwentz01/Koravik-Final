# Build Charter

## Purpose

This charter governs implementation after Blueprint v1.0 is approved. It exists to keep Koravik faithful to its purpose while allowing disciplined technical evolution.

## Core rule

> The blueprint is a product. The software is its implementation.

Code does not become product authority merely because it exists. When code and approved documentation disagree, work pauses until the conflict is resolved deliberately.

## Build principles

1. Build the platform, not disconnected pages.
2. Experience precedes technology.
3. Deliver one coherent vertical slice at a time.
4. Preserve domain ownership.
5. Publish facts only after successful commits.
6. Minimize data crossing module and World boundaries.
7. Require explicit approval for consequential Companion actions.
8. Make privacy, permission, automation, and World reactions understandable.
9. Treat accessibility and security as completion criteria.
10. Prefer simple, explainable systems over clever ones.
11. Keep shared-hosting operation finite and observable.
12. Update governing documentation and tests with each accepted change.

## Three questions rule

Before implementation, every capability must answer:

1. Why does it exist?
2. Who owns it?
3. What fact or event does it create, if any?

If these answers are unclear, implementation is premature.

## No-magic rule

Nothing important should happen without an understandable reason. Companion proposals, World reactions, reminders, permissions, scheduled work, and automated state changes must identify their source and consequence.

## Ten-year test

Design decisions should remain coherent if Koravik serves one million accounts and supports fifty Worlds. This does not justify premature distribution; it requires stable ownership, contracts, migrations, identifiers, and consent boundaries.

## Vertical-slice rule

A slice is complete only when it includes the necessary experience, authorization, persistence, event behavior, accessibility, tests, error recovery, documentation, and operational handling.

Incomplete foundations must not be hidden beneath additional feature volume.

## Change governance

Product or architectural changes require one of:

- an approved revision to an authoritative blueprint document;
- an accepted architecture decision record;
- a documented clarification that does not change existing intent.

Implementation convenience alone is not sufficient reason to alter the product model.

## Build 001 mission

Create the smallest working version of Koravik that already feels like Koravik.

The first proof must include secure identity, the shared shell, Hearth orientation, one Quests-owned action, transactional platform-event publication, an explainable Epic Ordinary reaction, durable isolated World State, and safe return and resume.

## Success measure

Koravik builds are successful when they reduce friction in real life, preserve agency and meaning, and remain maintainable, secure, accessible, and explainable.