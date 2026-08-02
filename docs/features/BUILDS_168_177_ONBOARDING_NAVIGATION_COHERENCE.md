# Builds 168–177: Onboarding, Navigation, and Everyday Coherence

Builds 168–177 make Koravik easier to enter, re-enter, and navigate without weakening source ownership.

## Implemented slices

1. First-Run Guided Setup — onboarding now explains Quests, Chronicle, Worlds, Companion permissions, optional Health privacy review, and a gentle first choice.
2. Returning User Orientation Upgrade — `/return` names what changed, what may be stale, what is safe to ignore, and one manageable next step.
3. Hearth Today Command Strip — Hearth now adds direct links to Today’s Focus, the next Quest, the latest World reaction, pending reflection proposals, and unread notifications.
4. Cross-Module Breadcrumbs — the visual system adds source-aware breadcrumbs across routed pages.
5. Unified Empty-State Guide Cards — onboarding and empty-state treatment now point users toward safe next actions and source ownership explanations.
6. Source Ownership Explainer — `/guide#source-ownership` explains which surface owns which records and where composition stops.
7. Guide / Help Center Completion — `/guide` is grouped into Act, Reflect, Share, Coordinate, Privacy, and Troubleshooting.
8. Settings Navigation Polish — `/settings` groups profile, accessibility, notification/privacy, security, data, and authorized admin links by consequence.
9. Mobile Navigation Pass — the app shell header carries the mobile navigation pass with an explicit menu toggle and grouped navigation.
10. Route-Level Error Recovery Polish — error routes gain preserved-state guidance and safe exits to Hearth, Guide, and Recovery Center.

## Contract

- Health checkpoint identifies Build 177 and `everyday-coherence-navigation`.
- Navigation improvements compose existing modules and do not transfer ownership between Hearth, Districts, Worlds, Companion, Beacon, Gather, or account data.
- Recovery language avoids payload bodies, private notes, and secret values.
- The release suite covers the visible strings and integration seams for these slices.
