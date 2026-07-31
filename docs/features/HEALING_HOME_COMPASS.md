# Healing Home Compass

Status: implemented vertical product slice.

This slice adds orientation surfaces that help people choose a Healing Home path by direction, mood, need, consent boundary, or product surface.

## Completed workflow

An authenticated person can open `/home`, choose House Compass, Moods, Rooms by Need, Consent Map, or House Changelog, and use those read-only surfaces to navigate the home safely.

## Product behavior

- House Compass orients rooms by meaning, story, care, making, and trust.
- Moods explains quiet morning, green dusk, and workshop lamplight as presentation-only language.
- Rooms by Need maps clarity, continuity, repair, expression, and safety to appropriate rooms.
- Consent Map names what requires explicit approval and what is excluded.
- House Changelog explains the product surfaces now available inside the Healing Home.

## Boundaries

Compass surfaces are read-only orientation. They do not create Quests, Chronicle entries, Companion memory, Health records, Beacon attendance, Gather communication, notifications, diagnoses, score state, hidden automation, or cross-account access.

## Verification

Covered by the release suite test `Healing Home room expansion supports making, welcome, meaning, tending, and privacy`, extended with compass contracts.
