# Design System

## Visual character

Koravik should feel calm, warm, purposeful, grounded, modern, organic, trustworthy, timeless, and human.

It must not resemble an MMO interface, corporate HR system, electronic medical record, generic workspace clone, gamified habit tracker, or social media feed.

## Typography

- Interface typography: Inter or an equivalent highly readable sans serif.
- Narrative typography: Source Serif, Literata, or an equivalent readable serif.
- System fallbacks must preserve legibility and layout stability.

Typography should use a restrained scale, comfortable line length, clear hierarchy, and sufficient spacing. Narrative type is reserved for moments that benefit from reflection or story tone rather than ordinary controls.

## Color direction

The base system should use warm neutrals with restrained semantic color. District accents help orientation without replacing shared interaction patterns.

Suggested District accents:

- Hearth: ember
- Chronicle: plum
- Health: green
- Quests: amber
- Gather: blue
- Beacon: sky
- Companion: indigo

World packages may define approved accent tokens and assets, but they may not compromise readability, interaction consistency, or accessibility.

## Layout

- Use a consistent 12-column responsive grid where appropriate.
- Apply a documented spacing scale rather than arbitrary values.
- Use rounded corners consistently and sparingly.
- Limit elevation to three purposeful levels.
- Prefer whitespace and hierarchy over decorative separators.
- Preserve comfortable touch targets and keyboard paths.

## Core controls

Only four button intents are required:

1. Primary — the main action for the current context.
2. Secondary — an important alternative.
3. Quiet — low-emphasis supporting action.
4. Danger — destructive or high-risk action.

Button styling must not be invented independently by Districts or Worlds.

## Cards

Cards should group related information, not decorate every element. A card needs a clear purpose, heading or accessible label, predictable spacing, and no unnecessary nested-card structure.

## Forms

Forms should be predictable, forgiving, and explicit:

- persistent labels rather than placeholder-only fields;
- clear required and optional indicators;
- inline validation associated with the field;
- preserved input after recoverable errors;
- consequence-aware confirmation;
- understandable help text;
- accessible error summaries for long forms.

## Motion

Motion should clarify change, continuity, or spatial relationship. It must be restrained, interruptible, and disabled or reduced when the person requests reduced motion.

## Accessibility baseline

The design system should meet or exceed WCAG AA expectations, including:

- sufficient text and control contrast;
- visible focus states;
- full keyboard operation;
- meaningful semantic structure;
- screen-reader labels and announcements;
- reduced-motion support;
- scalable text and reflow;
- non-color-only status communication;
- generous target sizing;
- understandable language and errors.

## Component governance

Shared components belong to the platform design system. Districts and Worlds may compose them and provide bounded variants through tokens, but may not fork basic behavior without an accepted design or architecture decision.