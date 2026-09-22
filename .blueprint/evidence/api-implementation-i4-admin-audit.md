# SENTAI — API Implementation I4 Administration & Audit

**Artifact ID:** EVD-API-IMPL-004  
**Blueprint Phase:** API Implementation  
**Status:** READY_FOR_REVIEW  
**Base:** `19f2c300ac7dc1910b52e15cfc2f76a1300ebf2f`  
**Implementation Validated Head:** `ee93b47cb24a099a1750b9e2e0ec373224467684`

## Scope

I4 implements the seven Administration and Audit operations approved by EVD-API-007.

### Identity administration

- API-ADM-001 `GET /api/v1/admin/users`
- API-ADM-002 `POST /api/v1/admin/users`
- API-ADM-003 `PUT /api/v1/admin/users/{id}`
- API-ADM-004 `PUT /api/v1/admin/users/{id}/disable`

### Role administration

- API-ADM-005 `POST /api/v1/admin/users/{userId}/roles`
- API-ADM-006 `DELETE /api/v1/admin/users/{userId}/roles/{roleId}`

### Global audit

- API-AUDIT-001 `GET /api/v1/audit/events`

No endpoint outside the approved contract is introduced.

## Authorization and Web session contract

All seven operations execute under the backend-managed Web session model established in I2.

Exact capabilities are enforced without wildcard or Administrator bypass:

- API-ADM-001..004 require `admin.identity.manage`;
- API-ADM-005..006 require `admin.roles.manage`;
- API-AUDIT-001 requires `audit.global.read`.

The canonical Administrator role does not implicitly receive global audit access. The I4 feature suite proves that an Administrator without `audit.global.read` receives the canonical authorization denial for `/api/v1/audit/events`, while AuditViewer can query it. Conversely, AuditViewer cannot use Administration endpoints without the corresponding administration capability.

## Identity lifecycle and session revocation

I4 uses the existing Identity persistence model and does not introduce a parallel identity or authorization store.

Security-sensitive changes revoke the target user's active authentication material:

- password change revokes active Web and Mobile sessions/tokens;
- disabling a user revokes active Web and Mobile sessions/tokens;
- assigning a role revokes active Web and Mobile sessions/tokens;
- revoking a role revokes active Web and Mobile sessions/tokens.

Role mutations lock the target user row before changing authorization state, preserving a deterministic database concurrency boundary for the affected identity.

No password, access token, refresh token, cookie value or credential secret is returned by Administration responses or persisted in audit context.

## Idempotency and transactional consistency

The five mutating Administration operations require `Idempotency-Key`:

- API-ADM-002 create user;
- API-ADM-003 update user;
- API-ADM-004 disable user;
- API-ADM-005 assign role;
- API-ADM-006 revoke role.

They reuse the durable shared idempotency gate established in I3:

- same operation + same key + same request identity replays the committed response;
- same operation + same key + different request identity produces `idempotency_conflict`;
- identity/role mutation, required session/token revocation, mandatory audit evidence and stored idempotency result execute inside the same MySQL/InnoDB transaction;
- failure of mandatory audit persistence rolls back the business mutation and does not leave a successful idempotency result behind.

Read-only API-ADM-001 and API-AUDIT-001 do not require persisted business idempotency keys.

## Audit coverage

I4 persists the canonical Administration and authorization events:

- `admin.user.created`
- `admin.user.updated`
- `admin.user.disabled`
- `authz.role.assigned`
- `authz.role.revoked`

When a sensitive Administration action revokes active authentication material, I4 also records the established revocation events as applicable:

- `auth.session.revoked`
- `auth.token.revoked`

The audit evidence records actor, role snapshot, operation, target identity, correlation identifier, source surface and safe context. It does not persist raw credentials or token material.

API-AUDIT-001 records each authorized global audit query through:

- `authz.audit.access`

The query supports the contracted audit scope and pagination while sanitizing forbidden secret-like context keys before returning stored audit data.

## Error and correlation behavior

I4 reuses the cross-cutting API behavior established in I2/I3:

- RFC 9457 `application/problem+json` responses;
- correlation ID propagation;
- authentication and authorization denial;
- validation errors;
- domain conflicts;
- idempotency conflict/replay behavior;
- resource-not-found handling;
- internal-error redaction.

Negative-path exceptions visible in CI logs are expected diagnostic output from explicit rejection/rollback tests and are not leaked by the HTTP Problem Details contract.

## Verification

Exact implementation-head GitHub Actions run: `35670203060`.  
Job: `106564729071`.

Validated implementation head: `ee93b47cb24a099a1750b9e2e0ec373224467684`.

The pull-request workflow validated the synthetic merge of this implementation head into base `19f2c300ac7dc1910b52e15cfc2f76a1300ebf2f`, proving compatibility with the current I3-integrated main baseline.

Result: PASS.

- Composer manifest and lock validation: PASS
- Locked dependency install: PASS
- Native MySQL test database: PASS
- Backend suite: **58 passed / 312 assertions**
- Architecture fitness suite: **34/34 passed**
- Pint: **PASS / 92 files**
- CI token permission: repository contents read-only

I4-specific coverage includes:

- registration of all seven contracted Administration/Audit routes;
- user create/list/update/disable behavior;
- durable idempotent replay and key-reuse conflict detection;
- password secrecy in HTTP and audit surfaces;
- role assignment and revocation;
- session/token revocation after sensitive identity or authorization changes;
- durable `auth.session.revoked` / `auth.token.revoked` evidence without secret material;
- exact Administrator versus AuditViewer authorization isolation;
- global audit filtering and access auditing;
- atomic rollback when mandatory security audit persistence fails;
- regression coverage for I1-I3 Identity, Master Data, RFC 9457, correlation, capability authorization and shared idempotency behavior.

## Blueprint disposition

I4 adds seven implemented Contract IDs to the twenty-one operations already evidenced by I2 and I3. The implementation now covers **28 of 51 active Contract IDs**; **23 remain**.

This is approximately **55%** of the contracted API operation inventory by operation count. It is a progress indicator only, not a claim that 55% of total engineering effort or release readiness is complete.

`api.endpoints_implemented`, `api.auth_authorization`, `api.audit_logging`, `api.backend_tests`, `api.architecture_implementation_conformance` and gate `api_implemented` remain PENDING at project level until the remaining contracted API is implemented and evidenced.

I4 is implementation-safe and ready for human review. It does not claim completion of the API Implementation phase.
