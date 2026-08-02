# Complete Quest Management

**Status:** Implemented vertical slice

Quests now has a management surface across active, paused, and archived records, plus focused edit and read-only occurrence-history screens. A person can change title, notes, purpose, and next step; reschedule the next available occurrence; inspect completed, skipped, dismissed, and moved occurrences; and continue using existing pause, resume, archive, restore, completion, steps, and recurrence behavior.

Quest history is preserved when editable details change. All reads and writes remain account-scoped, CSRF-protected where consequential, and Quests-owned.
