# Hearth Dashboard Polish

Status: implemented vertical product slice.

This slice makes Hearth a clearer front door after the Healing Home work: a calm orientation dashboard that helps a person choose where to go next without taking ownership from the source modules.

## Completed workflow

An authenticated person can open `/hearth`, see today’s Daily Focus, choose a doorway for acting, reflecting, entering Healing Home, or continuing Worlds, and read a trust strip explaining what Hearth does and does not own.

## Product behavior

- Hearth adds hero actions for Healing Home, today’s focus, and the guide.
- Hearth includes an orientation grid for Act, Reflect, Enter the house, and Continue story.
- Hearth keeps Organization and Household supporting panels available.
- Hearth includes a trust strip naming composition boundaries.

## Boundaries

Hearth remains an orientation surface. This slice does not create Quests, Chronicle entries, Companion memory, World facts, Healing Home state, notifications, hidden scoring, or hidden automation.

## Verification

Covered by the release suite test `Hearth daily focus composes only owned Quests`, extended with dashboard polish and CSS contracts.
