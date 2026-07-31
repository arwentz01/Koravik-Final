# Healing Home Room Presence

**Status:** Implemented
**Version:** 1.0
**Baseline date:** July 30, 2026

## Player outcome

Healing Home now lets a person intentionally choose which open room they are resting in. Visiting a room is browsing. Choosing **Rest here** is a deliberate, CSRF-protected action that updates the current room and reflects that state back on the room page and the Healing Home overview.

The experience answers one question: where am I resting in the house right now?

## Routes

- `POST /home/rooms/{roomKey}/rest`

Existing routes remain:

- `GET /home`
- `GET /healing-home`
- `GET /home/rooms/{roomKey}`
- `GET /home/relationships/{characterKey}`

## Ownership and privacy

- Room presence belongs to Healing Home state.
- Resting in a room does not create, complete, edit, publish, or delete a source District record.
- Locked rooms cannot be selected.
- Invalid room keys are rejected before database mutation.
- Each rest action records minimized audit evidence with the room key only.

## Persistence

No new schema was required. The slice uses `healing_home_state.current_room` and records a minimized `healing_home.room.rested` audit action.

## Accessibility and responsive behavior

- current room state is shown with text, not color alone;
- room detail pages show either **Rest here** or **You are resting here**;
- the POST action uses native form submission with CSRF protection;
- invalid CSRF renders a useful recovery page;
- the home room list uses `aria-current="location"` for the active resting room.

## Verification

Automated coverage proves visiting a room does not change rest state, resting in an open room persists and audits the state, the current room renders on the overview and detail page, and locked rooms cannot be selected.

## Known limitations

- Presence is a single account-local room marker, not a multiplayer presence system.
- Resting in a room does not yet influence World dialogue or room-specific narrative availability.
