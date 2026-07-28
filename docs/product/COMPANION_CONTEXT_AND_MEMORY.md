# Companion Context and Memory

**Status:** Product contract
**Effective build:** 017

Companion may use only context the player explicitly selects under an enabled permission. Permissions do not authorize background scanning.

Supported categories are selected Quest context, selected Chronicle context, Pillar summaries, accessibility preferences, and approved Companion memory.

Context use is minimized and recorded with source module, source type, source identifier when applicable, summary, and one-time scope. Companion memory remains separate from Chronicle, Quests, Account identity, and World State.

A memory must be explicitly approved, may be disabled or deleted, and displays provenance. Revocation stops future use but does not rewrite historical proposals or audit records.
