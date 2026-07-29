# Builds 042–046 — Gather Operational Core

## Build 042 — Platform Mail Delivery

Implemented a Platform-owned durable mail queue, authenticated SMTP adapter, finite cron-compatible worker, retry backoff, terminal failure handling, multipart HTML/text messages, Reply-To support, and provider references. Gather remains unaware of SMTP credentials.

Required production environment variables:

```dotenv
MAIL_HOST=mail.koravik.com
MAIL_PORT=465
MAIL_ENCRYPTION=ssl
MAIL_USERNAME=notifications@koravik.com
MAIL_PASSWORD=
MAIL_FROM_ADDRESS=notifications@koravik.com
MAIL_FROM_NAME=Koravik
```

Cron example:

```cron
*/5 * * * * /usr/local/bin/php /path/to/current/tools/mail-worker.php 20 >/dev/null 2>&1
```

## Build 043 — Restricted Gather Access

Implemented event access grants for invited email addresses, accounts, friends, Organizations, and Households. Public and unlisted events remain link-accessible. Restricted events require an explicit matching grant or organizer ownership. Friend, Organization, and Household membership resolution is intentionally delegated to their owning domains when those services are implemented.

## Build 044 — Guest RSVP Self-Service

Implemented hashed, expiring management-token lookup, RSVP response and party-size updates, named additional guest replacement, RSVP cancellation, signup release, and automatic waitlist reconsideration after cancellation. No guest account is silently created.

## Build 045 — Waitlist Promotion

Implemented party-size-aware event waitlist selection, expiring promotion offers, promotion email queueing, and independent signup waitlist states. Promotion does not silently confirm attendance; the participant must accept through the RSVP management experience.

## Build 046 — Signup Rules and Conflict Prevention

Implemented event-level signup limits, slot-level quantity limits, attendance requirements, multiple-signup controls, full-slot waitlisting, and overlapping volunteer-shift detection. Organizer overrides remain explicit future UI actions rather than hidden bypasses.

## Ownership boundary

- Platform Mail owns message delivery and transport credentials.
- Gather owns event access, participant records, RSVP state, waitlist state, signup slots, and commitments.
- Beacon may distribute Gather links but cannot weaken Gather restrictions.
- Account, Friend, Organization, and Household domains own their membership truth.

## Deployment steps

1. Apply migrations with `php tools/migrate.php`.
2. Add production mail variables outside source control.
3. Run `php tools/mail-worker.php 1` from the command line after queueing a test message.
4. Configure the recurring cron worker.
5. Confirm SPF, DKIM, and DMARC for the sending domain.
6. Confirm GitHub Actions before treating the arc as release-ready.
