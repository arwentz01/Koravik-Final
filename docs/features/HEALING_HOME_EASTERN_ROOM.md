# Healing Home Eastern Room

**Status:** Implemented vertical product slice

## User workflow

When Epic Ordinary Chapter Two has a completed Eastern Room purpose choice, the Healing Home now opens the visible Eastern Room for that account. A person can open `/home`, see the Eastern Room as open, enter `/home/rooms/eastern_room`, review what changed, see the fictional keepsake that belongs there, rest in the room, save a private room note, and return to Epic Ordinary.

The experience answers one question: what did the restored Eastern Room become?

## Routes

- `GET /home`
- `GET /home/rooms/eastern_room`
- `POST /home/rooms/eastern_room/rest`
- `POST /home/rooms/eastern_room/note`
- `POST /home/rooms/eastern_room/note/clear`

## Ownership and privacy

- Epic Ordinary owns the Chapter Two choice, fictional objective, relationship moment, and fictional keepsake.
- Healing Home only materializes account-scoped presentation state: the room opens, one minimized room change appears, and the World keepsake is displayed in that room.
- No real-life Quest, Chronicle entry, Companion memory, notification, or District fact is created by opening the room.
- The materialized room state is scoped to the account whose World installation contains the choice.

## Accessibility and states

- Before the Chapter Two choice exists, the Eastern Room remains a visible locked room with a useful unavailable state.
- After the choice exists, the room has specific copy, ownership explanation, room memory, keepsake display, and a clear continuation link.
- The open/locked distinction is rendered with text, not color alone.
- The room page keeps the existing responsive room grid and private room-note controls.

## Verification

Automated coverage proves the Eastern Room opens from an account-owned Epic Ordinary choice, materializes the room change and keepsake, rejects cross-account leakage, and renders source-ownership UI copy.
