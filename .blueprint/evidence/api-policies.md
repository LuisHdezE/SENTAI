# SENTAI – API Error Contract & Versioning Policy

**Artifact ID:** EVD-ARCH-API-001
**Blueprint Phase:** Architecture & Security Data (A3)
**Status:** READY_FOR_REVIEW
**Bases en:** EVD-ARCH-001, EVD-ARCH-SEC-001, EVD-ARCH-TXN-001, EVD-REQ-001
**Corrections Applied:** A3-CORRECTION-10 (RFC 9745 / RFC 8594 references), A3-CORRECTION-11 (breaking/non-breaking classification precision)

---

## 1. API Error Contract Architecture

### 1.1 Alignment Standard

All SENTAI API error responses align with **RFC 9457 – Problem Details for HTTP APIs**.

Every structured error response must include the `application/problem+json` content type and the following conceptual fields:

| Field | Description |
|---|---|
| `type` | URI reference identifying the error family (absolute or relative). May be a documentation URI or a stable opaque identifier. |
| `title` | Short, human-readable summary of the problem type. Must not change between occurrences of the same type. |
| `status` | HTTP status code matching the response status. |
| `detail` | Human-readable explanation specific to this occurrence. May vary between occurrences of the same type. |
| `instance` | URI reference identifying the specific occurrence of the problem. |
| `correlation_id` | Correlation identifier from the originating request (see Correlation / Traceability model in EVD-ARCH-AUDIT-001). Mandatory in all error responses. |

Extensions (additional properties beyond RFC 9457 base) are permitted for domain-specific context. Extensions must not leak sensitive data (see Section 1.3).

### 1.2 Error Family Taxonomy

| Family | HTTP Status | Description | Examples |
|---|---|---|---|
| `authentication` | 401 | Request lacks valid authentication credentials or session is expired/revoked | Invalid/expired token, session revoked |
| `authorization` | 403 | Authenticated actor lacks the required capability | Actor without `dispatch.confirm` attempting dispatch |
| `validation` | 422 | Request payload fails structural or format validation | Missing required field, invalid enum value, malformed ID |
| `domain_conflict` | 409 | Request is valid but conflicts with current business state | Order already cancelled, Dispatch already completed |
| `concurrency_conflict` | 409 | Request failed due to concurrent modification by another operation | Optimistic lock failure (if used), stale state detected |
| `idempotency_conflict` | 409 | Idempotency key was reused with a different request payload | Same key, different amount |
| `idempotency_replay` | 200 / 2xx | Idempotency key matches a prior committed operation; replaying result | Duplicate submission; prior result returned |
| `resource_not_found` | 404 | Referenced resource does not exist or is not accessible to the actor | Inventory item ID not found, Order ID not found |
| `invariant_violation` | 422 / 409 | Operation would violate a domain invariant | `Reserved > OnHand` would result, obligation already closed |
| `transient_infrastructure` | 503 / 500 | Temporary infrastructure failure; client may retry with backoff | Database unavailable, timeout |

**Note:** `idempotency_replay` (2xx) is not an error but a successful replay. The response must include the committed result and a header or field identifying it as a replay (e.g., `Idempotency-Replayed: true`).

### 1.3 Information Exposure Policy

| Information | Policy |
|---|---|
| `correlation_id` | MUST be included in every error response |
| Domain entity IDs | MAY be included in `detail` when relevant to the actor's authorized scope |
| Internal stack traces | MUST NOT be exposed in any error response returned to clients |
| Database error messages | MUST NOT be exposed (raw SQL errors, constraint violation messages) |
| Credential material | MUST NOT be included under any circumstances |
| Internal server paths / class names | MUST NOT be exposed |
| Actor enumeration (user existence) | MUST NOT be exposed via authentication errors; responses must be uniform for existing and non-existing accounts |
| Domain business detail | MAY be included when safe (e.g., `"The inventory item is in quarantine state"`) |
| Concurrency / retry guidance | MAY be included for `transient_infrastructure` and `concurrency_conflict` (e.g., `"Retry after: X seconds"`) |

### 1.4 Correlation Identifier in Errors

Every error response must include the `correlation_id` of the originating request. This enables:
- End-to-end request tracing in logs and audit records
- Support teams to correlate client-reported errors with server-side audit/log evidence
- Dalila to audit specific error scenarios

---

## 2. API Versioning Policy

### 2.1 Initial API Version

The initial SENTAI API version family is:

```
/api/v1/
```

All API endpoints will be prefixed with `/api/v1/` unless a future governance decision establishes an exception.

### 2.2 Breaking vs. Non-Breaking Changes (Corrected — A3-CORRECTION-11)

The following classification applies as API contract compatibility principles. These are conservative guidelines, not universal absolutes; edge cases should be evaluated against the published stable contract and the tolerance expectations of known consumers.

| Change Type | Classification | Policy / Notes |
|---|---|---|
| Removing a field from a response | **Breaking** | Requires new version; deprecated field must be maintained until v_old is sunset |
| Renaming a field | **Breaking** | Requires new version |
| Changing a field's type | **Breaking** | Requires new version |
| Removing an endpoint | **Breaking** | Requires new version |
| Changing required fields to optional | Generally **non-breaking** for request compatibility; may affect generated clients or schema-strict consumers. Evaluate against known consumers before deploying in-version. |
| Adding a new optional field to request | **Non-breaking** | May be deployed in-version |
| Adding a new optional field to response | Generally **non-breaking** for tolerant clients. Clients must be designed to ignore unknown response fields (additive evolution). Strict schema validators may require coordination. |
| Adding a new endpoint | **Non-breaking** | May be deployed in-version |
| Changing HTTP status codes for the same semantic outcome | **Breaking** | Requires new version |
| Changing error `type` URIs | **Breaking** if the URI is part of the published stable contract. Changes to unstable or internal URIs not yet contractually published require evaluation. |
| Relaxing validation constraints | **Non-breaking** | May be deployed in-version with care |
| Tightening validation constraints | **Breaking** (for existing clients) | Requires coordination or new version |

### 2.3 Contract Evolution

- Non-breaking changes may be deployed without a version increment.
- Breaking changes require a new version family (e.g., `/api/v2/`).
- Both versions must run concurrently for the duration of the deprecation window.
- A new version is not introduced speculatively; it is introduced when a breaking change is actually required.

### 2.4 Deprecation Policy (Corrected — A3-CORRECTION-10)

When a version is to be deprecated:
1. Announce deprecation with the target sunset date to all active API consumers.
2. Include a `Deprecation` header (per **RFC 9745** — The Deprecation HTTP Header Field) in responses from the deprecated version.
3. Include a `Sunset` header (per **RFC 8594** — The Sunset HTTP Header Field) to indicate the date after which the deprecated version may no longer be available.
4. Maintain the deprecated version for a deprecation window (exact duration is a product/governance decision; not fabricated here).
5. After the sunset date, the deprecated version may be removed.

Exact rollout behavior and header values remain governed by API Contract and Operations decisions.

### 2.5 Compatibility Expectations

- Clients must be able to tolerate new optional response fields without breaking (additive response evolution is non-breaking).
- Clients must not hardcode assumption that no new fields will be added.
- SENTAI API is not a public API; consumers are internal surfaces (Backoffice, Customer Portal, Mobile). Coordination for breaking changes is feasible within the project.

### 2.6 Version Ownership

- A version is owned by the engineering team responsible for SENTAI backend.
- Version introduction requires an ADR or documented governance decision.
- The version lifecycle (introduction, deprecation, sunset) must be tracked in project documentation.
- API Contract Design (the next phase, not yet started) will formalize the `/api/v1/` endpoint catalog.

---

## 3. Preservation of A1/A2 Decisions

This document does not alter:
- Module boundaries, aggregate candidates, or critical invariants from EVD-ARCH-001.
- Security model constraints from EVD-ARCH-SEC-001.
- `redis=false`, `mobile_licensing=false`, `saas=false`, `multi_tenant=false`.
- UNRES-001 through UNRES-005 remain unresolved.

---

## Traceability

| Requirement / Decision | API Policy Mapping |
|:---|:---|
| NFR-001 | Error information exposure policy (Section 1.3); no stack traces, no credentials in responses |
| NFR-005 | Idempotency replay family (`idempotency_replay`) in error taxonomy |
| NFR-012 | Correlation ID mandatory in all error responses |
| FR-022 | Authentication/authorization error families |
| EVD-ARCH-TXN-001 | Concurrency conflict and transient infrastructure error families |
| EVD-ARCH-AUDIT-001 | Correlation ID links error responses to audit events |
| RFC 9457 | Canonical error response structure |
| RFC 9745 | `Deprecation` header standard (A3-CORRECTION-10) |
| RFC 8594 | `Sunset` header standard (A3-CORRECTION-10) |
| A3-CORRECTION-11 | Breaking/non-breaking classification as compatibility principles, not universal absolutes |
