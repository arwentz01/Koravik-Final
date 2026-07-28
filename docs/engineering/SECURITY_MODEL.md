# Security, Privacy, and Consent Model

**Status:** Approved for Blueprint v1.0  
**Version:** 1.0  
**Owner:** Platform Security

## Security objective

Koravik protects agency, identity, personal records, group participation, and World boundaries. Security is part of the product contract, not a deployment afterthought.

## Trust boundaries

Primary boundaries exist between:

- anonymous visitors and authenticated Accounts;
- Accounts and other Accounts;
- Accounts and Households or Organizations;
- District modules;
- real-life records and World State;
- Companion proposals and approved actions;
- creator-authored packages and the Platform runtime;
- Koravik and external providers;
- application runtime and administration or deployment tooling.

## Identity and authentication

- One durable Account represents one person.
- Passwords use a modern adaptive password hash supported by PHP.
- Minimum password length is eight characters; longer passphrases are encouraged.
- Login, password reset, email change, and privilege changes use expiring single-use protections.
- Sessions rotate after authentication and privilege changes.
- Concurrent sessions are visible and revocable.
- Multi-factor authentication is planned for privileged accounts and may later be offered broadly.

## Authorization

Authorization is capability-based. Roles bundle capabilities but do not replace contextual checks. Every consequential action evaluates:

- authenticated actor;
- platform role;
- group role where relevant;
- resource ownership;
- visibility;
- consent;
- current resource state;
- administrative restrictions.

The canonical role hierarchy is Owner, Admin, Content Creator, and User. Owner powers remain exceptional, auditable, and unavailable through ordinary role assignment.

## Privacy classifications

Records are classified as:

1. Public
2. Shared with a defined audience
3. Private
4. Sensitive
5. Highly sensitive

Health details, private Chronicle content, authentication material, Companion memory, and certain relationship or location data require elevated controls. Worlds should receive derived conditions instead of source records whenever possible.

## Consent

Consent must be specific, informed, revocable, and visible. Consent records include scope, purpose, recipient, effective time, version, and revocation state.

Revocation stops future use. Historical consequences already validly created are handled according to retention and product rules, but must not silently expand their use.

## Companion

Companion may explain, summarize, suggest, and draft. It may not perform consequential actions without explicit approval. Memory must be sourced, inspectable, correctable, deletable, purpose-limited, and separated between real-life and fictional contexts.

## Worlds and creator content

World packages are structured data and assets, not arbitrary executable code. Packages declare permissions, event subscriptions, compatibility, content warnings, and data needs. The Platform validates packages and enforces runtime boundaries.

A World cannot:

- query District tables directly;
- receive undeclared events;
- modify source facts;
- access another World's state;
- execute arbitrary server or browser code;
- use personal data for undisclosed profiling or advertising.

## Application security

Required controls include:

- CSRF protection for browser writes;
- context-aware output escaping;
- prepared statements for database access;
- strict upload handling;
- secure headers;
- bounded request sizes;
- rate limiting for abuse-prone paths;
- safe redirects;
- secret management outside source control;
- production error suppression with traceable logs.

## Audit

Audit records are required for authentication events, role and capability changes, consent changes, administrative actions, data exports, account deletion, Companion-approved execution, World installation and permission changes, and sensitive record access where appropriate.

Audit records are append-oriented, access-controlled, minimized, timestamped in UTC, and correlated with actor and request identifiers.

## Retention and deletion

Each module defines retention by record type. Account deletion must distinguish immediate revocation, queued deletion, legally or operationally necessary retention, anonymization, and backup expiry. Soft deletion is not a substitute for a retention policy.

## Incident readiness

The platform must support credential rotation, session revocation, package suspension, feature disabling, audit review, backup restoration, and user notification where appropriate.

## Security definition of done

A feature is not complete until authentication, authorization, privacy classification, consent, audit, abuse cases, error behavior, and negative tests have been addressed.