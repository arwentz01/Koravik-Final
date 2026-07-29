# Build 052 — Platform Mail Operations

**Status:** Implemented on `main`  
**Date:** July 29, 2026

## Player-visible outcome

Authorized Owners and Admins have a visual Platform Mail operations home at `/system/mail`. It shows queue health, stale processing claims, recent deliveries, redacted recipient addresses, delivery status, attempts, and bounded delivery details.

## Operations

- Queue a test message to the signed-in operator's account email.
- Retry failed or retrying deliveries.
- Cancel pending, retrying, or failed deliveries without deleting history.
- Create a replacement delivery from a sent, failed, or cancelled delivery while preserving `resend_of_id` lineage.
- Recover processing claims older than fifteen minutes.
- View failure diagnostics with email addresses and IP-style addresses redacted.

## Authorization and safety

- `/system/mail` requires an authenticated `owner` or `admin` role.
- Every state-changing route requires CSRF validation.
- Sent deliveries cannot be cancelled or rewritten.
- Cancellation records the timestamp and acting account.
- Recovery records a recovery timestamp and returns the message to the retry queue.
- Raw message bodies are not displayed by the operations UI.

## Schema

Migration `037_platform_mail_operations.sql`:

- adds the `cancelled` delivery state;
- records cancellation actor and time;
- records stale-claim recovery time;
- adds a stale-processing lookup index.

Apply migrations with:

```bash
php tools/migrate.php
```

## Worker behavior

`tools/mail-worker.php` now recovers stale processing claims before claiming available pending or retry deliveries. The worker remains finite and cron compatible.

## Routes

- `GET /system/mail`
- `GET /system/mail/{deliveryId}`
- `POST /system/mail/test`
- `POST /system/mail/recover`
- `POST /system/mail/{deliveryId}/retry`
- `POST /system/mail/{deliveryId}/cancel`
- `POST /system/mail/{deliveryId}/resend`

## Deferred

Provider webhooks, bounce classification, suppression lists, bulk queue controls, attachment inspection, and outbound-content previews remain future work.
