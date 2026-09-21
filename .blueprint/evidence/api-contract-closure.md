# SENTAI — Consolidated API Contract Closure

**Artifact ID:** EVD-API-007  
**Blueprint Phase:** API Contract Design  
**Status:** READY_FOR_REVIEW  
**Type:** api_contract_closure_evidence  
**Effective Architecture Addendum:** ADR-006  
**Base:** EVD-API-002..006

## 1. Purpose and precedence

This artifact closes the initial `/api/v1/` contract as one implementation-safe contract. It reconciles C1, C2 and C3 without rewriting their historical evidence.

Where EVD-API-002..006 contain `TBD`, `GAP`, `UNRESOLVED_CONTRACT_GAP`, or a statement explicitly superseded by ADR-006, this document is the effective final disposition for initial v1. All unaffected C1-C3 statements remain valid.

This is a contract, not Laravel implementation. It does not prescribe framework classes, database table names, middleware classes, Redis, Docker, service accounts, or client implementation details.

## 2. Closure summary

- Active HTTP Contract IDs: **51**.
- Permission coverage: **51/51 RESOLVED**.
- Audit mapping: **51/51 RESOLVED**.
  - CANONICAL_EVENT: **36** Contract IDs.
  - NO_SEPARATE_AUDIT_EVENT_REQUIRED: **15** Contract IDs.
  - AUDIT_CATALOG_GAP: **0**.
- Endpoint inventory: **RESOLVED for initial v1**.
- Authentication contract: preserved from EVD-API-003.
- Idempotency contract: explicit below.
- Contract traceability: explicit below.
- API base path: `/api/v1/`.
- Errors: RFC 9457 Problem Details from EVD-ARCH-API-001.
- Correlation ID: mandatory on all requests/error responses and propagated to audit.

## 3. Effective operation register

Legend: `REQ` = persisted business idempotency required. `NO` = no persisted business-idempotency key required. `TX` = explicit business transaction required. `-` = no business transaction.

| Contract ID | operationId | Method / Path | Actor / Auth | Capability | Idem | Audit | TX | Request intent | Success intent |
|---|---|---|---|---|---|---|---|---|---|
| API-AUTH-001 | `webLogin` | POST `/api/v1/auth/web/login` | All / pre-auth | N/A | NO | success/failure login event | - | credentials required to authenticate; no account-enumeration hints | 204 and backend-managed session established |
| API-AUTH-002 | `webLogout` | POST `/api/v1/auth/web/logout` | authenticated Web user | N/A | NO | `auth.logout` | - | current session only | current backend session invalidated; cookies purged |
| API-AUTH-003 | `getWebCsrf` | GET `/api/v1/auth/web/csrf` | pre-auth/session bootstrap | N/A | NO | none | - | no business payload | CSRF material prepared/provided |
| API-AUTH-004 | `mobileLogin` | POST `/api/v1/auth/mobile/login` | ACT-002 / pre-auth | N/A | NO | success/failure login event | - | mobile credentials | short-lived access credential + rotating refresh material |
| API-AUTH-005 | `mobileRefresh` | POST `/api/v1/auth/mobile/refresh` | ACT-002 / refresh credential | N/A | NO | none | - | valid refresh-session material | rotated access + refresh pair; prior refresh invalidated |
| API-AUTH-006 | `mobileLogout` | POST `/api/v1/auth/mobile/logout` | ACT-002 / mobile access | N/A | NO | `auth.logout` | - | current mobile session context | refresh session invalidated; subsequent credentials rejected per revocation policy |
| API-MAST-001 | `listProducts` | GET `/api/v1/products` | ACT-007 / Web | `admin.masters.maintain` | NO | none | - | pagination/filter intent | product master projection |
| API-MAST-002 | `createProduct` | POST `/api/v1/products` | ACT-007 / Web | `admin.masters.maintain` | REQ | `admin.masters.changed` | TX | product master attributes; server owns id/audit metadata | created product |
| API-MAST-003 | `updateProduct` | PUT `/api/v1/products/{id}` | ACT-007 / Web | `admin.masters.maintain` | REQ | `admin.masters.changed` | TX | mutable product master attributes for target id | updated product |
| API-MAST-005 | `listCustomers` | GET `/api/v1/customers` | ACT-007 / Web | `admin.masters.maintain` | NO | none | - | pagination/filter intent | customer master projection |
| API-MAST-006 | `createCustomer` | POST `/api/v1/customers` | ACT-007 / Web | `admin.masters.maintain` | REQ | `admin.masters.changed` | TX | customer master attributes defined by Master Data domain; not commercial-condition maintenance | created customer |
| API-MAST-007 | `updateCustomer` | PUT `/api/v1/customers/{id}` | ACT-007 / Web | `admin.masters.maintain` | REQ | `admin.masters.changed` | TX | mutable customer master attributes; excludes undefined commercial-maintenance semantics | updated customer |
| API-MAST-012 | `listWarehouses` | GET `/api/v1/warehouses` | ACT-007 / Web | `admin.masters.maintain` | NO | none | - | pagination/filter intent | warehouse list |
| API-MAST-013 | `createWarehouse` | POST `/api/v1/warehouses` | ACT-007 / Web | `admin.masters.maintain` | REQ | `admin.masters.changed` | TX | warehouse master attributes | created warehouse |
| API-MAST-014 | `updateWarehouse` | PUT `/api/v1/warehouses/{id}` | ACT-007 / Web | `admin.masters.maintain` | REQ | `admin.masters.changed` | TX | mutable warehouse attributes | updated warehouse |
| API-MAST-015 | `listZones` | GET `/api/v1/zones` | ACT-007 / Web | `admin.masters.maintain` | NO | none | - | pagination/filter intent | zone list |
| API-MAST-016 | `createZone` | POST `/api/v1/zones` | ACT-007 / Web | `admin.masters.maintain` | REQ | `admin.masters.changed` | TX | zone master attributes and warehouse relation | created zone |
| API-MAST-017 | `updateZone` | PUT `/api/v1/zones/{id}` | ACT-007 / Web | `admin.masters.maintain` | REQ | `admin.masters.changed` | TX | mutable zone attributes | updated zone |
| API-MAST-018 | `listLocations` | GET `/api/v1/locations` | ACT-007 / Web | `admin.masters.maintain` | NO | none | - | pagination/filter intent | physical-location list |
| API-MAST-019 | `createLocation` | POST `/api/v1/locations` | ACT-007 / Web | `admin.masters.maintain` | REQ | `admin.masters.changed` | TX | location master attributes and containing zone/warehouse reference | created location |
| API-MAST-020 | `updateLocation` | PUT `/api/v1/locations/{id}` | ACT-007 / Web | `admin.masters.maintain` | REQ | `admin.masters.changed` | TX | mutable location attributes | updated location |
| API-ADM-001 | `listAdminUsers` | GET `/api/v1/admin/users` | ACT-007 / Web | `admin.identity.manage` | NO | none | - | pagination/filter intent | authorized user administration projection |
| API-ADM-002 | `createAdminUser` | POST `/api/v1/admin/users` | ACT-007 / Web | `admin.identity.manage` | REQ | `admin.user.created` | TX | identity/account creation attributes; secrets never echoed | created user |
| API-ADM-003 | `updateAdminUser` | PUT `/api/v1/admin/users/{id}` | ACT-007 / Web | `admin.identity.manage` | REQ | `admin.user.updated` | TX | mutable identity/account profile attributes | updated user |
| API-ADM-004 | `disableAdminUser` | PUT `/api/v1/admin/users/{id}/disable` | ACT-007 / Web | `admin.identity.manage` | REQ | `admin.user.disabled` | TX | target id plus disable intent/reason when required | user disabled; relevant sessions revocable per security model |
| API-ADM-005 | `assignUserRole` | POST `/api/v1/admin/users/{userId}/roles` | ACT-007 / Web | `admin.roles.manage` | REQ | `authz.role.assigned` | TX | role identifier to assign | assignment persisted |
| API-ADM-006 | `revokeUserRole` | DELETE `/api/v1/admin/users/{userId}/roles/{roleId}` | ACT-007 / Web | `admin.roles.manage` | REQ | `authz.role.revoked` | TX | target user/role from path | assignment revoked |
| API-AUDIT-001 | `listAuditEvents` | GET `/api/v1/audit/events` | AuditViewer / Web | `audit.global.read` | NO | `authz.audit.access` | - | time/event/actor/resource filters + pagination; query scope audited | global audit projection excluding forbidden secret content |
| API-INV-001 | `receiveAsn` | POST `/api/v1/reception/asn/{id}/receive` | ACT-002 / Mobile | `warehouse.receive` | REQ | `inventory.asn.received` | TX | ASN line references, actually received quantities and discrepancy facts | receipt committed into reception state; invalid ASN rejected |
| API-INV-002 | `confirmPutAway` | POST `/api/v1/inventory/put-away` | ACT-002 / Mobile | `warehouse.putaway` | REQ | `inventory.putaway.completed` | TX | receipt/inventory references, quantities and destination location | stock associated with destination location |
| API-INV-003 | `listInventory` | GET `/api/v1/inventory` | ACT-003 / Web | `inventory.read` | NO | none | - | pagination plus product/location/lot/serial/state filters | InventoryItem projection including OnHand, Reserved and eligible availability |
| API-INV-004 | `adjustInventory` | POST `/api/v1/inventory/adjust` | ACT-003 / Web | `inventory.adjust` | REQ | `inventory.adjustment.applied` | TX | inventory item reference, quantity/state adjustment and mandatory reason | committed adjusted inventory satisfying invariants |
| API-INV-005 | `moveInventory` | POST `/api/v1/inventory/move` | ACT-003 / Web | `inventory.adjust` | REQ | `inventory.move.completed` | TX | source inventory reference, destination location, quantity and reason | logical inventory relocation with no duplicate physical quantity |
| API-INV-006 | `blockLocation` | POST `/api/v1/locations/{id}/block` | ACT-003 / Web | `inventory.location.block` | REQ | `inventory.location.blocked` | TX | target location and reason | location blocked; affected stock excluded from standard eligibility |
| API-INV-007 | `unblockLocation` | POST `/api/v1/locations/{id}/unblock` | ACT-003 / Web | `inventory.location.block` | REQ | `inventory.location.unblocked` | TX | target location and reason | location unblocked subject to domain validation |
| API-ORD-001 | `listCatalog` | GET `/api/v1/catalog` | ACT-001 / Web | `catalog.read` | NO | none | - | catalog pagination/filter intent | products eligible for customer ordering |
| API-ORD-002 | `createOrder` | POST `/api/v1/orders` | ACT-001 / Web | `customer.order.create` | REQ | `order.created` | TX | product references and requested quantities; customer identity is forced from authenticated session | own order created pending commercial validation |
| API-ORD-003 | `confirmOrder` | POST `/api/v1/orders/{id}/confirm` | ACT-005 / Web | `commercial.order.approve` | REQ | `order.confirmed` | TX | target order; server validates current state and BR-009 credit conditions | order confirmed for logistics or structured domain conflict |
| API-FUL-001 | `allocateOrderInventory` | POST `/api/v1/orders/{id}/allocate` | ACT-003 / Web | `fulfillment.allocate` | REQ | `fulfillment.allocation.created` | TX | target confirmed order; server selects eligible stock under BR-001..003 | allocation/reservations created; OnHand unchanged |
| API-FUL-002 | `confirmPicking` | POST `/api/v1/fulfillment/picking/confirm` | ACT-002 / Mobile | `warehouse.picking` | REQ | `fulfillment.picking.completed` | TX | picking task/line references and confirmed picked quantities | picking state committed and linked to order; OnHand unchanged |
| API-FUL-003 | `confirmPacking` | POST `/api/v1/fulfillment/packing/confirm` | ACT-002 / Mobile | `warehouse.packing` | REQ | `fulfillment.packing.completed` | TX | picked work/order references plus package identifiers/contents | identifiable packages created/confirmed; OnHand unchanged |
| API-SHP-001 | `createShippingPlan` | POST `/api/v1/shipping/plans` | ACT-004 / Web | `dispatch.plan` | REQ | `shipping.plan.created` | TX | eligible packed order/package references and planning attributes | consolidated shipping plan created without conflicting assignments |
| API-SHP-002 | `confirmDispatch` | POST `/api/v1/shipping/dispatch/confirm` | ACT-004 / Web | `dispatch.confirm` | REQ | `dispatch.confirmed` + conditional `finance.obligation.created` | TX | dispatch reference/confirmation; server derives inventory/reservation/finance effects | atomic physical exit, OnHand decrement, reservation reconciliation, conditional obligation, Completed |
| API-FIN-001 | `listReceivables` | GET `/api/v1/finance/receivables` | ACT-006 / Web | `finance.receivables.read` | NO | none | - | customer/status/aging filters + pagination | AR view of Financial Obligations |
| API-FIN-002 | `registerPayment` | POST `/api/v1/finance/payments` | ACT-006 / Web | `finance.payment.register` | REQ | `finance.payment.registered` | TX | customer, amount/currency, received date and external/reference facts | payment registered as available/unapplied balance |
| API-FIN-003 | `applyPayment` | POST `/api/v1/finance/payments/{id}/apply` | ACT-006 / Web | `finance.payment.apply` | REQ | `finance.payment.applied` + conditional `finance.obligation.closed` | TX | payment id plus obligation references and amounts to apply | obligations reduced/closed; unapplied remainder allowed |
| API-FIN-004 | `listPayables` | GET `/api/v1/finance/payables` | ACT-001 / Web | `customer.finance.read` | NO | none | - | own obligation filters/pagination | own AP projection of same Financial Obligations |
| API-FIN-005 | `getCustomerStatement` | GET `/api/v1/finance/statements/{customerId}` | ACT-006 / Web | `finance.receivables.read` | NO | none | - | target customer and period/filter intent | consolidated obligation/payment statement |
| API-FIN-006 | `getOwnStatement` | GET `/api/v1/finance/statement` | ACT-001 / Web | `customer.finance.read` | NO | none | - | period/filter intent; customer forced from session | own consolidated statement only |
| API-MOB-001 | `listMobileWorkItems` | GET `/api/v1/mobile/work-items` | ACT-002 / Mobile | item-filtered `warehouse.receive` / `warehouse.putaway` / `warehouse.picking` / `warehouse.packing` | NO | none | - | optional cursor/filter for downloadable work; no mutation | server-authoritative work-item envelopes filtered by current per-item capability |
| API-SYNC-001 | `syncOfflineOperations` | POST `/api/v1/sync` | ACT-002 / Mobile | capability of each original operation | REQ per operation | outcome-dependent sync events | TX per applied operation | operation envelopes carrying original operation kind, stable idempotency key, correlation id and original payload | per-operation applied/rejected/conflict/auth outcome; no batch-global atomicity |

## 4. Authentication and authorization closure

### 4.1 Web

Backoffice and Customer Portal use backend-managed Web sessions with secure HttpOnly cookies, Secure transport, SameSite policy appropriate to deployment and CSRF protection for browser state-changing requests. No auth token/session secret is stored in `localStorage`.

### 4.2 Mobile

Mobile uses short-lived access credentials plus rotating refresh sessions in secure platform storage. Sessions are server-revocable.

### 4.3 Customer ownership

`API-ORD-002`, `API-FIN-004`, and `API-FIN-006` derive CustomerID from the authenticated customer context. Client-supplied identifiers MUST NOT widen ownership. Future customer-order read/tracking remains deferred and MUST enforce OWN data when introduced.

### 4.4 Allocation

ADR-006 adds `fulfillment.allocate` and assigns it to ACT-003. `API-FUL-001` is **51/51 permission-resolved** as an authenticated Backoffice operation. Initial v1 has no System HTTP identity or automatic allocation trigger.

### 4.5 Mobile work items

`API-MOB-001` creates no broad `work.read` capability. Authorization is item-filtered from the four existing warehouse capabilities. Download is not authority to commit; sync revalidates current authorization.

## 5. Idempotency matrix and behavior

### 5.1 HTTP binding

Every `REQ` operation requires a stable caller-supplied `Idempotency-Key` for the same logical operation. The server persists the idempotency identity and associated committed result atomically with the business transaction as required by EVD-ARCH-TXN-001.

The identity scope includes operation type plus caller/business context. The same opaque key may not be reused for a different logical request in the same scope.

### 5.2 Required operations

`REQ` applies to all business mutation Contract IDs in the operation register except the authentication/session lifecycle commands explicitly marked `NO`.

Covered groups:

- Master Data create/update: API-MAST-002,003,006,007,013,014,016,017,019,020.
- Identity/Roles mutation: API-ADM-002..006.
- Inventory mutation: API-INV-001,002,004..007.
- Orders/Fulfillment: API-ORD-002,003; API-FUL-001..003.
- Shipping: API-SHP-001,002.
- Finance: API-FIN-002,003.
- Offline sync: API-SYNC-001, **per submitted original operation**.

### 5.3 Not required

Persisted business idempotency is not required for GET queries or AUTH-001..006 session lifecycle commands as marked `NO`. Authentication/session endpoints remain subject to their own security replay/rotation rules.

### 5.4 Outcomes

| Scenario | Contract behavior |
|---|---|
| Same key + same logical request + committed result | Return prior committed result as successful `idempotency_replay` without re-executing business effects. |
| Same key + different logical request/payload | `409` RFC 9457 `idempotency_conflict`. |
| Concurrent duplicate while first execution has not committed | At most one may commit. The other returns `409` `idempotency_conflict` indicating the logical operation is already in progress/contended and may be retried safely. |
| No prior identity | Execute once and persist business result + idempotency record in the same transaction. |
| Deadlock/lock timeout | Roll back whole transaction and perform bounded whole-transaction retry only for retry-safe idempotent operations. |
| Domain conflict | Do not blindly retry; return the appropriate domain/invariant conflict. |

A replay MUST NOT repeat inventory quantity changes, reservation changes, dispatch effects, financial entries, payment applications, audit mutation events, or any other business side effect.

## 6. Audit closure

ADR-006 extends the canonical catalog with:

- `inventory.move.completed`
- `order.created`
- `order.confirmed`
- `fulfillment.picking.completed`
- `fulfillment.packing.completed`
- `shipping.plan.created`

All six are written in the same transaction as the successful business mutation. This resolves every C3 `AUDIT_CATALOG_GAP`.

Read-only API-MOB-001 requires request correlation but no separate durable business audit event. Global Audit query continues to emit `authz.audit.access`.

Final mapping status: **36 CANONICAL_EVENT + 15 NO_SEPARATE_AUDIT_EVENT_REQUIRED = 51/51 RESOLVED**.

## 7. Transaction closure

All operation-register rows marked `TX` use explicit InnoDB transaction boundaries. Existing A3 locking rules remain authoritative.

Critical invariants remain unchanged:

- `Reserved <= OnHand`.
- Allocation increases reservation only; it does not reduce OnHand.
- Picking does not reduce OnHand.
- Packing does not reduce OnHand.
- Confirm Dispatch is the physical stock exit and reduces OnHand exactly once.
- Dispatch reconciles the fulfilled reservation and performs mandatory finance/audit effects atomically before `Completed` becomes observable.
- Register Payment and Apply Payment remain separate operations.
- Apply Payment may leave unapplied payment balance and closes an obligation only when its outstanding balance reaches zero.
- Offline processing uses **one transaction per applied original operation**, never one batch-global transaction.

## 8. Endpoint inventory dispositions

The following earlier gaps are explicitly dispositioned and are **not missing initial-v1 endpoints**:

| Gap | Initial-v1 disposition | Implementation rule |
|---|---|---|
| `customer.order.read` / UNRES-001 | DEFERRED — NEEDS PRODUCT DECISION | Do not invent GET orders/tracking until tracking/read semantics exist. Capability remains reserved for future OWN-data operation. |
| `commercial.customer.maintain` | DEFERRED — NEEDS PRODUCT DECISION | Do not invent fields or customer-commercial mutation endpoints. |
| MOB-OFFLINE-TASKS | RESOLVED | API-MOB-001 work feed. |
| MOB-CONFLICTS independent query | DEFERRED — NOT REQUIRED BY CURRENT CONTRACT | `/sync` returns inline per-operation conflict outcomes; do not create `GET /sync/conflicts` or `conflicts.read`. |
| Allocation capability / UNRES-003 | RESOLVED FOR INITIAL V1 | `fulfillment.allocate` + ACT-003; System auto-trigger deferred. |
| ASN creator/approver / UNRES-002 | DEFERRED OUTSIDE RECEIPT CONTRACT | API-INV-001 requires valid ASN but does not invent ASN authoring workflow. |
| Master Data Delete | NOT REQUIRED | API-MAST-004 and API-MAST-008 remain retired. |
| Audit retention exact duration / UNRES-007 | DEFERRED TO OPERATIONS/LEGAL POLICY | No duration fabricated. |

These dispositions make the initial endpoint inventory implementation-safe while preserving unresolved Product Truth as deferred rather than silently manufacturing functionality.

## 9. Contract traceability

| Contract IDs | Product / Architecture Truth | Acceptance / invariant linkage |
|---|---|---|
| API-AUTH-001..006 | UC-001, FR-022, NFR-001, EVD-ARCH-SEC-001 | authenticated/authorized access; revocation/session rules |
| API-MAST-001..003,005..007,012..020 | UC-002, FR-001, ACT-007 | AC-001; authorized master maintenance only |
| API-ADM-001..006 | ACT-007 + EVD-ARCH-SEC-001 explicit admin capabilities | least privilege; no implicit business bypass; durable security audit on mutations |
| API-AUDIT-001 | FR-019, AC-017, EVD-ARCH-SEC-001 UNRES-006 resolution | only `audit.global.read`; access itself audited |
| API-INV-001 | UC-003, FR-002, BR-004 | AC-002; valid ASN required |
| API-INV-002 | UC-004, FR-003, FR-004 | AC-003; stock leaves reception and is associated with destination |
| API-INV-003 | UC-005, FR-004, BR-003, BR-004 | AC-004; granular InventoryItem visibility |
| API-INV-004..007 | UC-006, FR-005, FR-019, BR-004 | AC-005; traceable adjustment/movement/blocking |
| API-ORD-001,002 | UC-007, FR-007, ACT-001 OWN policy | AC-007; own order creation from eligible catalog |
| API-ORD-003 | UC-008, FR-008, BR-009 | AC-008; commercial validation and credit restriction |
| API-FUL-001 | UC-009, FR-006, BR-001..003, NFR-002, NFR-004 | AC-006; insufficient eligible stock cannot over-reserve |
| API-FUL-002 | UC-010, FR-009 | AC-009; confirmed picking linked to order; OnHand unchanged |
| API-FUL-003 | UC-011, FR-010 | AC-010; identifiable packages; OnHand unchanged |
| API-SHP-001 | UC-012, FR-011 | AC-011; packed units grouped without conflicting assignment |
| API-SHP-002 | UC-013, FR-012, FR-013, BR-005, NFR-002 | AC-012, AC-013; atomic physical exit + financial obligation when applicable |
| API-FIN-001 | UC-014, FR-016, BR-005 | AC-016; internal AR view of same obligations |
| API-FIN-002 | UC-015, FR-014, BR-006 | AC-014; payment registration separate from application |
| API-FIN-003 | UC-016, FR-015, BR-007 | AC-015; partial/full apply; close only at zero outstanding balance |
| API-FIN-004 | UC-017, FR-017, BR-005, ACT-001 OWN policy | AC-016; customer AP view of same obligations |
| API-FIN-005,006 | UC-018, FR-018 | AC-016; consolidated debt/payment statement; OWN enforcement for CP |
| API-MOB-001 | UC-019, FR-020, NFR-006, BR-010 | AC-018; previously downloaded server-authoritative work supports offline continuation |
| API-SYNC-001 | UC-020, FR-021, BR-008, BR-010, NFR-005, NFR-007 | AC-019; per-operation reauthorization/idempotency/conflict handling; server wins |

Cross-cutting traceability:

- RFC 9457 error semantics -> EVD-ARCH-API-001.
- Correlation and durable audit -> EVD-ARCH-AUDIT-001 + ADR-006.
- Transaction/concurrency/idempotency -> EVD-ARCH-TXN-001 + ADR-006.
- Authentication/authorization/ownership -> EVD-ARCH-SEC-001 + ADR-006.
- Interface intent -> EVD-UI-SCOPE-001.

## 10. Gate assessment

For initial v1, this closure provides explicit evidence for:

- `api.scope_defined`: PASS (EVD-API-002 + this closure)
- `api.endpoint_inventory`: PASS
- `api.auth_contract`: PASS (EVD-API-003)
- `api.permission_matrix`: PASS
- `api.audit_event_mapping`: PASS
- `api.idempotency_matrix`: PASS
- `api.contract_traceability`: PASS

Therefore `api_contract_ready` has sufficient implementation-safe evidence to move to PASS when this PR is accepted. API implementation remains NOT_STARTED until this contract closure is merged.

## 11. Non-goals

This closure does not:

- implement Laravel code;
- create migrations;
- create OpenAPI or Postman artifacts early;
- implement Web/KMP clients;
- define automatic System allocation;
- define customer order tracking;
- define commercial customer-maintenance fields;
- define a persistent conflict-query resource;
- define ASN authoring/approval;
- invent audit retention duration;
- weaken server authority, ownership, RBAC, inventory, finance, dispatch or offline invariants.
