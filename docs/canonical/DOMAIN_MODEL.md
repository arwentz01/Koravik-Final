# Koravik Domain Model

**Version:** 3.0
**Status:** Canonical Conceptual Model
**Rewritten:** Build 204E

---

## 1. Purpose

This document defines the principal domain concepts, ownership relationships, and invariants of Koravik v3. It is conceptual rather than a final database schema.

## 2. Root Model

```text
Account
├── Profile and Preferences
├── District Data
├── Group Memberships
├── Installed Worlds
│   └── Independent World State
├── Companion Context
└── Audit History
```

## 3. Platform Entities

### Account

Represents a durable person-level identity.

Key attributes:

- identifier;
- status;
- primary email;
- locale;
- timezone;
- created timestamp;
- updated timestamp.

Invariants:

- survives group membership changes;
- survives World switching;
- is not publicly exposed by default;
- has one protected identity authority.

### Role and Capability

**Role** is a named bundle of capabilities. Canonical roles are Owner, Admin, Content Creator, and User.

**Capability** is a discrete authority to perform an action.

**AccountRole** associates an Account with a Platform role.

### ConsentGrant

Records permission for a defined purpose and scope.

### UserPreference

Stores Account-level preferences.

### NotificationPreference

Stores channel and delivery preferences.

### AuditRecord

Preserves privileged or meaningful system activity.

### PlatformEvent

Represents a versioned state-change message.

### MediaAsset

Represents uploaded or generated media with ownership, visibility, metadata, and storage references.

## 4. Hearth Entities

- **HearthLayout:** Defines user-specific home composition.
- **HearthWidget:** Represents an available Hearth presentation component.
- **HearthPlacement:** Associates a widget with a layout position and configuration.
- **HearthAcknowledgement:** Stores Hearth-specific dismissed, seen, or acknowledged state.

Hearth references source District data but does not duplicate ownership.

## 5. Chronicle Entities

- **ChronicleEntry:** A user-authored or user-approved record of memory, reflection, or life history.
- **ChronicleChapter:** Groups entries into a meaningful period or theme.
- **ChronicleTag:** Organizes entries.
- **ChronicleMediaLink:** Associates media with an entry.
- **ChronicleSourceReference:** References a source District event or record without replacing it.
- **ChronicleShare:** Defines explicit sharing.

Invariants:

- private by default;
- source restrictions propagate;
- World presentation does not alter canonical content.

## 6. Beacon Entities

- **BeaconPage:** Represents a public or shared landing page.
- **BeaconBlock:** Represents a page content or action block. Supported types may include Link, Text, E-mail, Call, SMS, vCard, WhatsApp, Wi-Fi, PDF, App, Images, Video, Social Media, Event, and 2D Barcode.
- **BeaconCard:** Represents a digital business card.
- **ShortLink:** Represents a managed redirect.
- **QrCodeDefinition:** Represents a QR or related 2D code definition.
- **BeaconCampaign:** Represents an outreach campaign or public call to action.
- **BeaconEngagement:** Stores privacy-aware visits or action events.

Invariants:

- publishing is explicit;
- sensitive contact information requires warning;
- Gather owns event participation.

## 7. Gather Entities

- **GatherEvent:** Represents a scheduled event or experience.
- **EventScheduleItem:** Represents part of an event schedule.
- **RSVP:** Represents invitation or attendance intent.
- **SignupSlot:** Represents a volunteer, item, or participation slot.
- **SignupCommitment:** Represents a person's commitment to a slot.
- **VolunteerShift:** Represents a bounded volunteer assignment.
- **EventAssignment:** Represents an assigned responsibility.
- **WaitlistEntry:** Represents ordered waiting status.
- **RequestedItem:** Represents a needed potluck or event contribution.
- **AttendanceRecord:** Represents check-in or verified participation.
- **EventAnnouncement:** Represents participant communication.
- **EventResource:** Represents an event-linked document, link, or media asset.
- **EventFollowup:** Represents a survey, review, or post-event action.

Invariants:

- hosts see only necessary participant information;
- private events do not become public through Beacon;
- Chronicle memory creation remains user-controlled.

## 8. Health Entities

- **HealthEntry:** A generic foundation for supported personal health observations where appropriate.
- **Meal:** Represents a meal occurrence.
- **FoodItem:** Represents food or nutrition information.
- **Recipe:** Represents reusable meal composition.
- **WeightEntry:** Represents a recorded weight.
- **BodyMeasurement:** Represents a user-selected measurement.
- **HydrationEntry:** Represents fluid intake.
- **ExerciseEntry:** Represents activity or exercise.
- **SleepEntry:** Represents sleep duration or quality.
- **MedicationRoutine:** Represents a personal medication schedule or routine.
- **MedicationCompletion:** Represents adherence tracking without claiming clinical authority.
- **SymptomObservation:** Represents a user-reported observation.
- **BiometricEntry:** Represents a supported personal biometric value.
- **HealthGoal:** Represents a Health-owned goal.
- **HealthMilestone:** Represents meaningful Health progress.
- **HealthIntegration:** Represents a connection to an external source.
- **IntegrationImport:** Records provenance and synchronization state.

Invariants:

- private by default;
- high-level events expose minimal data;
- no clinical diagnosis authority;
- unsafe incentives are prohibited.

## 9. Quests Entities

- **Quest:** Represents a goal, task, habit, project, journey, responsibility, or World objective.
- **QuestStep:** Represents a required or optional action.
- **QuestRecurrence:** Defines repeating behavior.
- **QuestAssignment:** Associates a Quest with an Account, Household, Organization, Gather event, or World.
- **QuestDependency:** Represents ordering or prerequisites.
- **QuestProgress:** Represents current state.
- **QuestCompletion:** Represents a completed action or Quest.
- **QuestMilestone:** Represents meaningful progress.
- **QuestReflection:** Stores Quest-specific reflection or a reference to Chronicle.

Invariants:

- Quest type is explicit;
- World Quests remain distinct;
- Quests does not own source District records;
- private goals remain private.

## 10. Companion Entities

- **CompanionConversation:** Represents a conversation context.
- **CompanionMessage:** Represents a conversation message.
- **CompanionMemory:** Represents an approved remembered item.
- **CompanionPermission:** Represents scoped access to District context.
- **CompanionDraft:** Represents generated but unexecuted content.
- **CompanionSuggestion:** Represents a proposed plan, action, or reflection.
- **CompanionActionProposal:** Represents a consequential action awaiting approval.
- **CompanionActionApproval:** Records approval or refusal.
- **CompanionFeedback:** Stores user feedback on usefulness or correctness.

Invariants:

- no unrestricted database access;
- consequential action requires approval;
- source ownership remains external;
- World memory remains separate.

## 11. Household Entities

- **Household:** Represents an optional shared home-life context.
- **HouseholdMembership:** Associates an Account with a Household.
- **HouseholdRole:** Defines scoped authority.
- **HouseholdResource:** Represents an approved shared item or reference.
- **HouseholdPreference:** Represents household-level configuration.

Invariants:

- membership is optional;
- leaving does not erase personal data;
- household administrators do not gain unrelated Account access.

## 12. Organization Entities

- **Organization:** Represents an optional nonprofit, club, team, community, or service structure.
- **OrganizationMembership:** Associates an Account with an Organization.
- **OrganizationRole:** Defines scoped organizational authority.
- **OrganizationTeam:** Represents an internal group.
- **OrganizationResource:** Represents an approved shared resource.
- **OrganizationCampaignReference:** References Beacon campaigns.
- **OrganizationEventReference:** References Gather events.

Invariants:

- Organization does not own Account identity;
- Beacon owns public presentation;
- Gather owns event coordination;
- authority is capability-based.

## 13. World Entities

- **WorldDefinition:** Represents a creator-authored World specification.
- **WorldPackage:** Represents a versioned distributable package.
- **WorldInstallation:** Associates a World package with an Account or supported context.
- **WorldActivation:** Represents the currently active World.
- **WorldState:** Represents user-specific narrative state.
- **WorldStateMigration:** Defines state transformation between schema versions.
- **WorldEventSubscription:** Defines approved Platform Event access.
- **WorldReaction:** Maps an approved event to World effects.
- **NPC:** Represents a fictional character.
- **NPCRelationshipDefinition:** Defines relationship dimensions.
- **NPCRelationshipState:** Stores user-specific relationship state.
- **StoryNode:** Represents a narrative unit.
- **StoryChoice:** Represents a branching decision.
- **StoryCondition:** Defines entry or branch conditions.
- **StoryEffect:** Defines a World-only result.
- **WorldItem:** Represents a fictional item.
- **WorldInventoryEntry:** Associates a World item with World State.

Invariants:

- World State is independent per World;
- World effects do not alter Platform truth;
- permissions are declared;
- updates require compatible migration;
- uninstall behavior is explicit.

## 14. Creator Studio Entities

- **CreatorProject:** Represents an authoring workspace.
- **CreatorCollaborator:** Associates contributors and roles.
- **CreatorDraft:** Represents unpublished content.
- **CreatorAsset:** Represents authored media and metadata.
- **CreatorValidationResult:** Stores validation findings.
- **CreatorTestPersona:** Represents synthetic test state.
- **CreatorTestRun:** Represents preview or validation execution.
- **CreatorRelease:** Represents a versioned package release.
- **CreatorSubmission:** Represents Marketplace review submission.

Invariants:

- production user data is not available by default;
- ordinary content cannot execute arbitrary server-side code;
- releases are versioned and checksummed.

## 15. Marketplace Entities

- **MarketplaceListing:** Represents discoverable content.
- **Publisher:** Represents an approved creator or organization identity.
- **PackageCompatibility:** Represents supported Platform and schema versions.
- **InstallationRecord:** Represents install status.
- **MarketplaceReview:** Represents a review or trust signal.
- **ModerationCase:** Represents policy or security review.
- **ReleaseChannel:** Represents stable, beta, or another supported distribution status.

## 16. Arena Entities

- **ArenaActivity:** Represents a game or challenge definition.
- **ArenaSession:** Represents active participation.
- **ArenaParticipant:** Associates an Account with a session.
- **ArenaScore:** Represents Arena-specific scoring.
- **ArenaReward:** Represents an optional Arena reward.
- **ArenaLeaderboardEntry:** Represents explicitly enabled ranking.

Invariants:

- Arena is optional;
- rankings require explicit participation;
- Health data does not become competitive by default.

## 17. Relationship Summary

```text
Account
├── has Platform Roles and Capabilities
├── may join Households
├── may join Organizations
├── owns District records
├── may install many Worlds
├── may activate one primary World
├── has independent World State per World
└── may authorize Companion context
```

```text
Beacon Page
└── may reference Gather Event

Gather Event
├── owns RSVP
├── owns Signup Slots
├── owns Attendance
└── may generate Quest references

Health Record
├── may create a minimal Platform Event
├── may be surfaced in Hearth
├── may produce a Chronicle milestone
└── may be interpreted by an authorized World
```

## 18. Cross-Domain Reference Model

A cross-domain reference should include:

- source module;
- source type;
- source identifier;
- relationship purpose;
- visibility snapshot only if justified;
- created timestamp.

References must not become shadow copies of the source record.

## 19. Deletion and Retention

Deletion behavior should distinguish:

- user deletion;
- archive;
- soft deletion;
- legal retention;
- group departure;
- World uninstall;
- Account closure.

Deleting a source record must invalidate or restrict derived references appropriately.

World uninstall must never delete Platform or District truth.

## 20. Domain Invariants

- Account identity is durable.
- Household membership is optional.
- Organization membership is optional.
- Every capability has one primary owner.
- Platform truth and World truth remain separate.
- Each World has independent state.
- Companion actions remain approval-bound.
- Health data remains private by default.
- Public publishing is explicit.
- Creator content is structured and versioned.
- Cross-domain integration uses approved interfaces or events.
- Historical records are not silently lost during role, group, or World changes.
