# Builds 208–217: Durable Cross-Module Drafts

Builds 208–217 make cross-module draft paths recoverable without creating destination records prematurely.

## Implemented slices

1. Durable Source Review Draft Save — room-note and Gather follow-up review pages can save a durable Source Review draft.
2. Durable Source Review Draft Resume — `/source-review/drafts/{id}/resume` resumes a saved Source Review draft.
3. Recovery Center Integration — `source_review.*` drafts appear in Recovery Center with a resume link.
4. Draft Provenance Timeline — resumed drafts show source owner, source reference, and the draft path timeline.
5. Draft Expiry Visibility — resumed and Recovery Center draft cards show the 30-day expiry.
6. Source Inbox Durable Draft Items — saved Source Review drafts appear back in the Source Inbox.
7. Sensitive Field Exclusion Reuse — drafts reuse `platform_form_drafts`, which strips CSRF, tokens, and credential fields.
8. Destination Still Requires Review — durable drafts point to destination review forms without auto-saving Quest or Chronicle records.
9. System Health Checkpoint — health identifies Build 217 and `durable-cross-module-drafts`.
10. Release Contract Coverage — the release suite covers durable draft save/resume, Recovery Center links, provenance timeline, Source Inbox integration, and checkpoint metadata.

## Contract

- Durable drafts do not approve, execute, publish, send, or create destination records.
- Drafts expire after 30 days through the existing `platform_form_drafts` mechanism.
- Recovery Center can resume Source Review drafts without exposing credentials.
- Build 217 is the current operational checkpoint: `durable-cross-module-drafts`.
