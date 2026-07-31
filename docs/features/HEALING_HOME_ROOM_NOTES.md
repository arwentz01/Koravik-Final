# Healing Home Room Notes

**Status:** Implemented
**Version:** 1.0
**Baseline date:** July 30, 2026

## Player outcome

Each open Healing Home room can now hold one small private note. The person can save or clear a note from the room page to name why they are resting there, what the room is holding, or what they want to remember in that room.

The experience answers one question: what private intention belongs in this room right now?

## Routes

- `POST /home/rooms/{roomKey}/note`
- `POST /home/rooms/{roomKey}/note/clear`

Existing routes remain:

- `GET /home`
- `GET /healing-home`
- `GET /home/rooms/{roomKey}`
- `POST /home/rooms/{roomKey}/rest`
- `GET /home/relationships/{characterKey}`

## Ownership and privacy

- Room notes belong to Healing Home room state.
- A note does not create a Quest, Chronicle entry, World fact, Companion memory, or notification.
- Notes are account-scoped and available only through the authenticated account.
- Locked rooms cannot receive notes.
- Notes are limited to 600 characters.
- Save and clear actions append minimized audit evidence with the room key only.

## Persistence

Migration `096_healing_home_room_notes.sql` adds `note_text` and `note_updated_at` to `healing_home_rooms`.

Room notes are included in Account export through the `healing_home_rooms` section. Account closure explicitly deletes Healing Home state and room records because closure anonymizes the account instead of deleting the account row.

## Accessibility and responsive behavior

- room note fields use a persistent label;
- save and clear are native POST actions with CSRF protection;
- validation errors return to the room with visible status text;
- the note panel reflows to one column on narrow screens;
- copy clearly states the note is private room state and not Chronicle.

## Verification

Automated coverage proves schema presence, note persistence, UI rendering, audit evidence, export inclusion, length validation, locked-room denial, and clearing.

## Known limitations

- Room notes are single-note fields, not a timeline.
- Notes do not yet influence World dialogue, Caretaker response, search, or Companion context.
