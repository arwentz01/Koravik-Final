# Deployment and Operations Standard

**Status:** Approved for Blueprint v1.0  
**Version:** 1.0  
**Owner:** Platform Operations

## Supported environment

Koravik targets PHP 8.3+, Apache-compatible shared hosting, and MySQL or MariaDB. The application must operate without permanent workers, root access, containers, or provider-specific services.

## Release shape

A release is an immutable application revision plus versioned migrations, declared configuration requirements, static assets, and release notes. Environment-specific values remain outside source control.

Recommended layout separates releases, shared writable storage, and the active public target where hosting permits. Deployment must never expose source, configuration, logs, backups, or private uploads through the public document root.

## Pre-deployment gates

Before production deployment:

- required tests pass;
- dependencies are locked and reproducible;
- migrations are reviewed;
- configuration changes are documented;
- backup status is verified;
- release notes identify user-visible and operational impact;
- rollback or forward-fix behavior is understood.

## Configuration and secrets

Configuration is loaded from environment or protected host-level files. Secrets are never committed. Production uses distinct credentials with least privilege. Secret rotation must not require code changes.

## Database changes

Migrations run through the project migration runner and are recorded durably. Deployments do not rely on manual SQL edits. Destructive or long-running changes require explicit operational planning, backups, and compatibility with the currently deployed code during transition.

## Workers and scheduling

Outbox delivery, delayed consequences, notifications, and maintenance use finite, lock-safe commands suitable for cron. Each invocation has bounded time, bounded batch size, idempotent behavior, retry limits, and observable results.

No feature may require a continuously running worker to remain correct.

## Health and observability

The application provides a minimal health surface that distinguishes application availability from deeper dependency checks without leaking secrets. Structured logs include timestamp, severity, trace or correlation identifier, component, and safe context.

Logs exclude passwords, tokens, session identifiers, private journal text, detailed health content, and unnecessary personal data.

## Backups and restoration

Production requires scheduled database and required-file backups, retention appropriate to the data, access controls, and periodic restoration tests. A backup that has not been restored in testing is not considered proven.

## Maintenance mode

Maintenance behavior must be accessible, informative, and safe. It should preserve static support information where possible and must not expose operational details.

## Rollback

Code rollback is permitted only when database compatibility is preserved. Forward-only migrations are preferred; rollback planning may use compatible releases, corrective migrations, feature flags, or restoration when necessary.

## Release verification

After deployment, verify:

- home and authentication routes;
- database connectivity;
- writable storage;
- migration version;
- health check;
- cron command execution;
- the first critical journey;
- logs for unexpected errors.

## Incident operations

Operators must be able to disable integrations, suspend a World package, revoke sessions, rotate secrets, stop scheduled consumers, restore a backup, and communicate service impact.

## Shared-hosting discipline

Architecture and release choices must respect memory, execution-time, filesystem, process, and database limits. Work is paginated and bounded. Large exports and processing jobs use resumable chunks rather than unbounded requests.