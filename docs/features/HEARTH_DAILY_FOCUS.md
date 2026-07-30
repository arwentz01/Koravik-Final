# Hearth Daily Focus

**Status:** Implemented vertical product slice
**Owner:** Hearth
**Source owner for selected actions:** Quests

## User outcome

From Hearth, a signed-in person can choose a short intention and up to three
available Quests for the current day. The saved composition immediately
replaces the generic “What matters now” list with one calm, ordered focus
surface. They can open the source Quest, revise the composition, or clear it.

## Architecture decision

Hearth owns only the daily composition:

- one Account and local-date scoped focus;
- an optional 180-character intention;
- ordered references to at most three Quest occurrences.

Quests continues to own titles, schedules, next steps, completion, recurrence,
and history. Hearth validates Account ownership and current Quest availability
inside the save transaction and never copies or mutates Quest truth.

No Platform Event is published because selecting a presentation preference is
not a meaningful completed life fact. Saves and clears append minimized audit
records.

## Interface contract

- `GET /hearth/focus` renders the complete editor.
- `POST /hearth/focus` validates and saves today’s composition.
- `POST /hearth/focus/clear` clears only today’s composition.
- `/hearth` renders the saved focus or a useful empty state.

The workflow works without JavaScript. Validation preserves entered values,
uses an accessible error summary, and returns HTTP 422. Unexpected save
failures distinguish unsaved choices from committed state and return HTTP 503.
The editor reflows without horizontal page overflow at a 375-pixel viewport,
retains one `h1` and one `main`, and keeps the skip link first in focus order.

## Data and lifecycle

Migration `093_hearth_daily_focus.sql` adds:

- `hearth_daily_focus`;
- `hearth_daily_focus_entries`.

Migration `094_hearth_daily_focus_lifecycle.sql` adds the Hearth lifecycle step
to account closures that were already pending when this slice was deployed.

Focus records cascade with Account closure. Entry references cascade if a Quest
occurrence is deleted. Account exports include the focus and its references.

## Verified journey

The local XAMPP browser journey was exercised:

1. sign in;
2. see the Daily Focus empty state on Hearth;
3. open the editor;
4. set an intention and select an available Quest;
5. save and see the ordered composition on Hearth;
6. reopen the editor with values preserved;
7. clear the focus and return to the empty state;
8. repeat the editor review at mobile width with no horizontal page overflow.
