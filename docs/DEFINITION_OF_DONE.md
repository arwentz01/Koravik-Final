# Definition of Done

Status: Required

This document defines project-wide status language and completion gates. The
canonical product document at `docs/canonical/DEFINITION_OF_DONE.md` continues
to define feature-quality expectations. Both apply.

## Status language

| Status | Meaning |
|---|---|
| Planned | Scope and acceptance criteria are documented; implementation has not begun |
| Implemented | Code exists and relevant local validation passes |
| Published | The accepted commit exists on authoritative GitHub `main` |
| Deployed | The exact published commit has reached the target environment |
| Migrated | Required database migrations were successfully applied and recorded |
| Released | Deployment, required migrations, health checks, and smoke tests passed |
| Complete | Acceptance criteria, tests, documentation, publication, and required release verification are finished |

These words must not be used interchangeably. A local commit is not published.
A deployed artifact is not released until verification succeeds.

## A build is complete only when

- Scope and acceptance criteria were defined before implementation.
- Functional and failure-path criteria are satisfied.
- Module ownership and architectural boundaries remain intact.
- Authentication, authorization, consent, privacy, and audit impacts are
  addressed.
- Accessibility requirements are verified.
- Tests cover changed behavior and important regression paths.
- Formatting, static analysis, architecture, security, and test checks pass.
- Database changes comply with the database standards.
- Fresh installation and supported upgrade paths pass when affected.
- User-facing and technical documentation are current.
- The roadmap and implementation handoff describe actual repository state.
- The accepted change is published to GitHub `main`.
- Any required release is deployed, migrated, health-checked, smoke-tested, and
  recorded.

## Evidence required

The completion record must identify:

- Build number and scope.
- Acceptance criteria result.
- Commit SHA on `main`.
- Automated validation performed.
- Migration identifiers and outcome, when applicable.
- Deployed environment and release identifier, when applicable.
- Health and smoke-test results.
- Known limitations or deliberately deferred work.

## Honest partial completion

When a gate remains open, report the highest status actually achieved and name
the outstanding gate. Do not mark a build complete merely because its primary
screen or happy path appears to work.
