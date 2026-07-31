# Healing Home Room Map

**Status:** Implemented vertical product slice

## User workflow

The Healing Home overview now includes a more expressive room map. A person can open `/home`, understand what each room holds, see which rooms are open or still waiting, identify the current resting room, and recognize when the Eastern Room has been restored by Epic Ordinary Chapter Two.

The experience answers one question: where can I go in this house right now?

## Route

- `GET /home`
- `GET /healing-home`

## Interaction and states

- Open rooms are labeled `Open room`.
- Locked visible rooms are labeled `Door waiting`.
- The current room is labeled `Resting here` and uses `aria-current="location"`.
- The restored Eastern Room is labeled `Restored room open`.
- Every room includes a short description of what it holds or why it is waiting.

## Ownership and privacy

The room map is presentation-only. It does not create, edit, complete, publish, or delete Quests, Chronicle entries, Companion memory, World facts, or District records. Room availability is composed from existing account-scoped Healing Home and World state.

## Accessibility

- Room state is represented with text, not color alone.
- The map has a labeled section heading.
- Every room remains a normal link and works without JavaScript.
- Current location uses both visible text and `aria-current`.

## Verification

Automated coverage proves room labels, current-room state, restored Eastern Room state, descriptive copy, and CSS contract selectors.
