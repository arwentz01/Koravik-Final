# Koravik Application Shell

**Status:** Draft for Blueprint v1.0 review  
**Version:** 1.0-draft  
**Owner:** Product and UX Architecture  
**Last reviewed:** 2026-07-28

## 1. Purpose

The application shell is the persistent frame that makes Koravik feel like one coherent platform while allowing Districts, Worlds, Companion, and administrative surfaces to retain appropriate identity.

The shell must be implemented before broad feature expansion. It establishes navigation, orientation, responsive behavior, accessibility, global feedback, search, notifications, context boundaries, and recovery patterns that every feature will inherit.

## 2. Shell responsibilities

The shell owns presentation and coordination for:

- global navigation;
- current-location orientation;
- Account access and settings entry;
- global search entry;
- notifications entry;
- Companion entry and proposal status;
- active World context and World switching entry;
- accessibility and appearance preferences;
- global loading, error, and offline feedback;
- skip links and focus restoration;
- responsive navigation behavior.

The shell does not own District records, World State, notification source truth, Companion memory, or search indexes. It composes access to those capabilities through registered contracts.

## 3. Canonical regions

### 3.1 Skip navigation

The first focusable control must allow keyboard and assistive-technology users to skip directly to main content. Additional skip targets may include primary navigation and contextual actions when the page warrants them.

### 3.2 Global navigation

Global navigation provides stable access to the person's principal Koravik areas.

Initial order:

1. Hearth
2. Quests
3. Chronicle
4. Gather
5. Health
6. Beacon, when available to the Account
7. Worlds

Companion is a platform capability and should normally use a dedicated launcher rather than appear as an equal District destination. Creator Studio, Marketplace, Household, Organization, and administration links appear only when applicable and authorized.

Navigation labels must remain understandable without icons. Icons may reinforce but never replace text in expanded navigation.

### 3.3 Header

The header provides contextual orientation and global actions. It may include:

- page or District title;
- breadcrumb or parent context when needed;
- search launcher;
- notifications launcher;
- Companion launcher;
- active World indicator;
- Account menu.

The header should not become a second dashboard. Page-specific primary actions belong in the page header or content region, not among unrelated global controls.

### 3.4 Main content

The main region contains exactly one page-level heading and the active route's primary experience. District pages may provide local navigation, but it must not compete with global navigation or create ambiguous hierarchy.

### 3.5 Contextual panel

Large screens may support a contextual side panel for Companion, record details, filters, or World continuation. Only one contextual panel should be dominant at a time.

The panel must:

- identify its context;
- preserve the main page state;
- be dismissible;
- restore focus to its launcher;
- become a full-screen sheet or dedicated route on narrow screens when necessary;
- never hide an approval or consequence behind collapsed content.

### 3.6 Global feedback region

System-level feedback includes connection state, maintenance notices, successful cross-page actions, and errors that cannot be attached to a specific field.

Feedback must be announced accessibly and remain visible long enough to understand. Critical failures must not disappear automatically.

## 4. Desktop layout

The default desktop shell uses:

- a persistent left navigation rail or sidebar;
- a top contextual header;
- a centered, width-bounded content canvas;
- an optional right contextual panel;
- stable spacing based on the design system grid.

The sidebar may collapse to icons only when each item retains an accessible name, hover or focus labels are available, and the current location remains unmistakable.

Hearth may use a wider composition canvas than form-focused screens, but content width must remain readable.

## 5. Tablet layout

Tablet layouts may use a collapsible navigation drawer. The current location remains visible in the header. Contextual panels may overlay or replace a portion of content but must preserve focus order and dismissal behavior.

Split views are allowed only when both panes remain usable at the available width.

## 6. Mobile layout

Mobile uses a focused, single-column experience.

The shell may use a bottom navigation bar for the most frequent destinations, with remaining destinations in a menu. Bottom navigation must remain bounded; it must not expand indefinitely as modules become available.

Recommended initial bottom destinations:

- Hearth
- Quests
- Chronicle
- Worlds or `More`, depending on enabled capabilities

Global search, notifications, Companion, and Account access remain available from the header or `More` menu.

Mobile overlays should generally become full-screen sheets with explicit titles and close controls.

## 7. Hearth treatment

Hearth is the default authenticated destination and the primary orientation surface.

Within the shell, Hearth should:

- feel calmer and more spacious than operational lists;
- surface a bounded number of meaningful items;
- distinguish practical actions, World continuation, and Companion proposals;
- preserve ownership labels without visual clutter;
- avoid becoming a configurable wall of unlimited widgets;
- support a useful experience without an installed World or any group membership.

## 8. District identity

District identity is conveyed through restrained accents, labels, and local information architecture. District theming must not alter core control behavior, accessibility, or layout grammar.

The shell must always make the transition between Districts understandable. A user should never mistake a World narrative surface for Health, Quests, Chronicle, or another real-life record owner.

## 9. World context

When a World is active, the shell may show a subtle World indicator and continuation entry. The World may define approved accent assets within its own surfaces, but it may not restyle the global shell so heavily that platform identity, permissions, or navigation become unclear.

World switching must reveal:

- the currently active World;
- preserved state in inactive Worlds;
- the difference between switching, suspending, restarting, and uninstalling;
- any bounded background processing permissions.

## 10. Companion entry

Companion should be available without dominating the interface.

The launcher may indicate:

- a new proposal;
- an explanation available for the current page;
- an unfinished draft;
- an action awaiting approval.

It must not use anthropomorphic urgency, simulated distress, or persistent interruption to attract attention.

When opened, Companion must clearly identify whether it is discussing:

- the current real-life page;
- a specific record;
- an active World;
- a general question;
- a proposal awaiting approval.

## 11. Search

Global search is a shell entry point into a platform capability.

Search results must:

- identify the owning District or World;
- enforce authorization before displaying result content;
- avoid leaking sensitive snippets;
- support keyboard navigation;
- preserve the query when opening and returning from a result;
- distinguish direct records from suggested actions or Companion-generated interpretations.

## 12. Notifications

The shell notification center composes notifications from registered owners.

It should support:

- unread and acknowledged state;
- grouping by source and time without hiding importance;
- direct navigation to the relevant authorized context;
- notification preference access;
- explanation of why each notification was sent.

Badge counts should remain restrained. Koravik should prefer meaningful indicators over perpetual red-number pressure.

## 13. Account menu

The Account menu provides access to:

- profile and identity settings;
- privacy and consent controls;
- notification preferences;
- accessibility and appearance settings;
- connected Households and Organizations;
- installed Worlds;
- security and active sessions;
- sign out.

Administrative impersonation or elevated context, if ever supported, must be prominently and continuously indicated.

## 14. Authentication boundary

Unauthenticated pages use a simplified public shell. After successful authentication, the person should return to the authorized destination they originally requested or to Hearth.

Session expiry must preserve unsaved work where safely possible and clearly distinguish local drafts from committed records.

## 15. Loading and transition behavior

The shell should remain stable while route content changes.

- Navigation and header should not flash or reconstruct unnecessarily.
- Route transitions should announce the new page title.
- Skeletons may preserve layout for predictable content.
- Full-screen blocking loaders are reserved for operations that truly prevent other safe activity.
- Optimistic updates are allowed only when rollback is reliable and visible.

## 16. Error and maintenance behavior

The shell must provide consistent surfaces for:

- not found;
- access denied;
- expired session;
- temporary service failure;
- maintenance mode;
- offline or degraded operation;
- unsupported browser or required capability.

Error pages should retain enough shell navigation to recover when security permits. They must state whether an attempted change was saved.

## 17. Accessibility contract

The shell must provide:

- semantic landmarks;
- a single main landmark;
- skip links;
- visible focus and logical focus order;
- keyboard-operable navigation and menus;
- accessible names for icon controls;
- focus trapping only for true modal contexts;
- focus restoration after overlays close;
- live-region announcements for route and global-state changes;
- reduced-motion support;
- support for zoom, reflow, and text resizing;
- non-color current-location indicators.

## 18. Registration contract

Districts and platform modules integrate with the shell through explicit registration rather than direct shell modification.

A navigation registration should define:

- stable identifier;
- label;
- route;
- icon reference;
- required capability;
- availability rule;
- sort position within an approved region;
- optional badge provider contract;
- local navigation contract, if applicable.

The shell may reject registrations that exceed bounded navigation regions or violate product hierarchy.

## 19. First-build shell scope

The first implementation slice requires only:

- authenticated desktop and mobile shell;
- Hearth destination;
- Quests destination and one Quest detail flow;
- active Epic Ordinary indicator and continuation entry;
- basic Account menu;
- global feedback region;
- accessible navigation and focus management;
- explicit placeholders for search, notifications, and Companion that do not falsely imply completed capability.

## 20. Acceptance criteria

The application shell is ready for Blueprint approval when:

1. Navigation hierarchy is stable and bounded.
2. Desktop, tablet, and mobile behavior are specified.
3. District and World context cannot be confused.
4. Companion proposals are visibly distinct from committed records.
5. Global loading, error, offline, and session-expiry behavior is defined.
6. Keyboard, focus, landmarks, announcements, zoom, and reduced-motion behavior are defined.
7. Module integration occurs through explicit registrations.
8. The shell remains useful with only Hearth and Quests enabled.
9. The design does not depend on unlimited dashboard customization.
10. The first vertical slice can be implemented without inventing new shell behavior in code.
