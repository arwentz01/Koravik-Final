# Builds 148–157 — Core Loop Depth

**Status:** Implemented vertical slice batch

This batch makes Koravik's central loop more tangible: media can attach to source records, recurrence edits rebuild future occurrences, Quests expose a timeline, Chronicle can search and review source proposals, Companion memory and proposals show provenance, Hearth can summarize private Health state safely, and World reactions explain their source and permission state.

## Slices covered

1. Media Attachments in District Records
2. Quest Recurrence Occurrence Rebuild
3. Quest Detail Timeline
4. Chronicle Reflection Proposal Sources
5. Chronicle Entry Search + Filters
6. Companion Memory Provenance Detail
7. Companion Suggestion-to-Action Review Polish
8. Health-to-Hearth Private Signal Summary
9. Worlds Reaction Explanation Polish
10. Daily Focus + Quest Completion Loop Polish

## Product outcome

A person can move through the loop — focus, act, complete, optionally reflect, inspect World response, and return to Hearth — with more visible provenance and fewer hidden jumps between modules.

## Implementation notes

- Migration `103_core_loop_media_timeline.sql` adds media attachment links and Quest timeline events.
- Platform Media can link media references to Quests, Chronicle, Gather, Beacon, and Health records without taking over source ownership.
- Recurrence updates now remove pending future occurrences and regenerate the upcoming schedule while preserving completed history.
- `/quests/{id}/timeline` shows source-owned Quest timeline evidence.
- `/chronicle/search` adds basic Chronicle search and filters.
- `/chronicle/proposals` can create explicit proposals from Quest completion, Gather follow-up, Companion, and Healing Home Journal contexts.
- `/companion/memories/{id}` shows memory provenance, status, and audit history.
- Hearth shows a private, non-diagnostic Health signal summary without leaking feeling words or private notes.
- World reaction detail shows permission and review state alongside the received minimized fact and excluded private data.

## Boundaries

This batch does not implement binary upload storage, external integrations, medical interpretation, automatic reflection saving, automatic Companion execution, or public sharing changes. Each source module still owns its own records.
