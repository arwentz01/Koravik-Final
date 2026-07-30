# Database Standards

Status: Required

These standards govern every Koravik Final schema, migration, query, and
database-backed operation.

## Supported database direction

- Use MySQL or MariaDB through PDO.
- All migrations must remain compatible with the production versions approved
  for Bluehost.
- Use `InnoDB` unless an accepted ADR requires otherwise.
- Standardize text on `utf8mb4` with `utf8mb4_unicode_ci` unless a documented
  compatibility decision supersedes it.
- Use UTC for stored timestamps and convert at the application boundary.

## Identifiers and naming

- Use `BIGINT UNSIGNED` consistently for internal primary and foreign keys.
- A foreign key column must exactly match the referenced key's type and
  signedness.
- Internal sequential IDs must never be exposed as public identifiers.
- Use `snake_case` for tables and columns.
- Use descriptive names:
  - Foreign keys: `fk_<table>_<reference>`
  - Unique constraints: `uq_<table>_<purpose>`
  - Non-unique indexes: `idx_<table>_<purpose>`
- Never use SQL reserved words as table names, column names, constraints, or
  aliases.

## Ownership and integrity

- Each table has one owning module.
- A module must not directly mutate another module's tables.
- Every foreign key must specify intentional `ON DELETE` and `ON UPDATE`
  behavior.
- Create parent tables before child tables and remove them in reverse dependency
  order.
- Use database constraints for structural integrity such as nullability,
  uniqueness, and referential integrity.
- Validate portable business rules in PHP rather than relying on
  database-specific features such as `CHECK` constraints.
- Use transactions for multi-record operations that must remain atomic.

## Migration immutability

- Never modify an applied migration. Create a new migration instead.
- Migration identifiers and filenames must be sequential, unique, and never
  reused.
- Each applied migration records its identifier, checksum, execution time, and
  application release.
- A checksum mismatch for an applied migration must stop the upgrade.
- A failed migration must never be recorded as successful.
- Migration files must not depend on sample data, unpredictable row order, or
  environment-specific absolute paths.

## Idempotency and reconciliation

- Prefer idempotent migrations where practical, but never allow idempotency to
  hide schema drift.
- `CREATE TABLE IF NOT EXISTS` is acceptable only when the existing table is
  verified against the expected schema.
- A guarded `ALTER TABLE` must inspect the current schema before changing it.
- Existing-but-incorrect schema must fail with a clear diagnostic.
- Schema-verified reconciliation is reserved for exceptional historical repair.
  It requires a new, reviewed migration that defines the expected state.

## Destructive and data migrations

- Destructive changes require an accepted migration plan, explicit backup
  confirmation, and deliberate review.
- Prefer expand-and-contract changes when compatibility must span a deployment.
- Data backfills must be deterministic, resumable where practical, bounded for
  shared hosting, and safe to retry.
- Large changes must use finite batches and must not depend on a permanent
  worker.
- Application rollback must never be assumed to reverse a database migration.

## Query rules

- Use prepared PDO statements for all variable input.
- Select only required columns.
- Add indexes based on demonstrated access patterns and avoid duplicates.
- Avoid unbounded result sets and N+1 query patterns.
- Define deterministic ordering whenever row order matters.
- Treat query and schema errors as failures. Do not silently continue with
  partial or ambiguous state.

## Verification

CI must verify:

- A completely fresh installation from an empty supported database.
- Upgrade from the latest supported schema checkpoint.
- Migration checksum enforcement.
- Foreign-key type and signedness consistency.
- Expected charset, collation, engine, constraints, and indexes.
- Critical transaction rollback and retry behavior.
