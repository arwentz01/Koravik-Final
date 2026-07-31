# Healing Home Room Detail

**Status:** Implemented
**Version:** 1.0
**Baseline date:** July 30, 2026

## Player outcome

Healing Home rooms now open into focused room detail pages. A person can step from `/home` into the Quest Board, Fireplace, Journal Table, Entry Hall, Companion Chair, and other visible rooms, understand what is gathered there, and continue into the owning District or World surface.

The experience answers one question: what does this room hold, and where do I act next?

## Routes

- `GET /home/rooms/{roomKey}`

Existing routes remain:

- `GET /home`
- `GET /healing-home`
- `GET /home/relationships/{characterKey}`

## Ownership and privacy

- Quest Board previews the current owned Quest and links to Quests for lifecycle actions.
- Journal Table previews owned Chronicle entries and links to Chronicle.
- Fireplace and keepsake rooms surface account-owned World and Journey context while linking back to World progress.
- Companion Chair explains proposal boundaries and links to Companion.
- Locked rooms render visible but unavailable states without inventing obligations or punishments.

Healing Home does not create, complete, edit, archive, publish, or delete source records.

## Persistence

No new schema was required. `JourneyService::roomForAccount()` reads existing Healing Home and source-owned data. Opening a room is read-only; room presence is changed only through the explicit room-rest action documented in `HEALING_HOME_ROOM_PRESENCE.md`.

## Accessibility and responsive behavior

- room pages have a single `h1`;
- room memory and ownership panels are labeled with visible headings;
- locked room state includes a clear return path;
- no room action exists only through hover or color;
- room grids collapse to one column on narrower screens.

## Verification

Automated coverage proves overview room links, owned Quest Board composition, source ownership copy, locked-room rendering, and invalid room-key rejection.

## Known limitations

- Room detail pages remain read-only composition surfaces.
- Room art is still CSS-rendered foundation art.
- Health and Gather rooms remain deferred until their source-owned front-facing workflows are ready to compose here.
