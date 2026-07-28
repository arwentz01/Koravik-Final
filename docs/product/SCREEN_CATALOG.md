# Koravik Screen Catalog

**Status:** Draft for Blueprint v1.0 review  
**Version:** 1.0  
**Owner:** Product Architecture  
**Last reviewed:** 2026-07-28

## Purpose

This catalog defines the required product surfaces, their purpose, ownership, primary question, responsive behavior, and minimum states. It is not a route list. Routes may change; these experience contracts may not change without product review.

## Catalog rules

Every screen must:

- answer one primary question;
- identify the owning District or Platform module;
- provide a clear primary action or a deliberate read-only state;
- support loading, empty, success, validation, authorization, and failure behavior where applicable;
- preserve context when opened from Hearth, search, notifications, Companion, or a World;
- remain accessible at 200% zoom and across keyboard, touch, and assistive technology;
- avoid unnecessary dashboards, dense control walls, or hidden consequential behavior.

## Shared Platform surfaces

### SC-001: Sign in

**Owner:** Authentication  
**Primary question:** How do I securely enter Koravik?  
**Primary action:** Sign in  
**Secondary actions:** Recover access; review privacy information  
**Required states:** idle, submitting, invalid credentials, locked/rate-limited, expired link, service unavailable  
**Responsive notes:** single focused column; no decorative content that competes with authentication.

### SC-002: Session recovery

**Owner:** Authentication  
**Primary question:** How do I safely continue after my session ended?  
**Primary action:** Sign in again  
**Required behavior:** preserve intended destination when safe; never preserve sensitive unsaved values in an insecure location.

### SC-003: Global search

**Owner:** Search  
**Primary question:** Where is the thing I am looking for?  
**Primary action:** Open a result  
**Required states:** initial guidance, searching, grouped results, no results, unavailable index  
**Rules:** results grouped by owning module; privacy and authorization applied before display; search must not become a duplicate data owner.

### SC-004: Notifications center

**Owner:** Notifications  
**Primary question:** What changed that deserves my attention?  
**Primary action:** Open the relevant source  
**Required behavior:** bounded grouping, source attribution, mark read/unread, dismiss where appropriate, no engagement bait.

### SC-005: Account settings

**Owner:** Settings  
**Primary question:** How should Koravik work for me?  
**Sections:** account, appearance, accessibility, privacy, notification preferences, data controls  
**Required behavior:** settings grouped by consequence; autosave only for low-risk reversible preferences.

### SC-006: Privacy and consent center

**Owner:** Consent  
**Primary question:** What information may each capability use?  
**Primary action:** Review or revoke permission  
**Required behavior:** current grants, purpose, source, recipient, last use, revocation effect, audit history.

### SC-007: Audit activity

**Owner:** Audit  
**Primary question:** What consequential actions occurred and why?  
**Primary action:** Inspect an action  
**Required behavior:** human-readable summaries, actor, source, timestamp, affected module, approval context; no editing of historical records.

## Shared application shell

### SC-010: Desktop shell

**Owner:** Platform UI  
**Purpose:** Persistent orientation and navigation  
**Required regions:** skip link, primary navigation, page header, content area, utilities, optional contextual panel  
**Behavior:** navigation remains stable; active location is visually and semantically clear; content width is bounded.

### SC-011: Mobile shell

**Owner:** Platform UI  
**Purpose:** Preserve the same hierarchy in constrained space  
**Required behavior:** compact header, direct access to Hearth and primary actions, drawer or bottom navigation where appropriate, no horizontal overflow.

## Hearth surfaces

### SC-100: Hearth home

**Owner:** Hearth  
**Primary question:** What matters now?  
**Primary action:** Open the most relevant next item  
**Required regions:** orientation, one primary next step, active World continuation when installed, bounded supporting items, Companion proposal when appropriate  
**Required states:** useful first-use empty state, ordinary day, overdue/stale items, World unavailable, partial service failure, return after absence  
**Rules:** Hearth composes but does not own source records; no infinite widget wall.

### SC-101: Welcome-back summary

**Owner:** Hearth  
**Primary question:** What meaningfully changed while I was away?  
**Primary action:** Resume one relevant item  
**Required behavior:** neutral language, stale-item review, dismiss/reschedule/archive, World continuation without punishment.

### SC-102: Hearth customization

**Owner:** Hearth for layout preferences; source modules for widget contracts  
**Primary question:** What should appear on my Hearth?  
**Primary action:** Save layout  
**Required behavior:** bounded placements, keyboard-safe reordering, restore defaults, previews, no ability to change source truth.

## Quests surfaces

### SC-200: Quest list

**Owner:** Quests  
**Primary question:** What actions have I chosen to manage?  
**Primary action:** Open or create a Quest  
**Required views:** relevant now, upcoming, completed, archived; filters must not replace understandable defaults.

### SC-201: Create Quest

**Owner:** Quests  
**Primary question:** What do I intend to do?  
**Primary action:** Save Quest  
**Minimum field:** title  
**Progressive fields:** notes, due guidance, recurrence, project, association, privacy  
**Required states:** clean form, validation, saving, saved, recoverable failure, discarded draft.

### SC-202: Quest detail

**Owner:** Quests  
**Primary question:** What is this action and what can I do next?  
**Primary action:** Complete, begin, or resume according to state  
**Secondary actions:** edit, reschedule, archive, delete where permitted  
**Required behavior:** show ownership, status, context, history, and downstream World eligibility without implying the World owns the Quest.

### SC-203: Quest completion confirmation

**Owner:** Quests  
**Primary question:** Did the completion succeed?  
**Primary action:** Return or continue  
**Required behavior:** immediate ordinary confirmation, bounded undo when allowed, optional notice that an approved World reaction may follow.

### SC-204: Recurrence editor

**Owner:** Quests  
**Primary question:** When should this intention recur?  
**Primary action:** Apply recurrence  
**Required behavior:** plain-language preview; timezone-aware; clearly distinguish completion from creation of the next occurrence.

## Worlds surfaces

### SC-300: World catalog

**Owner:** World Catalog  
**Primary question:** Which optional World might I want to experience?  
**Primary action:** Review a World  
**Required metadata:** title, premise, creator, version, accessibility, content notices, compatibility, trust/review status.

### SC-301: World detail and permission preview

**Owner:** World Catalog / Installation  
**Primary question:** What is this World and what will it be allowed to interpret?  
**Primary action:** Install  
**Required behavior:** specific event subscriptions, purposes, data minimization, content notices, storage consequences, update expectations.

### SC-302: Install World

**Owner:** World Installation  
**Primary question:** Am I ready to install this package with these permissions?  
**Primary action:** Confirm installation  
**Required states:** validating, incompatible, permission review, installing, success, safe failure.

### SC-303: Installed Worlds

**Owner:** World Installation  
**Primary question:** Which Worlds are installed and what is their status?  
**Primary actions:** activate, resume, suspend  
**Secondary actions:** restart, update, uninstall  
**Rules:** restart and uninstall require clear consequence summaries; one primary active World initially.

### SC-304: World home

**Owner:** Active World  
**Primary question:** Where am I in this story?  
**Primary action:** Continue  
**Required regions:** current scene, World Quest or narrative objective, NPC/context cues, recent explainable change, leave-to-Hearth action.

### SC-305: World reaction detail

**Owner:** Active World  
**Primary question:** What changed in this World and why?  
**Primary action:** Continue story or return  
**Required behavior:** narrative presentation plus accessible explanation of source fact, timing, and applied World rule.

### SC-306: World permissions

**Owner:** Consent / World Installation  
**Primary question:** What may this World receive in the future?  
**Primary action:** Revoke or review permission  
**Required behavior:** event-category granularity, purpose, last delivery, effect of revocation, no access to District details beyond the approved contract.

### SC-307: World state and progress

**Owner:** World Runtime  
**Primary question:** What durable progress exists in this World?  
**Required sections:** story position, World Quests, relationships, achievements, inventory if supported, recent state reasons  
**Rules:** clearly fictional; never mixed with real-life records.

## Companion surfaces

### SC-400: Companion panel

**Owner:** Companion  
**Primary question:** What help do I need right now?  
**Primary action:** Ask or review a proposal  
**Required behavior:** contextual but dismissible; never blocks source workflows; identifies source context and uncertainty.

### SC-401: Companion proposal review

**Owner:** Companion for proposal; destination District for execution  
**Primary question:** Should this specific action happen?  
**Primary actions:** approve, edit, dismiss  
**Required content:** proposed action, reason, source, destination owner, affected records, consequence, privacy scope.

### SC-402: Companion memory controls

**Owner:** Companion Memory / Consent  
**Primary question:** What does Companion remember and why?  
**Primary actions:** correct, remove, revoke future use  
**Required behavior:** sourced memory, real-life/fictional separation, timestamps, visible correction history.

## Chronicle surfaces

### SC-500: Chronicle timeline

**Owner:** Chronicle  
**Primary question:** What have I intentionally preserved?  
**Primary action:** Open or create an entry  
**Rules:** chronological clarity; privacy visible; no automatic ingestion of raw District history.

### SC-501: Chronicle entry editor

**Owner:** Chronicle  
**Primary question:** What do I want to preserve?  
**Primary action:** Save entry  
**Required behavior:** manual entry and approved draft modes; autosave may preserve a draft but must not publish or change visibility without approval.

### SC-502: Proposed reflection

**Owner:** Proposal source; Chronicle owns final save  
**Primary question:** Should this reflection become part of Chronicle?  
**Primary actions:** edit and save, dismiss  
**Required behavior:** source attribution, privacy selection, distinction between fictional narration and personal reflection.

## Health surfaces

### SC-600: Health overview

**Owner:** Health  
**Primary question:** What health information or action matters now?  
**Required behavior:** privacy-forward summaries; sensitive detail hidden from unrelated shell surfaces; no World access unless a minimized approved fact contract exists.

### SC-601: Health record/detail

**Owner:** Health  
**Primary question:** What does this record mean and what may I do with it?  
**Required behavior:** careful terminology, provenance, correction path, consent controls, no diagnostic claims from Companion or Worlds.

## Gather surfaces

### SC-700: Gather events

**Owner:** Gather  
**Primary question:** What shared experiences are relevant?  
**Primary action:** Open or create an event according to authorization  
**Required behavior:** event status, time, location/privacy, invitation state, accessible calendar/list alternatives.

### SC-701: Event detail

**Owner:** Gather  
**Primary question:** What is happening and how am I participating?  
**Primary action:** RSVP or manage event  
**Rules:** arrival-related fields are host-configurable and off by default unless product requirements explicitly change.

## Beacon surfaces

### SC-800: Beacon public page editor

**Owner:** Beacon  
**Primary question:** What should this organization or campaign communicate publicly?  
**Primary action:** Preview or publish according to authorization  
**Required behavior:** draft/published distinction, permission checks, accessible content guidance, public/private boundary.

## Administration surfaces

### SC-900: User administration

**Owner:** System Administration / Identity  
**Primary question:** What account administration is authorized here?  
**Required behavior:** capability-based actions, audit trail, safe password-reset rules, no role inflation through UI assumptions.

### SC-901: System health

**Owner:** System Administration  
**Primary question:** Is Koravik operating safely?  
**Required sections:** migrations, worker/outbox health, failed jobs, storage, version, deployment status  
**Rules:** operational data only; no secret disclosure.

## Error and exceptional surfaces

### SC-950: Not authorized

Explain the boundary without exposing sensitive resource existence. Provide a safe return path.

### SC-951: Not found

Use plain language, preserve shell orientation, offer search or Hearth without blaming the person.

### SC-952: Temporary failure

State what did and did not save, provide retry only when safe, and avoid duplicate consequential actions.

### SC-953: Offline or interrupted

Identify unsent or unsaved work, preserve drafts where feasible, and distinguish local draft state from committed server state.

### SC-954: Maintenance

Provide an honest status, protect data, and avoid fabricated restoration times.

## Responsive expectations

### Wide screens

- Persistent primary navigation is permitted.
- Contextual panels may sit beside content when they do not compress the primary task.
- Long-form narrative uses bounded readable line length.

### Medium screens

- Navigation may collapse.
- Secondary panels become drawers or inline sections.
- Tables must transform or scroll with clear headers; critical actions cannot disappear.

### Small screens

- One primary column.
- Primary actions remain reachable without covering content.
- Dialogs should become full-screen sheets when needed.
- World narrative and practical District interfaces retain separate visual identities without changing controls unpredictably.

## Screen readiness checklist

A screen may enter implementation only when it has:

- an owner;
- a primary question;
- a primary action or intentional read-only purpose;
- content hierarchy;
- responsive behavior;
- required states;
- authorization and privacy expectations;
- accessibility acceptance criteria;
- links to relevant user flows and component contracts.
