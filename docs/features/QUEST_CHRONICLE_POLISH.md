# Quest and Chronicle Polish

**Status:** Implemented vertical slice

This slice connects Quests and Chronicle as one visible action-memory loop.

## Completed workflow

- `/quests` now explains that Quests are for chosen real-life action and offers direct paths to create a Quest, start a Chronicle reflection, or open the Healing Home Quest Board.
- Quest detail pages now include a reflection bridge so a person can intentionally preserve meaning in Chronicle after acting without forcing a saved entry.
- `/chronicle` now explains Chronicle as the quiet shelf for authored memory and offers direct paths to write, return to Quests, or open the Healing Home Journal Table.
- `/chronicle/new` now includes an explicit trust panel before the editor explaining that saving creates Chronicle memory only by user choice.

## Ownership boundaries

- Quests own commitments, steps, schedules, progress, completion, and lifecycle.
- Chronicle owns saved prose, tags, provenance, archive, restore, and deletion behavior.
- Hearth and Healing Home compose links and context without rewriting Quests or Chronicle.
- Drafting in Chronicle does not complete a Quest, notify anyone, create Companion memory, or change World State.

## Accessibility and responsive behavior

The polish uses semantic sections with labelled headings, visible text states, keyboard-accessible links, responsive card grids, and forced-color support in `action-memory-polish.css`.
