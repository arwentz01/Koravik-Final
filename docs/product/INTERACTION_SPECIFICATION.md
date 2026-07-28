# Koravik Interaction Specification

**Status:** Draft for Blueprint v1.0 review  
**Version:** 1.0-draft  
**Owner:** Product and UX Architecture  
**Last reviewed:** 2026-07-28

## 1. Purpose

This document defines how people interact with Koravik across Districts, Worlds, Companion, and shared platform surfaces. It translates the product philosophy into behavioral rules that implementation must preserve.

Koravik should reduce friction without hiding consequences. The interface may be gentle, but it must never be vague about ownership, privacy, permissions, state changes, or downstream effects.

## 2. Core interaction model

Every meaningful interaction should help a person understand:

1. Where they are.
2. What they can do.
3. What will happen.
4. Who owns the resulting record.
5. Whether anything else may react.
6. How to recover, revise, or leave.

A screen should normally present one primary question or decision. Secondary actions may exist, but they must not compete visually with the primary purpose.

## 3. Interaction states

Every interactive surface must deliberately support the following states when applicable:

- **Ready:** The person can understand and act.
- **Loading:** Progress is visible without blocking unrelated navigation unnecessarily.
- **Empty:** The surface explains why it is empty and offers a meaningful next step.
- **Success:** The result is confirmed in plain language.
- **Partial success:** Completed and incomplete portions are distinguished.
- **Validation error:** The problem appears next to the relevant field and in an accessible summary.
- **System error:** The person is told what was preserved, what failed, and what they can do next.
- **Offline or interrupted:** Unsaved and committed state are clearly distinguished.
- **Unauthorized:** The interface explains that access is unavailable without exposing protected information.
- **Archived or unavailable:** Historical context is preserved when appropriate, with destructive actions prevented.

Blank pages, indefinite spinners, silent failures, and unexplained redirects are not acceptable states.

## 4. Navigation and orientation

Koravik must preserve orientation as people move between Hearth, Districts, Worlds, and overlays.

Required behavior:

- The current area is visibly identified.
- Browser back and forward navigation remain meaningful.
- Deep links return to the intended record after authentication when authorized.
- Closing a panel or modal returns focus to the initiating control.
- Returning from a detail view restores the prior list position and filters when feasible.
- World context is visually distinct from real-life District context.
- Companion must never make it ambiguous whether the person is viewing a suggestion, a draft, or a committed record.

## 5. Primary actions

Each page or panel may have one visually dominant action. The primary action must:

- use an explicit verb;
- describe the immediate result;
- remain disabled only when the reason is understandable;
- not conceal additional consent or permissions;
- provide immediate acknowledgement after activation;
- protect against accidental duplicate submission.

Avoid generic labels such as `Submit`, `Continue`, or `Yes` when a clearer action is available. Prefer labels such as `Complete Quest`, `Save Reflection`, `Approve Draft`, or `Install World`.

## 6. Confirmation proportional to consequence

Confirmation should match the action's consequence.

### No separate confirmation

Use immediate action with optional undo for low-risk, reversible changes such as:

- acknowledging a notification;
- changing a display preference;
- reordering a personal layout;
- marking a non-sensitive item complete when undo is available.

### Inline confirmation

Use a short inline explanation for meaningful but familiar changes such as:

- completing a Quest that may publish an approved platform event;
- changing a recurring schedule;
- sharing an item with an existing Household or Organization.

### Explicit confirmation dialog

Use a focused dialog for consequential or difficult-to-reverse actions such as:

- deleting durable records;
- revoking consent;
- removing another person's access;
- restarting a World;
- publishing public content;
- approving Companion execution with external effects.

Confirmation dialogs must name the consequence, not merely ask whether the person is sure.

## 7. Forms

Forms should feel predictable across Koravik.

Requirements:

- Labels remain visible and are programmatically associated with controls.
- Required fields are identified in text, not color alone.
- Help text explains why information is needed when the purpose is not obvious.
- Validation occurs at a helpful time without interrupting normal typing.
- Entered information is preserved after recoverable errors.
- Sensitive fields state their visibility and use.
- Dates and times display the relevant timezone.
- Autosave is used only when its behavior is visible and trustworthy.
- Draft and committed states are distinct.
- Canceling a form with unsaved changes prompts only when something would actually be lost.

## 8. Lists, filters, and search

Lists should help people find and act, not merely display database rows.

- Default ordering should reflect likely intent.
- Active filters must remain visible and removable.
- Search results should identify the owning District or World.
- Sensitive result snippets must respect authorization and consent.
- Pagination or incremental loading must preserve keyboard access and history.
- Empty filtered results should offer a clear way to broaden or reset the view.
- Bulk actions require explicit scope and consequence review.

## 9. Notifications

Notifications exist to support timely action, not to manufacture urgency.

Each notification should communicate:

- what changed;
- why the person is receiving it;
- which District, group, or World originated it;
- whether action is required;
- how to adjust similar notifications.

Koravik must not use artificial scarcity, guilt, streak loss, or escalating red badges to compel return.

## 10. Companion interactions

Companion may suggest, explain, summarize, and draft. It may not blur the line between advice and action.

Every Companion proposal must show:

- that it is a proposal;
- the information used to create it;
- the owning District for any resulting record;
- the consequence of approval;
- editable content when appropriate;
- a clear dismiss option;
- whether the proposal will be remembered.

Consequential execution requires explicit approval at the moment of action. Prior general consent does not replace consequence-specific confirmation.

## 11. World interactions

Worlds interpret approved facts into fictional state. The interface must preserve a clear boundary between reality and fiction.

Required behavior:

- World reactions identify the originating fact in understandable terms.
- Sensitive source details are not displayed when a minimized explanation is sufficient.
- Narrative choices clearly indicate when they affect only World State.
- Restart, suspend, resume, and uninstall behaviors explain what happens to saved progress.
- Switching Worlds does not imply deletion of another World's state.
- World fiction must not masquerade as real-life instruction or system authority.

## 12. Absence and resumption

Koravik must support irregular use without punishment.

After an absence, the interface should:

- summarize only meaningful changes;
- avoid guilt-based language;
- identify outdated intentions without treating them as failures;
- permit dismissal, rescheduling, or continuation;
- preserve narrative and practical context;
- suggest one manageable re-entry action.

No essential feature may depend on maintaining a streak.

## 13. Undo, correction, and history

Where practical, reversible actions should provide undo. Durable changes should retain sufficient history for correction and audit.

The person must be able to distinguish:

- undoing a recent interface action;
- editing the current canonical record;
- adding a correction to historical information;
- reversing a permission or consent decision;
- changing World State through supported narrative behavior.

Audit history must not be presented as editable source truth.

## 14. Accessibility behavior

All interaction patterns must support:

- full keyboard operation;
- visible focus;
- logical focus order;
- screen-reader names, roles, states, and announcements;
- reduced motion preferences;
- adequate target size;
- zoom and text resizing;
- high contrast and non-color indicators;
- error identification and recovery;
- no time-limited interaction unless essential and adjustable.

Motion may reinforce spatial relationships but must not be required to understand state.

## 15. Responsive behavior

Responsive design changes arrangement, not capability.

- Core actions remain available at all supported sizes.
- Mobile surfaces favor one primary task at a time.
- Navigation may collapse but must remain predictable.
- Tables transform into accessible summaries or cards only when information relationships remain clear.
- Modals should become full-screen sheets on narrow displays when that improves usability.
- Companion and World side panels must not obscure essential content or trap navigation.

## 16. Content and tone

Koravik uses calm, direct, respectful language.

The interface should:

- prefer plain verbs and concrete outcomes;
- avoid moral judgment;
- avoid pretending certainty when uncertainty exists;
- distinguish recommendations from requirements;
- explain technical or privacy concepts in human terms;
- avoid childish gamification language in real-life Districts;
- allow Worlds to have distinctive narrative voices without changing platform truth.

## 17. Acceptance criteria

An interaction is ready for implementation only when:

1. Its primary question is clear.
2. Every relevant state is specified.
3. Ownership and consequence are visible.
4. Keyboard and screen-reader behavior are defined.
5. Responsive behavior is defined.
6. Failure and interruption recovery are defined.
7. Companion or World involvement is explainable.
8. Privacy and consent implications are stated.
9. The person can leave the flow without accidental loss.
10. The interaction does not rely on addictive or punitive mechanics.
