# Account Data Export and Closure

**Status:** Approved
**Effective build:** 019

## Export

Account exports are account-scoped, auditable, and expire after seven days. They include a manifest and source-owned sections while excluding password hashes, session secrets, encryption material, and other accounts' data.

## Closure

Closure requires the exact confirmation phrase and begins a seven-day cancellation window. Processing is bounded, restartable, and owner-specific. Quests, Chronicle, Companion, Worlds, and Platform each record an outcome step.

The Platform revokes credentials and anonymizes durable identity only after owner handlers finish. Minimal audit evidence and the closure receipt may be retained. Shared package definitions and unrelated system records are never deleted.

Account closure must not use an unreviewed database-wide cascade as its business process.
