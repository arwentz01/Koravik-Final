Publishing Standards

Status: Required

Publishing means making an accepted change available on the authoritative
GitHub main branch. It is distinct from implementing, deploying, migrating,
and releasing.

Build preparation

Define a numbered build's scope and acceptance criteria before implementation.

Develop on a dedicated branch.

Keep commits focused, logical, and reviewable.

Ship documentation, migrations, tests, and implementation together.

Never commit secrets, runtime files, private uploads, environmentconfiguration, production data, or dependencies that should be reproduciblyinstalled.

Do not reuse build numbers or inherit build claims from an earlier repository.

Publication gate

A build is published only when:

Its acceptance criteria are satisfied.

Formatting, static analysis, architecture, security, migration, and automatedtest checks pass.

Fresh-install and supported-upgrade tests pass when the schema changes.

The accepted change is committed and pushed.

The change is merged into the authoritative repository's main branch.

main remains buildable, testable, and releasable.

The roadmap and implementation handoff accurately identify the accepted state.

The publication record identifies the build, commit, and validation performed.

A local commit, ZIP archive, unpushed branch, open pull request, or draft pullrequest is not published.

Branch and review rules

main is the sole authoritative integration branch.

Direct publication to main is prohibited once branch protection and CI areavailable.

A pull request must describe scope, acceptance criteria, migrations,
security/privacy impact, testing, and rollback considerations.

Required review and CI checks must pass before merge.

Resolve review findings with additional commits. Do not rewrite appliedmigration history.

Use a consistent merge strategy accepted for the repository. The mergedcommit must remain traceable to the build and pull request.

Release relationship

GitHub publication does not imply production deployment.

Deployment uses a reproducible artifact built from the exact accepted maincommit.

Pending migrations are included but applied only through the controlleddeployment process.

A build may be published without being released when a release gate remainsoutstanding.

Prohibited practices

Do not edit production application files directly.

Do not use manual FTP replacement or upload isolated hotfix files.

Do not publish from an uncommitted or dirty working tree.

Do not bypass failing checks by disabling them.

Do not claim publication before the accepted commit exists on GitHub main.

Do not claim completion when documentation contradicts repository state.
