# Healing Home Garden Unlock

**Status:** Implemented vertical product slice

## User workflow

The Garden now opens after the person has a bounded Caretaker conversation. Before that moment, the Garden remains a visible waiting door. Afterward, `/home/rooms/garden` becomes an open room for tending, recovery, and small chosen care.

The experience answers one question: where can repair and growth live without becoming another performance system?

## Routes

- `GET /home`
- `GET /home/rooms/garden`
- `POST /home/relationships/caretaker/converse`

## Unlock rule

The Garden opens from account-scoped relationship continuity: the first Caretaker conversation. This is not a streak, score, achievement, punishment, or productivity reward.

## Ownership and privacy

- Healing Home owns the Garden presentation and materialized room change.
- The triggering conversation remains Journey relationship continuity.
- Chronicle owns any reflection the person chooses to start from the Garden.
- Opening the Garden does not create Quests, Chronicle entries, Companion memory, World facts, notifications, or District records.
- Materialization stores a minimized change with the conversation id as source.

## Accessibility and states

- Locked state remains visible and useful before the conversation.
- Open Garden state renders text, room-specific copy, source ownership, and a clear return path.
- The room map labels the Garden as open and describes its purpose in text.
- Garden actions are normal links and forms; no JavaScript is required.

## Verification

Automated coverage proves locked-before/open-after behavior, minimized room change materialization, room UI copy, map state, and CSS contract selectors.
