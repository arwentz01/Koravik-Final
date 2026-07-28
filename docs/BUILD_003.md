# Build 003 — Quest Lifecycle and Recurrence

**Status:** Validation

Build 003 combines Quest lifecycle management with relational recurrence and occurrence-based completion.

## Player-visible outcome

A person can create one-time or repeating Quests, including daily, every X days, weekly on one or more selected weekdays, every X weeks on selected weekdays, monthly, and yearly patterns. Weekly rules support combinations such as Monday, Wednesday, and Friday.

Quests can be paused, resumed, archived, and completed one occurrence at a time. The definition remains durable while occurrence history and platform events remain independent.

## Technical boundary

- Recurrence rules use relational tables rather than loose JSON files.
- Weekday selections use one compact join table.
- Generated occurrences are bounded and cron compatible.
- Completion publishes the approved minimized event through the transactional outbox.
- Existing Epic Ordinary interpretation remains idempotent.
