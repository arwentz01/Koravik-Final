# Release Candidate Checklist

**Status:** Approved
**Version:** 1.0
**Effective build:** 021

## Application shell

- One shared shell is applied to authenticated server-rendered pages.
- Primary places, utilities, and account/trust controls remain visually distinct.
- Active location uses `aria-current`.
- Mobile navigation preserves every desktop destination.
- Skip links and main landmarks remain available.

## Route-state review

For each player-facing route, verify useful empty, validation, unauthorized, missing-record, expired-action, and safe-failure behavior where applicable. Errors must not disclose credentials, tokens, SQL, filesystem paths, or other accounts’ records.

## Accessibility

- Keyboard-only access and visible focus.
- Labels and error messaging associated with controls.
- No horizontal overflow at narrow widths or 200% zoom.
- Reduced-motion and increased-contrast preferences remain effective.
- Touch targets are usable without precision pointing.

## Security and privacy

- Cross-account direct-object-reference attempts fail closed.
- Every mutation has CSRF protection.
- Authentication sessions regenerate and honor durable session versions.
- Companion context and World facts require current consent.
- Export and closure are account-scoped.
- User-authored output is escaped before rendering.

## Operations

- `php tools/migrate.php` is idempotent.
- Worker and account-closure commands are bounded.
- Recovery, export, and proposal expiration cleanup has an operational owner.
- Deployment, rollback, backup, and restore steps are documented before release.
- Health output identifies the deployed build.

## Release decision

Release only when the current `main` passes syntax, migration, authentication, shell, ownership, privacy, and prior-flow regression checks together. A failed critical check blocks release rather than becoming a known issue hidden in release notes.