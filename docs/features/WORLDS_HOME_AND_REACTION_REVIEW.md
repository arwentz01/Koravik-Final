# Worlds Home and Reaction Review

**Status:** Implemented
**Version:** 1.0
**Baseline date:** July 30, 2026

## Player outcome

`GET /worlds` is now a story-first home rather than only a package catalog. A
person can see the active World and current scene, continue the story, review
interpreted changes, explicitly mark a reaction reviewed, and reach World
permissions, progress, catalog details, and lifecycle controls.

## Ownership and privacy

- Worlds owns reaction review state.
- A review is allowed only when the reaction belongs to a World installation
  owned by the current Account.
- Review state does not alter a source District fact, World reaction,
  relationship state, or narrative progress.
- Review actions append minimized audit evidence without copying private source
  content.

## Persistence

Migration `095_world_reaction_reviews.sql` adds one durable review record per
World reaction, the reviewing Account, and its UTC review timestamp. Review
records are included in Account export and removed through existing Account and
World installation cascade behavior.

## First-install repair

The browser journey found that catalog installation could activate Epic
Ordinary without creating its initial narrative progress and Caretaker
relationship. `WorldService::install()` now initializes those records
idempotently so a newly installed World always has a playable first scene.

## Routes

- `GET /worlds`
- `POST /worlds/reactions/{reactionId}/review`

Existing World detail, play, progress, permission, and lifecycle routes remain
the destinations for their full behavior.

## Accessibility and responsive behavior

- labeled regions for the active World, reactions, and catalog;
- text labels for new and reviewed state without color-only meaning;
- CSRF-protected native form submission;
- server-rendered empty, success, unavailable, active, and reviewed states;
- single-column reflow below 760px with no horizontal overflow at 390px.

## Verification

Automated coverage proves active World composition, ownership isolation,
durable idempotent review state, rendering contracts, first-install narrative
initialization, migration inventory, and schema presence.

The local XAMPP browser journey verified account creation, World installation,
active-story rendering, reaction review, success feedback, reviewed state, and
mobile reflow. Synthetic verification data was removed afterward.

## Known limitations

- The catalog currently contains only Epic Ordinary.
- Opening an explanation does not silently mark it reviewed.
- This slice does not add another story chapter or new reaction rules.
