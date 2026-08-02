# Moment Engine Foundation

**Status:** Implemented in current working tree  
**Date:** 2026-08-01

## Executive summary

Moment Engine Foundation gives Koravik-Final a durable, source-aware way to surface meaningful change as ambience, one-at-a-time arrival scenes, remembered moments, or Chronicle preservation review.

This is not a notification center. It is the first runtime layer for the Epic Ordinary principle: nothing important happens off-screen.

## Major improvements

- Adds `platform_moments` as the Moment Engine candidate/read-state table.
- Adds `MomentService` for source submission, source seeding, next arrival selection, remembered moments, archive/dismiss state, and Chronicle proposal handoff.
- Adds `MomentController` with:
  - `/moments`
  - `/moments/next`
  - `/moments/remembered`
  - `/moments/{id}`
- Seeds initial Moment candidates from:
  - Epic Ordinary World reactions;
  - Healing Home visible changes;
  - reclaimed Healing Home ambience.
- Keeps arrival scenes one at a time.
- Adds replay-safe remembered moments.
- Adds explicit Chronicle preservation review through `chronicle_reflection_reviews`.
- Updates Healing Home’s `/home/moments` route to point into the real Moment Engine.

## Ownership boundaries

- Source modules own the originating change.
- Moment Engine owns only presentation state: queued, presented, archived, dismissed.
- Chronicle owns saved prose only after explicit review/save.
- Worlds do not receive Chronicle prose through this feature.
- Companion memory, Health records, room notes, Quest notes, account secrets, and unrelated records stay excluded.

## Demo routes

- `/moments`
- `/moments/next`
- `/moments/remembered`
- `/home/moments`
- `/chronicle/proposals`

## Migrations

- `database/migrations/104_moment_engine_foundation.sql`

## Tests

Release coverage includes `Moment Engine Foundation queues arrival scenes and Chronicle review safely`.

## Known limitations

- Source submission is service-level; not every District emits Moment candidates yet.
- Arrival presentation is server-rendered and minimal.
- Queue throttling currently enforces one selected arrival at read time; richer fatigue windows are a follow-up.

## Next sprint

Wire more source modules into Moment submission and add richer arrival scene templates for Caretaker, room, companion, memory, and silent scenes.

## Scene Templates and Source Expansion Pass

The combined broad-stroke pass adds additive scene-template fields and source expansion:

- `scene_template`: Caretaker, room, silent, memory, or companion.
- `speaker_label`: optional speaker for Caretaker-style scenes.
- `primary_object`: object that visually carries the scene.
- `ambient_detail`: the room/object detail noticed before explanation.
- `recommended_action_label`: gentler per-scene continue copy.

Additional source seeds:

- Caretaker conversations become Caretaker scenes.
- Displayed Healing Home keepsakes become memory/object scenes.
- Garden tending and room changes become room or silent scenes.
- Remembered Moments group by scene type.

## Artifact and Room Interaction Pass

Displayed Healing Home keepsakes can now be explicitly prepared as memory-object Moments from their keepsake detail page. This creates Moment Engine presentation state only:

- no Quest is created;
- no Chronicle entry is saved automatically;
- no Companion memory is written;
- Chronicle preservation still requires explicit review.

## Chronicle Preservation Polish

Moment-to-Chronicle proposals now include review context in the proposed body: scene template, room, source module/source type, provenance, and deliberately excluded data. This makes Chronicle review clearer without saving automatically.

## Living Moment Presentation Polish

Moments now load `moments.css` and render as fuller scene presentations with:

- scene-specific stage treatments;
- object/ambient lead-in blocks;
- quieter provenance panels;
- reduced-motion and forced-colors safeguards;
- remembered-library scene anchors.

## Remembered Moment Actions and Companion-ready Trace Expansion

Remembered Moment cards now include direct “Prepare Chronicle review” actions. Displayed keepsakes that look like visitor traces, such as robin feathers, become companion-ready scene templates instead of generic memory objects.

## Moment Expansion Loop — next 10 milestones

The Moment Engine now loops through the next broad milestones as one coherent expansion pass:

1. Additional District Moment Submissions: Quests, Gather, Health, Source Review, Chronicle, Companion, and World progress can submit minimized candidates.
2. Authored Scene Copy Packs: caretaker, room, silent, memory, and companion templates use warmer copy helpers instead of one generic sentence.
3. Moment Source Review Console: the library shows which source modules are contributing without exposing private payloads.
4. Moment Inbox / Tuning Controls: the UI names the quiet default posture, source grouping, and one-arrival rule.
5. Healing Home Living Rooms Pass: source candidates map to room keys and visible objects so the Home can react as ambience.
6. Quest-to-Moment Loop: completed occurrences and resolved commitments become review-safe memory/silent scenes.
7. Gather-to-Moment Loop: closeouts and outcome proposals become social Moments without exposing guests or communications.
8. World Chapter Moment Layer: minimized narrative progress can appear as room-state ambience.
9. Companion Presence Moment Layer: proposal state appears as companion-ready traces without exposing Companion memory.
10. Moment Library Polish: scene grouping, source counts, direct Chronicle review, and empty-state copy are now part of the library contract.

The expansion preserves the original rule: source modules own truth; Moment Engine owns presentation/read state; Chronicle saves prose only after explicit review.
