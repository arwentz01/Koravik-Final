# Documentation Authority

## Authority order

When documents disagree, use this order:

1. `FOUNDATIONAL_DECISIONS.md`
2. `canonical/CONSTITUTION.md`
3. `canonical/CHARTER.md`
4. Accepted architecture decision records, once created
5. `canonical/ARCHITECTURE.md`
6. `canonical/DOMAIN_MODEL.md`
7. `canonical/WORLD_ENGINE.md`
8. `canonical/EVENT_PHILOSOPHY.md`
9. `canonical/DEFINITION_OF_DONE.md`

Repository code, migrations, and tests become implementation truth only after
they are deliberately added to this repository. Historical implementation
claims do not override these documents.

## Canonical documents

| Document | Purpose |
|---|---|
| `CONSTITUTION.md` | Stable product principles and boundaries |
| `CHARTER.md` | Mission, scope, and platform responsibilities |
| `ARCHITECTURE.md` | Technical direction and module boundaries |
| `DOMAIN_MODEL.md` | Core concepts, ownership, and relationships |
| `WORLD_ENGINE.md` | World runtime and narrative behavior |
| `EVENT_PHILOSOPHY.md` | Cross-module fact publication and consumption |
| `DEFINITION_OF_DONE.md` | Minimum completion standard |

These files were consolidated from the final product review. They preserve the
product vision, but unresolved contradictions are governed by
`FOUNDATIONAL_DECISIONS.md` until the canonical text is revised.

## Archived documents

The `archive/` directory is non-authoritative.

- `ROADMAP_LEGACY.md` records historical build claims and must not be used as
  the roadmap for this repository.
- `IMPLEMENTATION_HANDOFF_LEGACY.md` points to an earlier implementation and
  must not be used to begin work here.

The new roadmap and implementation handoff will be written only after the
foundation decisions and build path are accepted.
