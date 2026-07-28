# Companion Proposal Experience

**Status:** Product contract
**Version:** 1.1
**Effective build:** 015

## Player goal

Ask for help, understand a bounded suggestion, retain agency, and allow an approved action to become a real source-owned record only through explicit owner validation.

## Required surfaces

### Companion home

- explains that proposals are not saved records;
- accepts an explicit request for help;
- offers Quest and Chronicle reflection proposal entry points;
- lists recent proposals with status, owner, and version;
- does not imply autonomous monitoring or background action.

### Proposal review

- labels the artifact as a suggestion until execution;
- shows the owning module, destination, expected consequence, reasoning, and source context;
- permits editing while awaiting approval;
- requires approval of the current version;
- permits dismissal without penalty;
- distinguishes approval from execution;
- provides the destination-specific execution action only after approval;
- links to the resulting source record after successful execution.

## States

- `draft`
- `awaiting_approval`
- `approved`
- `dismissed`
- `expired`
- `executed`
- `failed`

## Interaction rules

- Editing increments the proposal version and clears prior approval.
- Approval is specific to one proposal ID and version.
- Approval alone creates no District record.
- The destination module revalidates before execution.
- Execution is idempotent; repeat submission returns the existing record.
- Dismissal creates no Quest, Chronicle entry, loss, score, or warning state.
- Expired proposals cannot execute without renewed review.
- Suggestions and source records remain visually distinct.
- Keyboard, touch, and screen-reader paths expose the same decisions.

## Quest proposal

`quest.create` proposes one personal single-action Quest. Companion supplies a title, notes, owner, consequence, reasoning, and source context. After approval, **Create Quest** invokes Quests-owned execution. Quests revalidates the proposal and creates the Quest and initial occurrence in one transaction with the execution receipt and proposal state change.

## Chronicle reflection proposal

`chronicle.reflection.create` proposes one private reflection. The review surface shows source context, Chronicle as the destination, privacy consequence, editable title and body, and a Companion-draft voice label. The person must approve the current version and then explicitly choose **Save to Chronicle**. Chronicle performs final validation, saves the entry, and owns all later entry lifecycle behavior.

## Failure and recovery

- A failed execution does not erase or silently rewrite the proposal.
- No executed status is shown before the destination record commits.
- Repeat execution after success resolves to the existing destination record.
- Audit history preserves creation, approval, dismissal, and execution as separate facts.