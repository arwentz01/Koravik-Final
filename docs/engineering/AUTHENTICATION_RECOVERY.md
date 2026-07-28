# Authentication Recovery and Session Security

**Status:** Approved
**Version:** 1.0
**Effective build:** 020

## Purpose

Koravik must allow legitimate account recovery without revealing whether an email address exists, weakening session security, or relying on administrator intervention.

## Recovery contract

- Recovery responses are identical for known and unknown addresses.
- Tokens contain at least 256 bits of randomness, are stored only as hashes, expire after 30 minutes, and are single-use.
- Requests are bounded per account.
- Delivery is abstracted through queued authentication messages; production adapters may deliver email without changing recovery semantics.
- Password reset revokes older sessions by incrementing the account session version.

## Login protection

Five consecutive failed attempts cause a neutral fifteen-minute lockout. A successful login clears failed-attempt state. Authentication failures must not expose whether the account, password, or lock state caused the rejection.

## Session contract

Sessions regenerate after authentication. Each authenticated session carries the account session version and is rejected when the durable version changes. Password reset, password change, and account closure therefore invalidate older sessions.

## Audit

Recovery requests for known accounts, successful resets, and password changes create append-only authentication audit records. Raw tokens and passwords are never audited.

## Deferred

Passkeys, social login, multifactor authentication, and external recovery providers remain outside Build 020.