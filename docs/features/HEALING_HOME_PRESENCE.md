# Healing Home Presence

Status: implemented vertical product slice.

This slice makes Healing Home feel more inhabited and easier to re-enter without requiring the person to already know which room they want.

## Completed workflow

An authenticated person can open `/home`, choose Today in the House, browse the room directory, read the source glossary, and use the guide’s threshold reminders before entering a room.

## Product behavior

- Today in the House summarizes current room, atmosphere, latest threshold, and a suggested gentle route.
- Room Directory lists every known room with symbolic identity, state, door copy, and source-aware purpose.
- Source Glossary explains Quests, Chronicle, Worlds, Journey relationships, and deliberately excluded sources.
- The house guide includes quick access to these orientation surfaces plus threshold reminders.

## Boundaries

Presence surfaces are read-only orientation. They do not create Quests, Chronicle entries, Companion memory, Health records, Beacon attendance, Gather communication, notifications, diagnoses, score state, or hidden automation.

## Verification

Covered by the release suite test `Healing Home room expansion supports making, welcome, meaning, tending, and privacy`, extended with presence contracts.
