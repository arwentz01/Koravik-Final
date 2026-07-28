# Definition of Done

A numbered build is complete only when all applicable requirements are true:

1. The repository installs from a fresh clone.
2. `composer validate --strict` and `composer check` pass.
3. GitHub Actions passes for the exact commit.
4. Database changes run successfully on a clean database and an upgraded database.
5. The documented production browser journey is exercised on Bluehost.
6. Errors are logged without leaking sensitive information.
7. Documentation describes the verified state accurately.
8. No placeholder behavior is represented as complete.

A commit, generated class, or passing unit test alone does not complete a build.
