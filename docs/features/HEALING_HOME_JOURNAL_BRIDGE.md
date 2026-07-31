# Healing Home Journal Table Reflection Bridge

**Status:** Implemented vertical product slice

## User workflow

The Journal Table now lets a person begin a Chronicle reflection from inside Healing Home. From `/home/rooms/journal_table`, the person can choose **Start a reflection**, land on `/chronicle/new` with safe context/title/tag hints, and write the entry in Chronicle.

The experience answers one question: can the house invite reflection without taking over Chronicle?

## Routes

- `GET /home/rooms/journal_table`
- `GET /chronicle/new?context=healing_home_journal_table`
- `POST /chronicle/entries`

## Ownership and privacy

- Chronicle remains the source owner for saved entries, validation, privacy, archive, and deletion behavior.
- Healing Home only provides a starting link with safe context hints.
- The bridge does not create an entry until the person submits Chronicle’s form.
- No Companion memory, World fact, notification, Quest, or Healing Home state is created by opening the form.

## Accessibility and states

- The Journal Table renders a visible **Start a reflection** action.
- Chronicle shows a visible “Started from Healing Home” context panel.
- The form remains server-rendered, labeled, CSRF-protected, and JavaScript-independent.
- Prefilled title and tags are ordinary editable fields.

## Verification

Automated coverage proves the Journal Table link, Chronicle context panel, prefilled safe fields, and explicit Chronicle ownership copy.
