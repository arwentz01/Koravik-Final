# Healing Home Keepsake Shelf

**Status:** Implemented vertical product slice

## User workflow

The Keepsake Shelf now has its own focused surface. A person can open `/home/keepsakes`, browse displayed keepsakes, open a keepsake detail page, understand its source owner and room placement, and return to the relevant room.

The experience answers one question: what is this small thing, and why is it here?

## Routes

- `GET /home/keepsakes`
- `GET /home/keepsakes/{keepsakeId}`

## Ownership and privacy

- Healing Home owns the displayed keepsake presentation record.
- Source ownership remains visible: World choice, World reaction, Quest resolution, or another source type.
- Keepsakes are not currency, trophies, achievements, or performance proof.
- Opening a keepsake does not create Quests, Chronicle entries, Companion memory, World facts, notifications, or District records.
- Detail reads are account-scoped.

## Accessibility and states

- The shelf has a clear empty state.
- Each keepsake is a normal link with text source and room labels.
- Detail pages include source owner, room, creation time, and boundary language.
- The interface works without JavaScript.

## Verification

Automated coverage proves shelf rendering, detail provenance, empty state, account scoping, and boundary copy.
