# SENTAI — API Implementation I3 Master Data

**Artifact ID:** EVD-API-IMPL-003  
**Blueprint Phase:** API Implementation  
**Status:** READY_FOR_REVIEW  
**Base:** `ed4ea0de2f7edf98ddd36066a683e9bae2074c94`  
**Implementation Validated Head:** `a70bf666dd65978c6b2c1909c16e05a033520086`

## Scope

I3 implements the fifteen contracted Master Data operations approved by EVD-API-007.

### Products

- API-MAST-001 `GET /api/v1/products`
- API-MAST-002 `POST /api/v1/products`
- API-MAST-003 `PUT /api/v1/products/{id}`

### Customers

- API-MAST-005 `GET /api/v1/customers`
- API-MAST-006 `POST /api/v1/customers`
- API-MAST-007 `PUT /api/v1/customers/{id}`

### Warehouses

- API-MAST-012 `GET /api/v1/warehouses`
- API-MAST-013 `POST /api/v1/warehouses`
- API-MAST-014 `PUT /api/v1/warehouses/{id}`

### Zones

- API-MAST-015 `GET /api/v1/zones`
- API-MAST-016 `POST /api/v1/zones`
- API-MAST-017 `PUT /api/v1/zones/{id}`

### Locations

- API-MAST-018 `GET /api/v1/locations`
- API-MAST-019 `POST /api/v1/locations`
- API-MAST-020 `PUT /api/v1/locations/{id}`

No delete operation is introduced. No endpoint outside the approved contract is added.

The Customer master operations in this slice are Master Data operations authorized by `admin.masters.maintain`; they do **not** implement or redefine the separately deferred `commercial.customer.maintain` capability surface.

## Contract-safe data profile

I3 deliberately uses the minimum v1 master-data profile supported by current Architecture and API Contract Truth:

- Product: `id`, `code`, `name`, `is_active`
- Customer: `id`, `code`, `name`, `is_active`
- Warehouse: `id`, `code`, `name`, `is_active`
- Zone: `id`, `code`, `name`, `is_active`, `warehouse_id`
- Location: `id`, `code`, `name`, `is_active`, `warehouse_id`, `zone_id`

The implementation does not invent product dimensions, classifications, customer commercial conditions, financial limits, warehouse attributes or other fields that are not yet established by Product/Architecture Truth.

## Authorization and Web session contract

All fifteen routes execute under the backend-managed Web session model established in I2 and require the exact canonical capability:

- `admin.masters.maintain`

There is no Administrator bypass, wildcard permission or alternate implicit authorization path.

The I3 test fixture was reconciled with the production session contract by providing the same `sentai.authenticated_at` marker established by real Web login. Production middleware was not weakened to accommodate tests.

## Idempotency and transactional consistency

The ten mutating Master Data operations require `Idempotency-Key`.

I3 introduces a durable reusable database idempotency gate with these semantics:

- request identity is scoped by contracted operation plus idempotency key;
- the request payload is canonicalized and hashed before execution;
- same operation + same key + same request identity replays the committed response;
- same operation + same key + different request identity produces `idempotency_conflict`;
- the idempotency decision, Master Data mutation, mandatory audit event and stored result execute inside the same MySQL/InnoDB transaction;
- a failed mandatory audit write rolls back the business mutation and does not leave a successful idempotency result behind.

Read-only list operations do not require idempotency keys.

## Domain and persistence constraints

I3 enforces the structural invariants required for the v1 master model:

- master codes are unique in their required scope;
- a Zone references an existing Warehouse;
- a Location references an existing Warehouse and Zone;
- the referenced Zone must belong to the referenced Warehouse;
- update of a missing master record returns the canonical resource-not-found family;
- structural or uniqueness violations use the canonical domain-conflict family.

Master Data and idempotency persistence use versioned MySQL/InnoDB migrations. No Redis, Docker or alternate authoritative data store is introduced.

## Audit coverage

Every successful create/update mutation emits the canonical:

- `admin.masters.changed`

The audit event records the operation context, affected master identity, actor, correlation identifier and changed-field names without introducing secret or credential material.

The mandatory audit write is transactionally coupled to the Master Data mutation. The feature suite includes a synthetic audit failure proving rollback of the business mutation.

## Error and correlation behavior

I3 reuses the cross-cutting API behavior established in I2:

- RFC 9457 `application/problem+json` responses;
- correlation ID propagation;
- authorization denial;
- validation error;
- domain conflict;
- idempotency conflict;
- resource not found;
- internal-error redaction.

Negative-path exceptions visible in the CI test runner are expected diagnostic output and are not leaked by the HTTP problem response contract.

## Verification

Exact implementation-head GitHub Actions run: `35644084277`.

Validated implementation head: `a70bf666dd65978c6b2c1909c16e05a033520086`.

Result: PASS.

- Composer manifest and lock validation: PASS
- Locked dependency install: PASS
- Native MySQL test database: PASS
- Backend suite: **52 passed / 230 assertions**
- Architecture fitness suite: **34/34 passed**
- Pint: **PASS / 79 files**

I3-specific coverage includes:

- registration of all fifteen contracted Master Data routes;
- Product and Customer create/list/update behavior;
- Warehouse → Zone → Location hierarchy behavior;
- duplicate-code and cross-Warehouse Zone/Location rejection;
- exact `admin.masters.maintain` capability enforcement;
- mandatory `Idempotency-Key` on mutations;
- idempotent replay for identical requests;
- `idempotency_conflict` for key reuse with different request identity;
- durable audit emission;
- atomic rollback when mandatory audit persistence fails;
- regression coverage for I1/I2 Identity, RFC 9457, correlation and authorization behavior.

## Blueprint disposition

I3 adds fifteen implemented contract operations to the six authentication operations from I2. The implementation now covers **21 of 51 active Contract IDs**; **30 remain**.

This is approximately **41%** of the contracted API operation inventory by operation count. It is a progress indicator only, not a claim that 41% of total engineering effort or release readiness is complete.

`api.endpoints_implemented`, `api.auth_authorization`, `api.audit_logging`, `api.backend_tests`, `api.architecture_implementation_conformance` and gate `api_implemented` remain PENDING at project level until the remaining contracted API is implemented and evidenced.

I3 is implementation-safe and ready for human review. It does not claim completion of the API Implementation phase.
