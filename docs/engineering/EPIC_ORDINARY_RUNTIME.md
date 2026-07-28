# Epic Ordinary Runtime Contract

**Status:** Approved
**Version:** 1.0
**Effective build:** 023

## Ownership

Epic Ordinary owns fictional narrative progress, World objectives, fictional choices, keepsakes, relationship state, and relationship-history explanations. It does not own Quests, Chronicle entries, Companion memory, account identity, or Pillar truth.

## Chapter transitions

Chapter transitions are explicit player actions. Beginning Chapter Two requires an active installation and the completed Caretaker support-style choice. Repeated begin requests are idempotent.

## World objectives

World objectives are fictional narrative state. They may resemble story tasks, but they must not create or modify real-life Quest records. Objective completion occurs in the same transaction as the choice, keepsake, narrative-state change, and relationship moment.

## Consent

The World home may show the current permission status and a minimized explanation of the latest already-committed World reaction. New reactions require current fact permission. Revocation stops future delivery without erasing retained World history.

## Emotional safety

No runtime rule may reduce relationship trust because of inactivity, absence, recurrence failure, skipped occurrences, or an empty Chronicle. Relationship changes require a durable human-readable explanation.

## Idempotency

One choice is allowed per scene by `(installation_id, scene_key)`. Repeated submissions return the committed result without duplicate keepsakes, objectives, relationship changes, or choice records.

## Rendering

World scenes use the shared application shell and visual system while retaining a distinct fictional presentation. The player must always have a clear route back to Hearth and to World permission controls.