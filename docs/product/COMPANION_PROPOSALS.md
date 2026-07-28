# Companion Proposal Experience

**Status:** Product contract
**Version:** 1.0
**Effective build:** 014

## Player goal

Ask for help, understand a bounded suggestion, and retain final agency over whether any source record changes.

## Required surfaces

### Companion home

- explains that proposals are not saved records;
- accepts an explicit request for help;
- lists recent proposals with status, owner, and version;
- does not imply autonomous monitoring or background action.

### Proposal review

- labels the artifact as a suggestion;
- shows the owning District;
- shows expected consequence, reasoning, and source context;
- permits editing while awaiting approval;
- requires approval of the current version;
- permits dismissal without penalty;
- distinguishes approval from execution.

## States

- `draft`
- `awaiting_approval`
- `approved`
- `dismissed`
- `expired`
- `executed`
- `failed`

Build 014 uses awaiting approval, approved, and dismissed while reserving the complete lifecycle for owner execution in Build 015.

## Interaction rules

- Editing increments the proposal version and clears prior approval.
- Approval is specific to one proposal ID and version.
- Dismissal creates no Quest, Chronicle entry, loss, score, or warning state.
- Expired proposals cannot execute without renewed review.
- Suggestions and source records must remain visually distinct.
- Keyboard, touch, and screen-reader paths must expose the same decisions.

## Initial proposal type

`quest.create` proposes one personal single-action Quest from the player’s request. Companion does not search private records for this initial flow. The proposal includes a title, optional notes, owner, consequence, reasoning, and source-context explanation.