Deployment Guidelines

Status: Required direction; implementation details require an accepted ADR

Koravik Final deploys reproducibly from GitHub to Bluehost-compatible shared
hosting. Deployment places an exact application release on the server.
Migration and release verification remain separate controlled actions.

Release artifact

Build the artifact from an exact commit on authoritative main after CIpasses.

Include application source, production dependencies, built assets, migrationfiles, release metadata, and a portable checksum manifest.

Record the build number, commit SHA, artifact creation time, and expectedschema state.

Exclude secrets, environment configuration, logs, caches, sessions, useruploads, backups, and persistent runtime data.

Deploy the same artifact that passed release validation.

Server layout

Keep shared configuration and persistent storage outside versioned releases.

Deploy each artifact into a distinct release directory.

Activate a release using the safest atomic mechanism supported by hosting.

Keep a bounded number of prior application releases for code rollback.

Never treat application rollback as database rollback.

Deployment sequence

Confirm the exact main commit and successful CI state.

Create and verify the reproducible release artifact.

Confirm production backup status and maintenance requirements.

Upload and verify the artifact without activating it.

Acquire an upgrade lock.

Preview pending migrations using the shared migration runner.

Apply approved migrations through Foundry or the approved CLI process.

Activate the exact release.

Run health and smoke checks.

Record the commit, migration state, checks, and outcome.

Release the upgrade lock.

Any sequence that requires activation before a backward-compatible migrationmust define its expand-and-contract plan explicitly.

Migration controls

Ordinary web requests must never run migrations.

Deployment must not automatically apply migrations.

Foundry and CLI operations must use the same migration runner and ledger.

Foundry migration actions require authentication, capability authorization,CSRF protection, an upgrade lock, pending-change preview, and explicit backupconfirmation.

A migration checksum mismatch or schema verification failure stops upgrade.

A failed migration stops release completion and requires a documented recoverydecision.

Finite cron processing may run approved bounded backfills. Permanent queueworkers are not assumed.

Verification

The release is verified only after:

The health endpoint identifies the expected commit and release.

Database connectivity and schema state are correct.

Authentication and authorization operate normally.

Critical routing and the changed capability pass smoke testing.

Affected cron and outbox processing remain healthy.

Logs contain no new critical errors and expose no sensitive data.

Failure and rollback

If activation fails before an incompatible database change, return to thepreviously verified release.

If a database change was applied, follow the reviewed recovery plan. Neverimprovise destructive down-migrations in production.

Preserve logs, release metadata, and migration records needed for diagnosis.

Do not label a failed or partially verified deployment as released.

Deployment mechanism decision

The exact GitHub Actions workflow, authentication method, Bluehost directory
layout, retention policy, backup command, and activation mechanism must be
accepted in a deployment ADR before Build 1 is released. Earlier Koravik
deployment systems are not automatically authoritative.
