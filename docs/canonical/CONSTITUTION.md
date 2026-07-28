# Koravik Constitution

**Version:** 3.0
**Status:** Canonical Governance Standard
**Rewritten:** Build 204E

---

## Preamble

Koravik exists to serve people, not to control them.

This Constitution defines the non-negotiable principles that govern product design, engineering, administration, artificial intelligence, content creation, data access, and future expansion.

When a feature, World, business goal, or technical shortcut conflicts with this Constitution, the Constitution prevails unless it is deliberately amended through documented governance.

## Article I — Human Agency

- The person remains the final authority over their life.
- Koravik may inform, organize, remind, suggest, reflect, and prepare.
- Koravik must not manipulate, shame, coerce, or create artificial dependency.
- Consequential actions require explicit approval.
- Users must be able to correct important assumptions and records.
- Optional systems must remain optional.

## Article II — Identity

- Each person has a durable Account identity.
- Household, Organization, District, and World participation do not replace the Account.
- Role and membership changes must not erase personal data.
- Public identity must remain distinct from private identity.
- Beacon owns outward-facing identity presentation.
- The Platform owns authentication and Account authority.

## Article III — Truth

- The Platform and Districts preserve canonical real-life truth.
- Worlds preserve only World-specific truth.
- A World may interpret approved facts but may not rewrite them.
- Companion must distinguish fact, inference, suggestion, and fiction.
- Summaries must remain traceable to their source.
- Cross-District duplication must not create competing authorities.

## Article IV — District Ownership

- Every capability has one primary owner.
- Integration does not transfer ownership.
- Hearth surfaces but does not absorb District business logic.
- Chronicle remembers but does not replace source records.
- Quests owns action state but does not own every referenced domain.
- Beacon owns outreach, public presentation, short links, and digital identity tools.
- Gather owns events, RSVP, signup, attendance, and volunteer coordination.
- Health owns personal wellness records and related privacy controls.

## Article V — Privacy

- Privacy is the default.
- Sharing must be explicit, scoped, and understandable.
- Sensitive data must receive stronger protection.
- Health data must not be broadly exposed through events, Worlds, analytics, or social features.
- Public publishing must be clearly identified before execution.
- Access must be revocable where practical.
- Data retention and deletion behavior must be documented.
- Audit records must avoid unnecessary sensitive content.

## Article VI — Consent

- Consent must identify purpose and scope.
- Consent must not be hidden inside unrelated actions.
- New permissions require renewed disclosure.
- Worlds must declare requested Platform Event access.
- Companion must not silently broaden its context.
- Creators must not access production user data by default.

## Article VII — Companion

- Companion proposes; the user decides.
- Companion may not silently perform consequential actions.
- Companion memory must be visible, correctable, and deletable.
- World memory must remain separate from real-life memory.
- Companion must not claim clinical, legal, financial, or emergency authority.
- Companion must acknowledge uncertainty.
- Companion must not impersonate a real person.
- Fictional NPC interaction must be clearly identified.

## Article VIII — Health and Safety

- Koravik must not promote dangerous health behavior.
- Weight must not be treated as the sole measure of health.
- Health features must avoid shame and punitive design.
- Competitive Health rankings are prohibited by default.
- Koravik must not impersonate licensed care.
- High-risk situations must be handled with appropriate safety boundaries.
- Worlds and rewards must not encourage unsafe restriction, overtraining, medication misuse, self-harm, or reckless conduct.

## Article IX — Worlds

- Worlds are optional.
- World switching must preserve Platform and District data.
- Each World has independent World State.
- Uninstalling a World must not erase real-life records.
- Worlds may access only approved event contracts.
- Worlds may not bypass permissions.
- Worlds must disclose content warnings and permissions.
- World updates must preserve state or provide explicit migrations.
- Fiction must not be deceptively represented as real life.

## Article X — Creators

- Creators control authored content, not Platform authority.
- Creator Studio must use structured, validated content formats.
- Arbitrary server-side execution is prohibited for ordinary content packages.
- Creator testing must use synthetic data by default.
- Creator packages must declare permissions, dependencies, compatibility, licensing, and content warnings.
- Published content is subject to security, policy, privacy, and moderation review.

## Article XI — Groups

- Household and Organization membership is optional.
- Users may participate fully without joining either.
- Group authority must be scoped.
- Leaving a group must not erase personal history.
- Shared records must clearly distinguish personal ownership from group ownership.
- Group administrators may not gain access beyond their granted capabilities.

## Article XII — Roles and Authority

The canonical hierarchy is:

```text
Owner
→ Admin
→ Content Creator
→ User
```

- Owner has protected ultimate authority.
- Admin has scoped system and user-management authority.
- Content Creator has creator capabilities without automatic administrative power.
- User has standard participation capabilities.
- Permissions should be capability-based even when roles provide defaults.
- Privileged actions must be auditable.

## Article XIII — Accessibility

- Accessibility is a product requirement, not an optional enhancement.
- Core functions must support keyboard navigation.
- Meaning must not rely on color alone.
- Text should scale appropriately.
- Images require meaningful alternatives where relevant.
- Audio and video require captions or transcripts where practical.
- Worlds and creator content must meet Platform accessibility expectations.

## Article XIV — Data Durability

- User data must not be silently lost during upgrades, role changes, World switching, or membership changes.
- Migrations must be versioned and testable.
- Destructive operations require deliberate handling.
- Export and portability should be supported where practical.
- Backups and restore procedures must be documented.
- Cached and derived data must not outlive restricted source access improperly.

## Article XV — Security

- Least privilege is required.
- Authentication, authorization, consent, and audit are Platform responsibilities.
- Secrets must not be committed to source control.
- Inputs must be validated.
- Outputs must be escaped.
- Database access must use safe parameterization.
- Uploads must be validated and isolated.
- Privileged operations require protection against misuse and forgery.
- Security failures must not be hidden.
- Dependencies and content packages must be reviewable.

## Article XVI — Engineering

- Koravik v3 is a custom PHP 8.3+ application.
- Laravel is retired and must not be reintroduced.
- The architecture is a modular monolith unless an accepted decision changes it.
- District boundaries must be reflected in code organization and data ownership.
- Shared Platform services must not be duplicated by Districts.
- Public contracts must be versioned.
- Tests must protect critical behavior.
- Deployment must remain compatible with the supported hosting environment.
- Historical documents may reference retired architecture only when clearly identified as historical.

## Article XVII — Change

- Canonical documents must remain internally consistent.
- Architectural changes require a documented decision.
- Constitution changes require explicit review and rationale.
- Product expansion must preserve neutrality across Worlds.
- Temporary implementation shortcuts must not silently become permanent policy.
- When documentation and implementation conflict, the conflict must be resolved explicitly.

## Article XVIII — Amendment

An amendment should include:

- the proposed change;
- the reason;
- affected principles;
- privacy and security impact;
- migration impact;
- backward compatibility;
- approval authority;
- effective version;
- affected canonical documents.
