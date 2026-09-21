# SENTAI — API Implementation I2 Identity & Cross-Cutting Foundation

**Artifact ID:** EVD-API-IMPL-002  
**Blueprint Phase:** API Implementation  
**Status:** READY_FOR_REVIEW  
**Base:** `04a3d7b44b6ec9eaff2ad54c8065e1d472491a3b`  
**Validated Head:** `18b5e739f31ec9eb3d3cce808b5081b50c4b8f33`

## Scope

I2 implements the authentication and authorization foundation required by the approved API contract before business modules are exposed.

The increment implements exactly the six contracted authentication operations:

- API-AUTH-001 `POST /api/v1/auth/web/login`
- API-AUTH-002 `POST /api/v1/auth/web/logout`
- API-AUTH-003 `GET /api/v1/auth/web/csrf`
- API-AUTH-004 `POST /api/v1/auth/mobile/login`
- API-AUTH-005 `POST /api/v1/auth/mobile/refresh`
- API-AUTH-006 `POST /api/v1/auth/mobile/logout`

No Inventory, Orders, Fulfillment, Shipping, Finance, Master Data, Admin or Sync business operation is introduced by I2.

## Contract conformance

### Web authentication

- Backend-managed sessions persisted in MySQL.
- Login regenerates the backend session identifier.
- Browser session identifier remains cookie-only.
- Session cookie configuration is HttpOnly and Secure.
- CSRF protection remains active for state-changing browser requests.
- `GET /api/v1/auth/web/csrf` provides the browser CSRF token.
- Web sessions support server-side invalidation and an absolute lifetime in addition to Laravel idle expiry.
- Disabled users are rejected on the next authenticated request.

### Mobile authentication

- Access and refresh material are opaque credentials, not JWTs.
- Only SHA-256 hashes of mobile credentials are persisted server-side.
- Access credentials are short lived.
- Refresh sessions rotate on successful refresh.
- Previous refresh material is invalid after rotation.
- Logout revokes the mobile session so subsequent credentials are rejected.
- Mobile login is restricted to Warehouse Operator according to the approved mobile actor boundary.

### Authorization

- The canonical 23 capabilities, including `fulfillment.allocate`, are seeded exactly.
- Canonical security roles and role-capability mappings are persisted.
- Authorization is re-evaluated against server-side state.
- Administrator has no implicit capability bypass.
- `audit.global.read` remains independently assigned to AuditViewer.
- Significant authorization denial emits the canonical security audit event.

### Cross-cutting API behavior

- Every request receives an `X-Correlation-ID` response header.
- Valid incoming correlation IDs are preserved; invalid values are replaced server-side.
- API errors use RFC 9457 `application/problem+json` responses.
- Authentication, authorization, validation and internal error families carry `correlation_id`.
- HTTP error responses do not expose stack traces, SQL diagnostics, credentials, tokens, filesystem paths or internal class names.
- Negative-path application exceptions may appear in test runner logs; this is diagnostic logging and is not part of the HTTP response contract.

## Persistence introduced

Versioned MySQL/InnoDB migrations add persistence for:

- users
- roles
- permissions/capabilities
- user-role and role-permission mappings
- backend Web sessions
- mobile session/access/refresh material
- append-only audit events

No Redis or Docker dependency is introduced.

## Audit coverage

I2 implements the approved authentication/security audit events applicable to this slice, including:

- `auth.login.success`
- `auth.login.failure`
- `auth.logout`
- `auth.session.revoked`
- `auth.token.revoked`
- significant authorization denial

Credential and token secrets are not written into audit payloads.

## Verification

Exact-head GitHub Actions run: `35629830002`.

Result: PASS.

- Composer manifest and lock validation: PASS
- Locked dependency install: PASS
- Native MySQL test database: PASS
- Backend suite: **47 passed / 149 assertions**
- Architecture fitness suite: **34/34 passed**
- Pint: PASS
- PHPUnit warning gate remains enabled

Test coverage includes:

- Web CSRF/login/logout session contract
- indistinguishable wrong-password vs unknown-account authentication response
- disabled-user revalidation
- mobile login / refresh rotation / logout revocation
- mobile actor restriction
- exact capability enforcement without Administrator bypass
- identity schema and canonical authorization seeding
- correlation ID generation/preservation/replacement
- RFC 9457 validation responses
- internal-error response redaction

## Blueprint disposition

I2 materially advances `api.auth_authorization`, `api.audit_logging`, `api.backend_tests` and `api.architecture_implementation_conformance`, but none of those project-level checks is declared final PASS yet because the remaining 45 contracted operations are not implemented.

`api.endpoints_implemented` and gate `api_implemented` remain PENDING.

I2 is implementation-safe and ready for human review; it does not claim completion of the API Implementation phase.
