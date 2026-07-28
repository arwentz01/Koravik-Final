# API Specification

**Status:** Approved for Blueprint v1.0  
**Version:** 1.0  
**Owner:** Platform Architecture

## Purpose

Koravik is a server-rendered custom PHP application first. APIs exist to support approved product interactions, progressive enhancement, integrations, and future clients. They are not a parallel product architecture.

## Core rules

- HTTP entry points remain thin and delegate to application services.
- Domain rules do not live in controllers, routes, or JavaScript.
- Every write requires authentication, authorization, validation, CSRF protection where browser sessions apply, and an explicit transaction boundary.
- APIs expose module-owned contracts, never direct table access.
- Responses minimize personal and sensitive data.
- Errors are safe for users and sufficiently traceable for operators.

## URL and versioning

Browser routes use stable product-oriented paths. Machine-facing endpoints use `/api/v1/...` when a public or durable contract is required. Internal progressive-enhancement endpoints may remain unversioned only when they are not treated as external contracts.

Breaking changes require a new major API version or a compatible transition period. Additive optional fields do not require a new major version.

## Request conventions

- JSON APIs use `application/json`.
- Dates and timestamps use ISO 8601; persisted timestamps are UTC.
- Identifiers are opaque strings.
- Unknown fields are rejected for consequential writes unless a contract explicitly permits extension data.
- Pagination uses bounded page sizes and stable ordering.
- Idempotency keys are required for retry-prone consequential operations where duplicate execution would be harmful.

## Response conventions

Successful JSON responses use a stable envelope where useful:

```json
{
  "data": {},
  "meta": {},
  "links": {}
}
```

Errors use:

```json
{
  "error": {
    "code": "stable.machine_code",
    "message": "Human-readable explanation",
    "field_errors": {}
  },
  "trace_id": "opaque-id"
}
```

The response must not expose stack traces, SQL, secrets, internal paths, or private authorization details.

## Authentication and sessions

The initial web application uses secure cookie-based sessions. Session cookies must be HttpOnly, Secure in production, and use an appropriate SameSite policy. Login, logout, session rotation, password reset, and sensitive account changes require audit events.

Token-based authentication is deferred until an approved integration or client requires it. Tokens must be scoped, revocable, expiring, hashed at rest where applicable, and never embedded in URLs.

## Authorization

Every request is authorized at the application-service boundary using capabilities, ownership, visibility, group context, consent, and resource state. Hiding a control in the interface is not authorization.

## Browser interaction

Server-rendered HTML is the baseline. JavaScript may enhance forms, navigation, notifications, and panels, but core journeys remain usable when enhancement fails. Form submissions follow Post/Redirect/Get where appropriate.

## Rate limits and abuse controls

Authentication, recovery, invitations, public submissions, search, exports, and integration endpoints require bounded rate limits. Limits should fail clearly without disclosing defensive thresholds.

## File handling

Uploads require allow-listed types, size limits, content verification, generated storage names, authorization checks, and delivery outside executable paths. User-provided filenames are presentation metadata only.

## Integrations

External providers are isolated behind adapters. Provider payloads are normalized before entering domain modules. Webhook support, when added, requires signature verification, replay protection, idempotency, and durable processing.

## Documentation and testing

Every durable endpoint records purpose, actor, authorization, request, response, errors, privacy classification, idempotency behavior, and tests. No endpoint is complete without negative authorization and validation coverage.