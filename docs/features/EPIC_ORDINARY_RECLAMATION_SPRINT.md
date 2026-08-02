# Epic Ordinary Reclamation Sprint

**Status:** Implemented in current working tree  
**Date:** 2026-08-01  
**Scope:** Major product reclamation sprint, not a micro-slice

## Executive summary

This sprint restores Epic Ordinary’s emotional identity inside Koravik-Final without importing legacy application architecture. The Healing Home now exposes a reclamation hearth, discoveries, tiny joys, seasonal life, remembered Moments, source-labeled evidence objects, and Caretaker continuity grounded in the approved Epic-Ordinary source repository at `/Applications/MAMP/htdocs/Epic-Ordinary`.

The result is a warmer, more explorable Healing Home: ordinary life can leave visible fictional traces, the Caretaker remembers softly, objects feel like evidence rather than rewards, and Chronicle remains the explicit long-memory destination.

## Major improvements

- Healing Home reclamation: `/home/reclamation` gives the player a dedicated doorway into reclaimed Epic Ordinary identity.
- Lore and world identity: recovered canon language appears in discoveries, room ambience, Moment copy, and Caretaker memory.
- Artifacts and collections: source-labeled keepsakes include the Caretaker’s brass lantern, Quiet Hearth coal, robin feather, open book, and folded Caretaker note.
- NPC and relationship memory: Caretaker memories are seeded with human-readable explanations and no authority claims.
- Moments and visible change: reclaimed changes appear as durable room changes, not disappearing banners.
- Chronicle integration: `/home/moments` and `/home/tiny-joys` provide Chronicle starting links while preserving explicit save consent.
- Exploration and discovery: `/home/discoveries` exposes world identity as optional discovery cards.
- Tiny joys and mini experiences: `/home/tiny-joys` reclaims non-scoring ambient prop interactions.
- Seasonal and ambient life: `/home/seasons` gives rooms weather/time identity without diagnosis or pressure.
- Visual/content cohesion: Healing Home’s command center now has a “Reclaimed wonder” section.

## Reuse ledger

See `docs/reclamation/EPIC_ORDINARY_RECLAMATION_AUDIT.md` for the full reuse ledger.

Highlights:

- `NORTH_STAR.md`: reclaimed Healing Home as flagship.
- `CANON.md`: reclaimed evidence-not-rewards, Caretaker lantern, Chronicle, and off-screen change rule.
- `THE_BOOK_OF_MOMENTS.md` and `MOMENT_ENGINE.md`: reclaimed ambience, remembered Moments, and Chronicle preservation language.
- `ROOM_BIBLE.md`: reclaimed room emotional identity.
- `CHARACTER_BIBLE.md`: reclaimed Caretaker presence and voice boundaries.
- `LORE_BIBLE.md`: reclaimed grounded, softly mysterious home logic.
- WE3C/WE5B/WE8F/Tiny Joys: reclaimed persistent evidence objects, ambient props, Moments Remembered, and non-scoring tiny joys.

## Changed implementation surfaces

- `src/Platform/Journey/JourneyService.php`
- `src/Platform/Journey/HealingHomeController.php`
- `tests/ReleaseSuite.php`
- `docs/features/EPIC_ORDINARY_RECLAMATION_SPRINT.md`
- `docs/reclamation/EPIC_ORDINARY_RECLAMATION_AUDIT.md`
- `docs/IMPLEMENTATION_HANDOFF.md`

## Migrations

No new migration was required. The sprint uses existing Koravik-Final Healing Home, keepsake, and relationship-memory tables with idempotent uniqueness constraints.

## Tests

Release coverage adds `Epic Ordinary reclamation restores wonder without breaking boundaries`, checking the materializer, routes, documentation, audit record, and handoff markers.

## Demo routes

- `/home`
- `/home/reclamation`
- `/home/discoveries`
- `/home/tiny-joys`
- `/home/seasons`
- `/home/moments`
- `/home/keepsakes`
- `/home/timeline`
- `/home/relationships/caretaker`

## Security, privacy, consent, and accessibility checks

- Account authentication remains required.
- SQL uses parameterized statements.
- Output is escaped.
- Chronicle preservation remains explicit.
- No private notes, Companion memory, Health records, Gather communication, Beacon attendance, or account secrets are silently imported.
- No shame, streak, diagnosis, reward-pressure, or punitive absence copy.
- Pages use existing accessible server-rendered structures and links.

## Known limitations

- This is a reclaimed first pass, not a full Moment Engine runtime queue.
- Tiny Joys are explorable and Chronicle-linkable but do not yet persist individual notice logs.
- Seasonal life is authored presentation, not a weather/time simulation.
- Legacy visual assets were not imported; this sprint focuses on durable product behavior and content cohesion.

## Next sprint

Build a proper Koravik-Final Moment candidate model: source submissions, queue throttling, one-at-a-time arrival scenes, archive/read states, and Chronicle preservation review, all under Koravik-Final consent and World ownership rules.
