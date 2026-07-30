# Healing Home Visual Foundation

**Status:** Implemented
**Version:** 1.0
**Baseline date:** July 30, 2026

## Player outcome

`GET /home` and `GET /healing-home` now present Healing Home as the forward-facing heart of Koravik: a warm, illustrated room that composes the person's current Quest, World changes, Chronicle presence, keepsakes, Caretaker relationship continuity, Companion entry point, and unopened rooms.

The experience answers one question: what is alive in my home right now?

## Ownership and privacy

- Healing Home composes source-owned records but does not own or mutate Quest, Chronicle, Companion, Gather, Health, Beacon, or World truth.
- Quest Board links to the Quests-owned record.
- Journal Table links to Chronicle.
- Fireplace changes are materialized from owned, explainable World reactions.
- Relationship memory remains account-scoped and fictional where sourced from Epic Ordinary.
- Companion Chair explains assistance without implying automatic consequential action.

## Persistence

No new schema was required. The slice uses the existing `healing_home_state`, `healing_home_rooms`, `healing_home_changes`, `healing_home_keepsakes`, `journey_relationships`, and `journey_relationship_memories` tables introduced by the Healing Home foundation.

The page updates `last_returned_at` through `JourneyService::homeForAccount()` and renders return continuity without guilt language.

## Routes

- `GET /home`
- `GET /healing-home`
- `GET /home/relationships/{characterKey}`

## Accessibility and responsive behavior

- one main heading and labeled room regions;
- meaningful alternative label for the room illustration;
- keyboard-visible source links and native controls;
- text status for open and locked rooms without color-only meaning;
- reduced-motion-compatible decorative atmosphere;
- single-column reflow below tablet width with no feature hidden behind hover.

## Verification

Automated coverage proves account-scoped composition, owned Quest surfacing, owned World change surfacing, Caretaker materialization, durable return state, and required UI vocabulary.

## Known limitations

- The room illustration is CSS-rendered placeholder production art, not a packaged bitmap asset with full art provenance.
- Room-specific interactions remain links to owning surfaces rather than in-room detail views.
- Health and Gather are intentionally not shown until their source-owned visible workflows are ready for this home.
