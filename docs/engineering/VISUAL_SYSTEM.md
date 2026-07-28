# Visual System Contract

**Status:** Approved
**Version:** 1.0
**Effective build:** 022

## Shared rendering

Authenticated pages pass through the shared application shell and then the visual-system renderer. The renderer may add presentation classes, landmarks, contextual location, and preference classes. It may not change source ownership, authorization, or committed data.

## Preference enforcement

- `appearance` applies system, light, or dark presentation.
- `reduced_motion` disables nonessential animation and transitions in addition to honoring the operating-system preference.
- `high_contrast` increases text, border, link, and focus distinction.

Preferences must affect the rendered page rather than remain informational settings only.

## Component vocabulary

Approved shared patterns include page headers, local action groups, surfaces, editor surfaces, metadata/provenance, trust panels, notices, and state panels. New route-specific markup should use these patterns before inventing a new visual language.

## Safety

The renderer must escape generated labels, preserve CSRF fields and form actions, keep one main landmark, retain the skip link, and avoid inserting private data into page classes or URLs.

## Route states

Each owner remains responsible for authorization and business-state responses. The visual system standardizes how empty, validation, authorization, missing, expired, partial-failure, and success states are presented.