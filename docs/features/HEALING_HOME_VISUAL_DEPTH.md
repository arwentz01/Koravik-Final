# Healing Home Visual Depth

Status: implemented vertical product slice.

This slice makes Healing Home feel more like the heart of Koravik: a place a person enters, reads, and moves through, not merely a dashboard of links.

## Completed workflow

An authenticated person can return to `/home`, read what changed since they were gone, understand the current atmosphere, choose a room by symbolic identity and state, enter a room, and move room-to-room without returning to the top-level map each time.

## Product behavior

- The home page includes an arrival scene with atmosphere, latest change, open-room count, and a non-pressuring next threshold.
- The room map includes symbolic room markers for stronger visual identity while retaining explicit text state.
- Room detail pages include symbolic headings and a `Move through the Healing Home` navigation region.
- Garden, Workshop, and Library include stronger visual motifs for tending, making, and meaning.
- Atmosphere-specific styling distinguishes quiet morning, green dusk, and workshop lamplight.

## Accessibility and boundaries

All decorative symbols are paired with text labels or marked `aria-hidden`. Room movement is ordinary navigation, not a hidden state change. The slice does not create Quests, Chronicle entries, Companion memory, World facts, notifications, Health records, Beacon attendance, Gather communication, or hidden scores.

## Verification

Covered by the release suite test `Healing Home room expansion supports making, welcome, meaning, tending, and privacy`, extended with visual-depth contracts.
