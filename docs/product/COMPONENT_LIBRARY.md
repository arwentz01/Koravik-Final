# Koravik Component Library

**Status:** Draft for Blueprint v1.0 review  
**Version:** 1.0-draft  
**Owner:** Product and UX Architecture  
**Last reviewed:** 2026-07-28

## 1. Purpose

This document defines the shared component contracts that make Koravik consistent, accessible, and maintainable. Components are product behavior, not merely reusable CSS.

A component is complete only when its semantics, states, accessibility behavior, responsive behavior, content rules, and ownership boundaries are defined.

## 2. Design-token contract

All components must use shared tokens rather than isolated visual values.

Required token groups:

- typography;
- spacing;
- sizing;
- border radius;
- elevation;
- motion duration and easing;
- focus treatment;
- neutral and semantic colors;
- District accent references;
- World accent references within approved boundaries;
- breakpoints and content widths;
- layering and overlay levels.

Semantic tokens must describe purpose, such as `surface-raised`, `text-muted`, `status-danger`, or `focus-ring`, rather than a specific raw color.

## 3. Component principles

Shared components must be:

- understandable without decoration;
- keyboard operable;
- screen-reader compatible;
- resilient to longer text and localization;
- usable at supported zoom levels;
- consistent across Districts;
- themeable only within approved token boundaries;
- explicit about loading, disabled, error, and success states;
- free of hidden data ownership or domain logic.

District-specific composites may use shared primitives, but they must not fork core behavior without an accepted reason.

## 4. Buttons

Koravik has four button roles.

### Primary

The dominant action for the current screen or contained decision. Normally only one primary button appears within a decision region.

### Secondary

A meaningful alternative that does not compete with the primary action.

### Quiet

Low-emphasis actions such as dismiss, edit, reveal details, or navigate within context.

### Danger

Destructive or high-risk actions. Danger styling does not replace explicit consequence language or confirmation.

Button requirements:

- use a clear action label;
- expose loading state without changing width unexpectedly;
- prevent accidental repeat activation;
- distinguish disabled from loading;
- retain an accessible name for icon-only variants;
- support keyboard activation according to native semantics;
- never use a non-interactive element styled as a button.

## 5. Links

Links navigate. Buttons act.

Links must:

- remain visually identifiable without relying on color alone;
- describe destination or context;
- indicate external destinations where relevant;
- not trigger destructive or state-changing actions through navigation alone;
- preserve visited-state behavior when useful and privacy-safe.

## 6. Form controls

Shared controls include:

- text input;
- textarea;
- select;
- combobox;
- checkbox;
- radio group;
- switch;
- date and time controls;
- file input;
- search input;
- field group;
- validation summary.

Every field contract includes:

- persistent label;
- optional description;
- required or optional status;
- current value;
- validation state;
- error message;
- disabled and read-only behavior;
- sensitive-data guidance when applicable.

Switches are reserved for settings that take effect immediately. Checkboxes are used for selection, acknowledgement, or form submission choices.

## 7. Cards

Cards group related information and actions. They must not become the default container for every block of content.

A card may contain:

- eyebrow or ownership label;
- title;
- short summary;
- status or metadata;
- one primary card action;
- limited secondary actions;
- optional illustration or accent.

The entire card should be clickable only when it represents one clear navigation target and nested interactive controls are absent.

Canonical card categories:

- orientation card;
- record summary card;
- action card;
- World continuation card;
- Companion proposal card;
- notification card;
- empty-state card.

World and Companion cards must identify their source and distinguish fictional interpretation or proposal from canonical District data.

## 8. Status indicators

Status must be communicated using text plus shape, icon, or other non-color cue.

Common semantic statuses:

- neutral;
- informational;
- pending;
- success;
- warning;
- danger;
- archived;
- draft;
- proposal;
- World-only.

Status labels should describe state, not judgment. Avoid labels such as `Failed` for a person who did not complete an intention.

## 9. Alerts and banners

### Inline alert

Explains a condition within the relevant content region.

### Page banner

Communicates a page-level warning, confirmation, or limitation.

### Global banner

Reserved for platform-wide conditions such as maintenance, degraded service, or security-sensitive notices.

Alerts require:

- semantic role appropriate to urgency;
- concise heading when more than one sentence;
- clear action when action is possible;
- persistent display for critical issues;
- no automatic dismissal for errors or warnings that require understanding.

## 10. Toasts

Toasts provide brief acknowledgement for low-risk completed actions.

They must not be the only location for critical information, validation errors, permission changes, or destructive consequences. Toasts should support pause on hover or focus and accessible announcements without stealing focus.

## 11. Dialogs and sheets

Dialogs interrupt the current flow and therefore require a strong reason.

Dialog requirements:

- descriptive title;
- explicit consequence or decision;
- logical initial focus;
- keyboard containment for modal dialogs;
- escape or close behavior unless the decision is genuinely blocking;
- return focus to the initiating control;
- no nested modal dialogs;
- full-screen sheet adaptation on small screens when appropriate.

A confirmation dialog should use action-specific labels such as `Delete Reflection` rather than `Confirm`.

## 12. Menus

Menus contain compact action or navigation choices. They must use correct menu semantics only when application-style keyboard behavior is implemented; otherwise, use a labeled list of links or buttons.

Overflow menus should not hide the primary action or frequently needed accessibility controls.

## 13. Tabs

Tabs switch between related views of the same context. They are not a substitute for global or District navigation.

Tabs require:

- a bounded set;
- keyboard arrow navigation;
- persistent active state;
- direct-link behavior when views represent meaningful URLs;
- responsive handling that does not make labels unreadable.

## 14. Accordions and disclosure

Disclosure components hide supporting information, not essential consequences or required fields.

They must expose expanded state programmatically and retain understandable headings when collapsed.

## 15. Tables and data grids

Use a semantic table for genuinely tabular data.

Requirements:

- proper headers and relationships;
- meaningful captions or accessible names;
- keyboard-accessible sorting and filtering;
- visible sort direction;
- responsive alternatives that preserve data relationships;
- explicit row-action labels;
- no horizontal scrolling that hides row identity without a stable reference.

Data grids with spreadsheet-like keyboard interaction require a separate, deliberate contract and should not be introduced casually.

## 16. Lists

Canonical list patterns include:

- simple content list;
- record list;
- activity timeline;
- selectable list;
- notification list;
- search results;
- task or Quest list.

List items must identify their owner, status, and primary destination when those are not obvious from context.

## 17. Navigation components

Shared navigation includes:

- global sidebar;
- mobile bottom navigation;
- navigation drawer;
- breadcrumb;
- District local navigation;
- pagination;
- previous and next controls.

Navigation components must preserve current-location indication using more than color and must not expose unauthorized destinations.

## 18. Search components

Search components include:

- search launcher;
- search field;
- recent searches;
- result groups;
- ownership labels;
- filter controls;
- no-results state.

Autocomplete must announce result count and selection changes accessibly. It must not leak protected records through suggestions.

## 19. Empty states

An empty state explains:

1. What normally appears here.
2. Why nothing appears now, when known.
3. Whether this is expected.
4. What meaningful action is available.

Empty states should not pressure people to create data merely to make the interface look populated.

## 20. Loading components

Loading patterns include:

- inline progress indicator;
- button progress state;
- skeleton placeholder;
- determinate progress;
- background activity indicator.

Skeletons should approximate stable content structure and respect reduced-motion preferences. Spinners require nearby text when waiting may exceed a brief moment.

## 21. Error components

Error patterns include:

- field error;
- validation summary;
- inline recovery panel;
- page error;
- global service error;
- not-found and access-denied states.

Every error should state what happened, what was preserved, and the next safe action when known.

## 22. Ownership labels

Koravik requires a lightweight shared label to identify the owning District, group, platform capability, or World when content appears outside its native context.

Ownership labels must:

- use the canonical name;
- remain understandable without color;
- link to source context when authorized and useful;
- not imply that Hearth, search, Companion, or a World owns composed records.

## 23. Companion proposal component

A Companion proposal is a distinct composite component.

Required regions:

- `Companion proposal` label;
- proposed outcome;
- source summary;
- owning District for the resulting action;
- editable draft when appropriate;
- consequence statement;
- approve, revise, and dismiss actions;
- memory disclosure when relevant.

Approval must never be triggered by opening or navigating through the proposal.

## 24. World reaction component

A World reaction communicates a fictional consequence derived from an approved platform fact.

Required regions:

- World identity;
- narrative reaction;
- concise reason or `Why this happened` disclosure;
- World State effect when appropriate;
- continuation action;
- clear indication that the effect belongs to that World.

Sensitive underlying facts must remain minimized.

## 25. Accessibility validation

Every shared component must be validated for:

- native semantics or justified ARIA;
- keyboard interaction;
- visible focus;
- screen-reader output;
- text resize and reflow;
- color contrast;
- reduced motion;
- touch target size;
- error identification;
- disabled and read-only distinction;
- high-contrast and forced-color modes where supported.

Automated checks supplement but do not replace manual keyboard and assistive-technology review.

## 26. Component governance

A new shared component may be added only when:

- no existing component or composition satisfies the need;
- its behavior is useful across more than one context or is a critical canonical composite;
- accessibility behavior is documented;
- all states are documented;
- design tokens are used;
- a test and story or preview fixture are provided during implementation.

District-specific components should be promoted to the shared library only after their general contract is clear.

## 27. First vertical-slice component set

Build 001 and its first complete product loop require, at minimum:

- application shell navigation;
- page header;
- primary, secondary, quiet, and danger buttons;
- link;
- form field and checkbox;
- card;
- ownership label;
- status label;
- inline alert;
- toast;
- confirmation dialog;
- empty state;
- loading indicator;
- Quest summary and detail composition;
- World reaction composition;
- global error page.

## 28. Acceptance criteria

The component library is ready for Blueprint approval when:

1. Core primitives and canonical composites are defined.
2. Semantic state behavior is consistent.
3. Component ownership remains separate from domain ownership.
4. Accessibility contracts are explicit.
5. Responsive adaptations are defined.
6. Companion and World content cannot be confused with committed District records.
7. The first vertical slice can be assembled without inventing unreviewed UI primitives.
8. New component governance prevents uncontrolled variants and visual drift.
