# Builds 108–117 — Workflow Resilience

## Outcome

Koravik now provides shared recovery infrastructure for interrupted forms,
duplicate submissions, expired or revoked sessions, and unfinished work before
the roadmap returns to forward-facing product development.

- **108:** shared accessible form-error summary contract;
- **109:** safe value preservation with credential-field exclusion;
- **110:** expiring account-owned form drafts;
- **111:** database-backed duplicate-submission claims;
- **112:** explicit already-processed and changed-session recovery messages;
- **113:** tracked signed-in sessions with current-device protection;
- **114:** individual and bulk revocation of other sessions;
- **115:** unified recovery center for drafts, access, shared spaces, and delivery operations;
- **116:** persistence, duplicate, revocation, sanitization, and accessibility regression tests;
- **117:** migration, CI, documentation, and health-check stabilization.

The new controls live at `/recovery-center` and `/settings/sessions`. Drafts
expire after 30 days, idempotency claims after one day, raw session identifiers
are never stored, and password, token, CSRF, and submission-key fields are
excluded from draft payloads.
