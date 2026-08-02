# Epic Ordinary Reclamation Audit

**Status:** Implemented reclamation record  
**Date:** 2026-08-01  
**Koravik-Final authority:** `/Applications/MAMP/htdocs/Koravik` on `main`  
**Approved salvage source:** `/Applications/MAMP/htdocs/Epic-Ordinary` on `main`

## Reclamation audit

Koravik-Final remains the implementation authority. The legacy Epic-Ordinary repository was inspected as an approved salvage source for product identity, content direction, and experience patterns. Legacy schemas, routes, framework assumptions, admin surfaces, reward economies, and player/household identity models were not imported.

Read from Koravik-Final before implementation:

- `README.md`
- `docs/README.md`
- `docs/FOUNDATIONAL_DECISIONS.md`
- `docs/canonical/CONSTITUTION.md`
- `docs/canonical/WORLD_ENGINE.md`
- `docs/product/EPIC_ORDINARY_CHAPTER_TWO.md`
- `docs/product/WORLD_PROGRESS_AND_REACTIONS.md`
- `docs/engineering/EPIC_ORDINARY_RUNTIME.md`
- `docs/IMPLEMENTATION_HANDOFF.md`

Read from Epic-Ordinary before reclamation:

- `README.md`
- `docs/HANDOFF.md`
- `docs/NORTH_STAR.md`
- `docs/CANON.md`
- `docs/THE_BOOK_OF_MOMENTS.md`
- `docs/MOMENT_ENGINE.md`
- `docs/ROOM_BIBLE.md`
- `docs/CHARACTER_BIBLE.md`
- `docs/LORE_BIBLE.md`
- `docs/BUILD_WE3C_PERSISTENT_HOME_OBJECTS.md`
- `docs/BUILD_WE5B_INTERACTIVE_AMBIENT_PROPS.md`
- `docs/BUILD_WE8F_MOMENTS_REMEMBERED_LIBRARY.md`
- `sql/feature-train-6e/001_tiny_joys.sql`

## Reuse ledger

| Legacy source | Reclaimed into Koravik-Final | Boundary preserved |
| --- | --- | --- |
| `NORTH_STAR.md` | Healing Home treated as emotional flagship: ordinary care restores a place that welcomes return. | No productivity dashboard, shame loop, or streak pressure imported. |
| `CANON.md` | “Evidence, not rewards,” Caretaker lantern, Chronicle as long memory, and “nothing important happens off-screen.” | Fiction remains World/Journey state and does not rewrite District truth. |
| `THE_BOOK_OF_MOMENTS.md` | `/home/moments` and Moment-facing copy distinguish ambience, remembered moments, and Chronicle preservation. | No generic notification service or repeated interruption queue. |
| `MOMENT_ENGINE.md` | Reclamation surfaces meaningful changes as ambient discoveries and Chronicle starting contexts. | Chronicle writes remain explicit player action. |
| `ROOM_BIBLE.md` | Room identity strengthened through Hearth Room, Garden Window, Quiet Corner, Memory Shelf, seasonal and weather language. | Rooms remain invitations, not menus or required chores. |
| `CHARACTER_BIBLE.md` | Caretaker memory emphasizes noticing, welcoming, brass lantern, short gentle observations. | Caretaker is not therapist, quest giver, assistant, boss, or authority. |
| `LORE_BIBLE.md` | Home remembers through objects, rooms, light, companions, weather, and soft mystery. | Wonder stays grounded; no chosen-one or spectacle mythology imported. |
| `BUILD_WE3C_PERSISTENT_HOME_OBJECTS.md` | Reclaimed artifacts are durable Healing Home keepsakes and source-labeled evidence. | No legacy `user_home_objects` schema imported. |
| `BUILD_WE5B_INTERACTIVE_AMBIENT_PROPS.md` | `/home/tiny-joys` presents small interactions such as kettle steam and rain on herbs. | No score, progression credit, or task creation. |
| `BUILD_WE8F_MOMENTS_REMEMBERED_LIBRARY.md` | `/home/moments` is replay-safe remembered continuity. | Does not requeue arrival moments. |
| `001_tiny_joys.sql` | Tiny Joys concept reclaimed as non-persistent player-facing exploration. | No legacy SQL table imported; no points or household/task credit. |

## Implementation summary

The sprint adds an idempotent Epic Ordinary reclamation materializer inside `JourneyService`. When an account has active Epic Ordinary installation, it seeds source-labeled Healing Home changes, keepsakes, and Caretaker memories using existing Koravik-Final tables and uniqueness constraints.

Player-facing routes added:

- `/home/reclamation`
- `/home/discoveries`
- `/home/tiny-joys`
- `/home/seasons`
- `/home/moments`

The Healing Home command center now links these surfaces under “Reclaimed wonder.”

## Explicit non-reuse

- No legacy PHP controllers or views were copied.
- No legacy database tables were imported.
- No admin/creator navigation was exposed to players.
- No household-first identity assumptions were imported.
- No rewards, points, streaks, or punitive absence mechanics were imported.
- No Companion memory or Chronicle prose is silently consumed.

## Security, privacy, consent, and accessibility notes

- Routes require account authentication through existing Healing Home controller flow.
- All rendered dynamic values are escaped.
- Database writes use prepared statements and existing account-scoped tables.
- Chronicle integration is link-only until the player submits Chronicle forms.
- Reclaimed objects identify fictional/source-aware meaning and do not claim real-life authority.
- Copy avoids shame, diagnosis, manipulation, and streak pressure.
- The pages use existing semantic panels, headings, links, and server-rendered navigation.
