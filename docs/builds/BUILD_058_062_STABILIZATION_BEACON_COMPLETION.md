# Builds 058–062 — Lifecycle Stabilization and Beacon Completion

## Build 058 — Lifecycle stabilization

- Added cancellable agenda reminders with durable unsubscribe-token hashes.
- Prevented cancelled reminders from being queued by the finite worker.
- Tightened event existence and owner authorization checks.
- Preserved manual lookup as the dependable day-of fallback.

## Build 059 — Approved outcome application

- Added one application ledger per approved Gather outcome proposal.
- Added idempotent application state, destination type, reference, attempts, and failure fields.
- Approval creates a pending application; explicit apply completes the handoff.
- Application records a stable cross-module reference without letting Gather directly rewrite destination-owned content.

## Build 060 — Beacon domain administration

- Added an Owner/Admin domain-management home.
- Added hostname registration, verification tokens, root redirects, verified/suspended states, and revision history.
- Kept `krvk.nl` as the default platform domain and `koravik.com` as its root redirect.
- Certificate issuance remains deployment infrastructure, not an application fiction.

## Build 061 — Beacon link management

- Added label, destination, slug, domain, active/paused/archived state, and revision management.
- Added reserved-slug validation and domain-neutral stable link identity.
- Added fields for destination health and future preferred-domain/fallback behavior.

## Build 062 — QR scanning and day-of hardening

- Added a secure-context browser-camera scanner using the BarcodeDetector API when supported.
- Added camera permission, unsupported-browser, and failure states.
- Kept manual attendee lookup as the explicit fallback.
- Camera scanning is considered available only on HTTPS and supported browsers.

## Deployment

Run all migrations:

```bash
php tools/migrate.php
```

Continue the finite workers:

```bash
php tools/mail-worker.php 20
php tools/gather-reminder-worker.php 100
```

Both `koravik.com` and `krvk.nl` must route to the application with TLS and the original Host header preserved.

## Boundaries

- Gather outcome application records an authorized handoff; destination modules still own their final records and review experiences.
- DNS verification does not provision TLS certificates.
- Pausing or archiving a Beacon address does not delete the stable Beacon record.
- QR camera support never removes the manual day-of path.
