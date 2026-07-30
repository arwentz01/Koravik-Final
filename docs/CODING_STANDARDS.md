# Coding Standards

Status: Required

These standards govern all Koravik Final application code. A narrower accepted
ADR may add constraints, but it must not silently weaken these rules.

## General principles

- Use PHP 8.3 or newer and enable strict types in every PHP source file.
- Follow PSR-12 formatting and PSR-4 autoloading.
- Prefer clear, explicit code over framework-like magic.
- Keep the application a modular monolith with enforceable module boundaries.
- Each module owns its domain records, business rules, and write operations.
- Cross-module behavior uses declared services or versioned platform events.
- Do not read or write another module's tables directly.
- Keep controllers thin. Put business behavior in application and domain
  services with explicit inputs and outputs.
- Use dependency injection rather than hidden global state or service location.
- Treat user input, external data, package content, and event payloads as
  untrusted.

## Naming and structure

- Use descriptive English names and avoid unexplained abbreviations.
- Use `PascalCase` for classes and enums, `camelCase` for methods and local
  variables, and `UPPER_SNAKE_CASE` for constants.
- Namespace application code under the accepted Koravik root namespace.
- Organize code by module and responsibility, not by an undifferentiated
  collection of controllers, models, or helpers.
- One source file should have one primary responsibility.
- Do not use SQL reserved words as identifiers or aliases.

## Types and behavior

- Declare parameter types, return types, and property types wherever PHP allows.
- Use value objects or enums for meaningful constrained concepts.
- Avoid boolean parameters when a named option or enum communicates intent more
  clearly.
- Handle expected failures explicitly. Do not suppress errors.
- Log operational context without logging secrets or unnecessary sensitive data.
- Use transactions for business operations that must succeed or fail together.
- Make retries safe when an operation can be repeated by cron, an outbox worker,
  or a user refresh.

## Security and privacy

- Enforce authentication, capability authorization, ownership, and consent on
  the server.
- Use parameterized PDO statements for all variable SQL.
- Escape output for its destination context.
- Require CSRF protection for state-changing browser requests.
- Store passwords only with PHP's current password hashing API.
- Never commit secrets, credentials, environment files, production data, or
  private runtime artifacts.
- Minimize event and Companion payloads before they cross an ownership boundary.
- Record auditable evidence for consequential administrative, migration,
  permission, and Companion-approved actions.

## User interface

- Use the shared application shell, design tokens, and reusable components.
- Meet WCAG 2.2 AA expectations from the first implementation.
- Support keyboard navigation, visible focus, semantic markup, useful labels,
  sufficient contrast, reduced motion, and understandable validation errors.
- Do not create module-specific shells or styling systems without an accepted
  architectural reason.

## Quality and change discipline

- Add or update automated tests with every behavior change.
- Test authorization boundaries and failure paths, not only successful paths.
- Static analysis, formatting, architecture checks, and tests must pass before
  publication.
- Do not disable a validation rule merely to make a build pass.
- Keep changes focused and reviewable.
- Documentation, migrations, tests, and implementation for a behavior ship
  together.
- Do not mix unrelated cleanup into a functional build.
- A build is a product increment with defined scope and acceptance criteria. A
  commit is not automatically a build.

Database changes are additionally governed by
`docs/DATABASE_STANDARDS.md`.
