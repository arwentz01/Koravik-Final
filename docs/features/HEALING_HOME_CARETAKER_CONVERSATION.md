# Healing Home Caretaker Conversation

**Status:** Implemented vertical product slice

## User workflow

The Caretaker relationship page now supports a small, bounded conversation from `/home/relationships/caretaker`. A person can open the Healing Home, choose the Caretaker from relationship memory, select the kind of moment they need, and see the response preserved in recent conversation history.

The experience answers one question: how can the relationship feel present without taking authority from the person?

## Routes

- `GET /home/relationships/caretaker`
- `POST /home/relationships/caretaker/converse`

## Interaction model

The conversation offers four deliberately bounded choices:

- share gratitude;
- ask to repair;
- disagree honestly;
- sit quietly.

Each choice has authored response copy. There is no generated advice, hidden scoring, or “correct” path.

## Ownership and privacy

- Conversation records belong to account-scoped Journey/Healing Home relationship continuity.
- Conversations do not create Quests, Chronicle entries, Companion memory, notifications, World facts, or District records.
- The Caretaker may display a minimized remembered relationship context, but does not inspect private Quest notes, Chronicle prose, Companion memory, Health records, Beacon attendance, or Gather communication.
- Conversation submission records minimized audit evidence with only the character key.
- Account export includes relationship conversations, and account closure deletes them with Healing Home composition.

## Accessibility and states

- The relationship page has a visible conversation form, persistent button labels, recent conversation history, and an empty history state.
- The form is CSRF-protected and works without JavaScript.
- Invalid choices fail closed with a visible flash message.
- The history is rendered with text and timestamps rather than color-only meaning.

## Verification

Automated coverage proves persistence, account scoping, bounded remembered context, UI rendering, audit evidence, export inclusion, and invalid-choice rejection.
