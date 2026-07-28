# Epic Ordinary Art and Narrative Bible

**Status:** Draft for Blueprint v1.0 review  
**Version:** 1.0  
**Owner:** World and Narrative Design  
**Last reviewed:** 2026-07-28

## Purpose

Epic Ordinary is the reference implementation of the Koravik World system. It must prove that ordinary real-life actions can create meaningful fictional consequences without turning life into a points game or allowing fiction to replace reality.

This document governs Epic Ordinary’s tone, visual language, narrative structure, character behavior, accessibility, and relationship to platform facts.

## Core premise

A quiet world is being restored through small acts of steadiness, care, courage, and attention.

The player is not a chosen hero because an interface declared them exceptional. The world gradually recognizes them because of what they repeatedly choose to do.

Epic Ordinary should make a completed real-life action feel witnessed, not scored.

## Experience promise

Epic Ordinary should feel:

- intimate rather than grandiose;
- hopeful without becoming sentimental;
- mysterious without becoming confusing;
- consequential without becoming punishing;
- warm without becoming childish;
- fantastical while remaining emotionally grounded.

It should never feel like:

- a streak tracker wearing fantasy clothes;
- a loot treadmill;
- a morality score;
- a guilt engine;
- a productivity leaderboard;
- a replacement for professional health, financial, legal, or relationship guidance.

## Narrative thesis

Ordinary life contains epic weight when attention, responsibility, and care are sustained over time.

The World interprets approved facts through metaphor. A completed Quest may mend a lantern path, restore a workshop, earn quiet trust, or move a community project forward. It must not claim that a fictional outcome is the literal meaning of the person’s life.

## Setting

Epic Ordinary takes place in a recovering region known provisionally as **The Near Country**.

The Near Country is made of familiar but subtly enchanted places:

- a village whose lamps remember who tends them;
- a riverside road that clears when promises are kept;
- a common house rebuilt through many small contributions;
- a neglected garden where names return to forgotten plants;
- a workshop where repaired objects reveal fragments of local history;
- a distant ridge that becomes visible as the community regains direction.

The setting should feel lived-in. Buildings show repair. Tools have histories. Weather changes moods but does not punish the player. Magic is quiet, relational, and often expressed through light, sound, memory, growth, and restoration.

## The player role

The player enters as a **Waykeeper**: a person able to notice weak connections between actions, places, and people.

The title is not a rank. It carries no inherent superiority. Waykeeping means:

- noticing what needs care;
- following through;
- helping others act;
- restoring paths;
- remembering what matters;
- accepting that not everything can be repaired immediately.

The player character should remain lightly defined so the person can identify with them without being forced into a fixed appearance, gender, age, body type, or cultural background.

## Narrative structure

### Seasons

Epic Ordinary is organized into narrative Seasons. A Season is a bounded arc with:

- one central community tension;
- several locations;
- a small cast of recurring characters;
- World Quests;
- optional side stories;
- delayed consequences;
- a clear but non-final resolution.

A Season should be completable without perfect behavior or daily attendance.

### Chapters

Chapters group related scenes and state changes. They open through a mixture of:

- direct story progress;
- approved platform facts;
- elapsed time;
- prior choices;
- NPC relationship state;
- World Quest completion.

No chapter may require a hidden real-life behavior that the player did not knowingly authorize.

### Scenes

Scenes are concise, readable units intended for short sessions. Most scenes should take two to five minutes to read and respond to.

A scene may include:

- narrative text;
- one illustration or ambient visual;
- one primary choice;
- an optional explanation of recent World change;
- a clear return to Hearth.

Scenes should not bury practical actions inside decorative interaction.

## Reference opening arc: The Lamps of Brackenmere

### Premise

The village of Brackenmere still has lamps, but many no longer hold light. The problem is not a missing magical artifact. The lamp network has weakened because routes, agreements, and acts of maintenance have been neglected over time.

### Opening objective

Help Caretaker Mara restore one lamp near the common path.

### First real-life interpretation

A person completes an approved Quests-owned action. Epic Ordinary receives a minimized fact such as:

```json
{
  "event": "quests.quest_completed",
  "version": 1,
  "occurred_at": "...",
  "subject": {
    "account_id": "..."
  },
  "attributes": {
    "classification": "ordinary_commitment",
    "timing": "completed"
  }
}
```

The World does not receive the Quest title, private notes, project name, health information, or other unnecessary detail.

### First reaction

The lamp catches briefly, revealing a maker’s mark beneath years of soot. Mara recognizes it and realizes the lamp was part of an older route.

The visible explanation states that the reaction occurred because the player completed an approved real-life Quest and Epic Ordinary’s opening lamp rule was eligible.

### Why this works

The action matters without awarding coins, experience points, or moral value. The World responds with attention, memory, and possibility.

## Key characters

### Mara Vale, Caretaker of Lamps

**Role:** Opening guide and practical mentor  
**Voice:** Warm, direct, observant, dry humor  
**Values:** Reliability, maintenance, humility, shared responsibility  
**Flaw:** Carries too much alone and sometimes assumes asking for help will burden others  
**Visual cues:** Layered work clothes, repaired gloves, portable lamp tools, weathered satchel  
**Relationship dimensions:** trust, candor, shared work

Mara never praises the player in exaggerated terms. She notices specifics. Preferred line pattern:

> “You came back when you said you would. That matters here.”

Avoid:

> “You are the greatest hero Brackenmere has ever known!”

### Iven Reed, Keeper of the Common House

**Role:** Community memory and social connection  
**Voice:** Patient, story-rich, occasionally evasive  
**Values:** Hospitality, continuity, remembrance  
**Flaw:** Protects old stories so carefully that he sometimes prevents them from changing  
**Visual cues:** Ink-stained fingers, keys, folded notices, old wood and paper textures  
**Relationship dimensions:** openness, remembrance, reciprocity

### Tessa Morn, Apprentice Mapmaker

**Role:** Curiosity, experimentation, and future direction  
**Voice:** Energetic, precise, lightly irreverent  
**Values:** Discovery, clarity, testing assumptions  
**Flaw:** Moves quickly and may overlook emotional context  
**Visual cues:** rolled maps, charcoal marks, bright thread, asymmetrical layers  
**Relationship dimensions:** curiosity, confidence, collaboration

### Old Fen

**Role:** A semi-mythic presence associated with roads and neglected places  
**Voice:** Sparse, metaphorical, never omniscient  
**Values:** Patience, boundaries, natural consequence  
**Flaw:** Difficult to interpret and not always helpful  
**Visual cues:** reeds, rain-dark fabric, walking staff, reflected light  
**Relationship dimensions:** recognition, caution, understanding

## NPC relationship model

Relationships are multidimensional, not a single affection meter.

Possible dimensions include:

- trust;
- candor;
- shared work;
- curiosity;
- reciprocity;
- recognition;
- caution.

A relationship change must:

- have an identifiable World cause;
- avoid claiming to measure the person’s real moral worth;
- support different but valid relationship paths;
- never require compulsive daily engagement;
- remain isolated to Epic Ordinary.

Relationship state may influence dialogue, available help, scene framing, and story options. It should not simply unlock better rewards.

## World Quests

World Quests are fictional objectives owned by Epic Ordinary. They may be influenced by approved platform facts but must remain distinct from real-life Quests.

Examples:

- inspect three dark lamps along the east path;
- ask Iven what he remembers about the old route;
- decide whether to repair the bridge now or mark a safer detour;
- help Tessa compare two conflicting maps;
- return a recovered maker’s token to its family or place it in the common house.

A World Quest must not masquerade as medical, financial, legal, or interpersonal instruction.

## Real-life fact interpretation

### Allowed interpretation pattern

```text
Approved District fact
→ minimized platform event
→ World subscription and consent check
→ eligible narrative rule
→ idempotent World-only state change
→ explainable presentation
```

### Initial supported fact

Blueprint v1.0 should authorize only the minimum fact required for the first vertical slice:

- a Quests-owned completion fact classified broadly enough to avoid disclosing private content.

Additional fact categories require explicit event contracts, consent design, narrative justification, and privacy review.

### Prohibited interpretations

Epic Ordinary must not:

- infer diagnoses or health status;
- interpret missed actions as failure or moral weakness;
- punish absence;
- expose Quest titles or private notes without a separate explicit contract;
- edit, complete, reschedule, or delete District records;
- imply that fictional approval validates a real-life decision;
- create urgency through threats of irreversible loss while the player is away.

## Consequence philosophy

Consequences should create texture, not anxiety.

### Positive consequence

A place becomes more navigable, a character becomes more candid, or a forgotten history becomes available.

### Neutral consequence

Time passes, weather changes, another character makes progress, or a scene becomes available from a different angle.

### Difficult consequence

A plan may become harder, a character may disagree, or an opportunity may change. Difficult consequences must arise from fictional choices or transparent World rules, not from failing to use Koravik.

### Absence

During absence, the World may continue only in bounded, non-punitive ways. On return:

- no resources are confiscated;
- no relationship decays solely because time passed;
- no story becomes permanently inaccessible solely due to inactivity;
- the narrative acknowledges time gently and offers orientation.

## Writing style

### Narrative voice

Use close, sensory prose with restraint. Favor concrete details over lore dumps.

Preferred:

> Rain had found the crack in the lamp glass before Mara did. She turned the frame once, listening to the loose hinge, then held it toward you.

Avoid:

> The ancient and legendary Lamp of Eternal Destiny pulsed with immeasurable cosmic power.

### Dialogue

Dialogue should be readable aloud, distinct by character, and free of faux-medieval clutter.

Use contractions naturally. Humor may be dry or affectionate. Characters can disagree without becoming cruel.

### Interface copy

Practical controls use plain platform language:

- Continue
- Review why this changed
- Return to Hearth
- Not now
- Revoke permission

Do not rename basic controls with fantasy terms when that would reduce clarity.

## Visual direction

### Overall style

A modern storybook realism with painterly texture, clear silhouettes, warm practical light, and restrained magical effects.

The art should feel handcrafted but not nostalgic to the point of imitation. It must remain compatible with a contemporary accessible interface.

### Visual keywords

- weathered;
- luminous;
- intimate;
- restorative;
- grounded;
- quiet wonder;
- practical magic;
- layered history.

### Avoid

- glossy mobile-game rendering;
- exaggerated armor and weapon spectacle;
- excessive particle effects;
- tiny decorative text;
- muddy low-contrast scenes;
- visual dependence on color alone;
- generic medieval European stereotypes presented as universal fantasy;
- interfaces made to look like unreadable parchment.

## Color and light

Epic Ordinary may use a World-specific accent package while preserving platform accessibility.

Suggested palette roles:

- **Lamp amber:** attention, safe continuation, restored connection;
- **Rain blue-gray:** uncertainty, distance, reflection;
- **Moss green:** patient growth and repaired places;
- **Brick and clay:** community, work, material history;
- **Deep plum:** memory, evening, unresolved stories;
- **Warm paper:** narrative surfaces and quiet contrast.

Exact tokens must be defined in the technical design-token system and pass contrast requirements. Color never carries state alone.

## Typography

- Platform controls remain in the shared interface typeface.
- Narrative prose may use the approved serif companion typeface.
- Decorative lettering is limited to illustrations or headings with accessible text equivalents.
- Body text must support resizing, reflow, and user font preferences where feasible.

## Illustration system

### Scene illustrations

Use one strong composition rather than many decorative fragments. The focal point must remain understandable at reduced size.

### Character portraits

Portraits should show recognizable expression and role without locking all narrative scenes to fixed cinematic framing.

### Location art

Locations evolve through visible state layers:

- neglected;
- noticed;
- under repair;
- restored or transformed.

State changes should be meaningful but not always dramatic.

### Icons

World-specific icons may supplement shared icons but cannot replace standard control meaning. A lamp symbol may identify an Epic Ordinary reaction; it cannot replace the label **Review why this changed**.

## Motion and sound

### Motion

- subtle lamp ignition;
- slow environmental movement;
- restrained transitions between state layers;
- no reward explosions;
- no shaking, flashing, or urgency loops.

Reduced-motion mode must replace animation with immediate state changes or gentle fades.

### Sound

Sound is optional and off or conservative by default according to platform settings.

Potential sounds:

- low lamp chime;
- rain on roof;
- workshop tools;
- page or map movement;
- quiet environmental themes.

No critical information may depend on audio. Captions or textual equivalents are required for meaningful spoken or environmental content.

## Accessibility requirements

Epic Ordinary must support:

- semantic headings and landmarks;
- keyboard navigation;
- visible focus;
- screen-reader descriptions for illustrations that convey story information;
- decorative-image suppression;
- text resizing and reflow;
- high-contrast compatibility;
- reduced motion;
- optional sound with independent volume and mute controls;
- content notices before potentially distressing material;
- plain-language summaries for complex narrative state.

Alternative text should describe narrative significance, not every visual detail.

Example:

> The repaired east-path lamp now illuminates an older road marker that was previously hidden.

## Content boundaries

Epic Ordinary may explore:

- responsibility;
- grief and remembrance;
- community strain;
- uncertainty;
- repair;
- loneliness;
- change;
- reconciliation;
- limits of control.

It must handle these subjects without diagnosing the player, coercing disclosure, or presenting the World as therapy.

Content warnings should be specific, calm, and available before entry.

## Explainability presentation

Every event-driven reaction includes two layers:

### Narrative layer

What the character or place experienced.

### Plain-language layer

A disclosure such as:

> This scene became available after Quests recorded an approved completion fact. Epic Ordinary received only the completion classification and time—not the Quest title or notes.

The person can review the active permission or revoke future delivery from the same explanation path.

## Reference first vertical slice

### Starting state

- Player has one active Epic Ordinary installation.
- Mara is available at the west lamp.
- The west lamp is dark.
- No relationship change has occurred.

### Trigger

- One authorized `quests.quest_completed` event, version 1.

### Rule

- Event has not been processed before.
- Opening lamp scene is active.
- Relevant World permission remains granted.

### State change

- West lamp changes from `dark` to `flicker`.
- Maker’s mark becomes known.
- Mara’s `shared_work` increases by one bounded step.
- Opening continuation scene becomes available.
- A reason record links the World change to the event identifier and rule version.

### Player-facing result

- Hearth shows one World continuation card.
- Opening the card displays the short reaction scene.
- **Review why this changed** explains the source and minimized data.
- **Return to Hearth** is always available.
- Leaving and returning preserves the exact World state.

## Art production requirements

Every final asset must include:

- asset identifier;
- owning World package and version;
- intended placement;
- crop-safe regions;
- light and dark background behavior where relevant;
- alternative text or decorative designation;
- content-warning metadata when relevant;
- licensing and creator provenance;
- performance variants for supported breakpoints.

World packages must not contain executable art scripts or inaccessible text baked into images as the only presentation.

## Narrative production requirements

Every scene must declare:

- scene identifier and version;
- prerequisites;
- choices;
- state reads;
- state writes;
- event dependencies;
- idempotency behavior;
- explanation text;
- content notices;
- accessibility notes;
- resume behavior;
- test cases.

## Acceptance criteria

Epic Ordinary is ready to serve as the reference World when:

1. The opening arc can be understood without prior lore.
2. The first authorized Quest completion produces exactly one durable reaction.
3. The reaction remains meaningful without points, streaks, or loot.
4. The source fact and minimized data are explainable.
5. World State remains isolated from District truth.
6. The player can revoke future permission.
7. Absence does not create punishment.
8. Narrative and controls remain accessible.
9. The player can leave and resume safely.
10. The experience feels recognizably Koravik: calm, warm, purposeful, and alive.
