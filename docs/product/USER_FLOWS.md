# Koravik User Flows

**Status:** Draft for Blueprint v1.0 review  
**Version:** 1.0  
**Owner:** Product Architecture  
**Last reviewed:** 2026-07-28

## Purpose

This document defines the complete user journeys that the first implementation must support. These flows are product contracts: implementation may refine presentation, but it must preserve intent, ownership, permissions, interruption recovery, and explainability.

## Flow principles

Every flow must:

- begin from a clear user intention;
- identify the owning District or Platform service;
- expose consequential effects before they occur;
- preserve context after interruption;
- remain usable without a Household, Organization, Companion, or installed World unless the flow explicitly requires one;
- provide accessible keyboard, screen-reader, touch, and reduced-motion behavior;
- avoid punishment, shame, streak loss, or artificial urgency.

## UF-001: First sign-in and orientation

### Goal

Allow a person to enter Koravik securely and understand what the platform is for without completing a lengthy setup ritual.

### Primary path

1. The person opens the sign-in surface.
2. They authenticate through the supported credential method.
3. Koravik creates or restores the authenticated session.
4. The shared application shell loads.
5. Hearth presents a calm orientation state.
6. The person may create a first Quest, review an existing item, open an installed World, or leave.

### Required behavior

- The first session must not require Household, Organization, social, Companion, or World configuration.
- Optional setup is progressive and may be skipped.
- Privacy and consent choices must use plain language.
- Authentication errors must not reveal whether an account exists.
- Returning users resume meaningful context rather than seeing onboarding again.

### Recovery

Expired sessions return the person to sign-in while preserving the intended destination when safe.

## UF-002: Create a personal Quest

### Goal

Record one meaningful real-life action with minimal friction.

### Owner

Quests.

### Primary path

1. The person chooses **New Quest** from Hearth or Quests.
2. The create surface requests the minimum required information: title.
3. Optional details may include notes, due guidance, recurrence, project, and association.
4. The person saves.
5. Quests validates and persists the record.
6. The person sees the saved Quest and a clear next action.

### Required behavior

- Advanced options remain collapsed until requested.
- The user can save a simple Quest without selecting a World, Household, Organization, category, or reward.
- Validation errors remain attached to the relevant fields.
- Cancel returns to the prior context without creating a record.

### Recovery

Unsaved text should survive accidental navigation when technically feasible. If the session expires, Koravik should preserve a safe draft or clearly explain that it could not.

## UF-003: Complete a Quest and publish a fact

### Goal

Complete a real-life action and make its downstream effects understandable.

### Owner

Quests owns completion. The Platform Event system owns durable publication. Worlds may interpret only approved facts.

### Primary path

1. The person opens a Quest.
2. They choose **Complete**.
3. Quests confirms immediately for ordinary low-consequence completion.
4. The completion is committed in the same transaction as the outbox record.
5. The interface shows the Quest as completed.
6. If an active World is authorized to receive the minimized fact, the interface indicates that a World reaction may follow.
7. The person may return to Hearth immediately.

### Required behavior

- The real-life completion must succeed independently of World processing.
- Event publication must be idempotent and explainable.
- Sensitive Quest details must not be included when a derived fact is sufficient.
- Undo is available for a bounded period when the business rules permit it.

### Recovery

If World processing fails, the Quest remains completed. The person sees no false failure and may later receive the World reaction when processing succeeds.

## UF-004: View an Epic Ordinary reaction

### Goal

Understand how a real-life action influenced the active World.

### Owner

Epic Ordinary owns its World State and presentation. It does not own the originating Quest.

### Primary path

1. Epic Ordinary receives an authorized, versioned platform fact.
2. The World runtime evaluates applicable reaction rules.
3. A single idempotent state change is committed.
4. The person sees a concise World continuation card on Hearth or in the World.
5. They open the reaction.
6. The interface shows what changed and why.
7. The person continues the story or returns to Hearth.

### Explainability contract

Every visible reaction must be able to answer:

- What changed?
- Which approved real-life fact caused it?
- When did the interpretation occur?
- Which World rule or narrative condition applied?

The explanation should be human-readable and must not expose internal implementation details.

## UF-005: Leave and safely resume

### Goal

Allow the person to stop using Koravik without penalty and return without losing orientation.

### Primary path

1. The person leaves at any point after committed work.
2. Koravik safely ends or expires the session.
3. On return, the person signs in.
4. Hearth summarizes only meaningful changes since the last visit.
5. Stale intentions may be dismissed, rescheduled, archived, or resumed.
6. The active World offers continuation from durable state.

### Required behavior

- No guilt language, broken streaks, or punishment for absence.
- The product must distinguish committed state from unfinished drafts.
- Time-sensitive items should be described neutrally.
- The next step should be manageable, not an accumulated wall of obligations.

## UF-006: Companion proposes an action

### Goal

Receive useful assistance without surrendering agency.

### Owner

Companion owns the proposal. The relevant District owns execution.

### Primary path

1. The person asks Companion for help or Companion surfaces a bounded suggestion.
2. Companion presents its reasoning and source context.
3. The proposal identifies the action, owning District, affected records, and consequence.
4. The person edits, approves, dismisses, or asks for clarification.
5. On approval, the owning District revalidates authorization and executes.
6. An audit record captures the approved action.

### Required behavior

- Companion never silently creates, edits, sends, publishes, or deletes consequential records.
- Suggestions and saved records must look different.
- Approval must be specific to the proposed action.
- A rejected proposal creates no negative product consequence.

## UF-007: Install and activate a World

### Goal

Install optional narrative content with informed consent.

### Owner

World Installation and World Runtime Platform modules.

### Primary path

1. The person opens the World catalog.
2. They review the World description, content notices, compatibility, accessibility metadata, and requested fact subscriptions.
3. They choose **Install**.
4. Koravik validates the structured package and compatibility.
5. The person reviews permissions and confirms.
6. Installation creates isolated World State.
7. The person may activate the World as their primary World.

### Required behavior

- A World must work from structured, non-executable package content.
- Requested permissions must be specific and revocable.
- Activation must not erase another World’s state.
- Restart, suspend, resume, uninstall, and data-retention consequences must be distinct actions.

## UF-008: Revoke a World permission

### Goal

Stop future access to a category of real-life facts without corrupting existing District records.

### Primary path

1. The person opens World permissions.
2. They select an active subscription.
3. Koravik explains what future reactions may stop.
4. The person confirms revocation.
5. The Platform stops future delivery of that fact category.
6. Existing World State remains unless the person separately resets or removes it.

### Required behavior

- Revocation takes effect promptly.
- The World must not infer continued access from historical state.
- District records remain untouched.
- Audit and consent history remain available to the person.

## UF-009: Create a Chronicle reflection from a proposal

### Goal

Preserve meaning intentionally while keeping Chronicle ownership and approval boundaries intact.

### Owner

Chronicle owns the saved reflection. Companion or a World may own the draft proposal.

### Primary path

1. Companion or a World offers a reflection draft.
2. The person opens the proposal.
3. Source context and privacy destination are shown.
4. The person edits the text.
5. They explicitly choose **Save to Chronicle**.
6. Chronicle validates and persists the entry.
7. The proposal is marked resolved.

### Required behavior

- No draft is saved to Chronicle without approval.
- Fictional and real-life voices remain distinguishable.
- The person controls visibility and deletion.

## UF-010: Correct or undo a recent action

### Goal

Recover gracefully from ordinary mistakes.

### Primary path

1. The person sees a recent action confirmation.
2. They choose **Undo** or **Correct** within the allowed boundary.
3. The owning module validates whether reversal is safe.
4. The record is reverted or a compensating action is created.
5. Downstream consumers receive the appropriate correction fact when needed.
6. The interface explains the final state.

### Required behavior

- Irreversible actions require stronger confirmation before execution.
- Corrections must not rewrite audit history.
- World reactions must use compensating or revised state logic rather than editing District truth.

## UF-011: Return after an extended absence

### Goal

Restore orientation without presenting backlog as failure.

### Primary path

1. Hearth detects a meaningful period of absence.
2. It presents a concise welcome-back summary.
3. Items are grouped as still relevant, changed, completed elsewhere, or potentially stale.
4. The person may resume one item, review changes, or clear outdated intentions.
5. Epic Ordinary offers a narrative return that acknowledges time without punishing the player.

### Content requirements

Preferred language:

- “Welcome back.”
- “A few things changed while you were away.”
- “Does this still matter?”

Prohibited language:

- “You failed.”
- “You lost your streak.”
- “You are behind.”

## First vertical-slice acceptance flow

Build 001 must prove the uninterrupted and interrupted versions of this chain:

```text
Sign in
→ Hearth
→ Open Quest
→ Complete Quest
→ Commit District state and outbox fact
→ Epic Ordinary interprets fact
→ Explainable World reaction
→ Return to Hearth
→ Sign out or leave
→ Sign in later
→ Resume from durable state
```

## Flow validation checklist

A flow is ready for implementation only when:

- ownership is explicit;
- permission and consent points are explicit;
- success, empty, loading, validation, authorization, offline, and failure states are defined;
- keyboard and screen-reader paths are possible;
- interruption and retry behavior are known;
- consequential downstream behavior is explainable;
- the flow helps the person act or understand rather than merely remain engaged.
