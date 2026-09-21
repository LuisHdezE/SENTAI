# SENTAI - API Permission Matrix

**Artifact ID:** EVD-API-005
**Blueprint Phase:** API Contract Design (C3)
**Status:** READY_FOR_REVIEW

## 1. Coverage Summary

* **Active Contract IDs:** 50
* **Contract IDs RESOLVED:** 49
* **Contract IDs GAP:** 1 (`API-FUL-001`)

The following matrix contains 20 grouping rows that exhaustively map all 50 Contract IDs.

## 2. Matrix

| Contract ID | Surface | Actor / Security Role | Authentication | Required Capability | Ownership / Context Policy | Resource Scope | Authorization Decision | Offline Revalidation | Significant Denial Audit | Status | Notes |
|---|---|---|---|---|---|---|---|---|---|---|---|
| API-AUTH-001 | BO, CP | Todos | pre-auth | N/A | N/A | N/A | N/A | N/A | N/A | RESOLVED | Web Login |
| API-AUTH-002 | BO, CP | Todos | authenticated Web session | N/A | solo invalidar propia sesión | own | server-side | N/A | N/A | RESOLVED | Web Logout |
| API-AUTH-003 | BO, CP | Todos | pre-auth/session-bootstrap | N/A | N/A | N/A | N/A | N/A | N/A | RESOLVED | Web CSRF |
| API-AUTH-004 | MOB | ACT-002 | pre-auth | N/A | N/A | N/A | N/A | N/A | N/A | RESOLVED | Mobile Login |
| API-AUTH-005 | MOB | ACT-002 | refresh-session credential required | N/A | solo renovar propia sesión | own | server-side | N/A | N/A | RESOLVED | Mobile Refresh |
| API-AUTH-006 | MOB | ACT-002 | access credential | N/A | N/A | own | server-side | N/A | N/A | RESOLVED | Mobile Logout |
| API-MAST-001..003, 005..007, 012..020 | BO | ACT-007 | authenticated Web session | `admin.masters.maintain` | N/A | global | server-side | N/A | conditional: `authz.denial.significant` | RESOLVED | Master Data CRUD |
| API-ADM-001..004 | BO | ACT-007 | authenticated Web session | `admin.identity.manage` | N/A | global | server-side | N/A | conditional: `authz.denial.significant` | RESOLVED | Identity |
| API-ADM-005..006 | BO | ACT-007 | authenticated Web session | `admin.roles.manage` | N/A | global | server-side | N/A | conditional: `authz.denial.significant` | RESOLVED | Roles |
| API-AUDIT-001 | BO | AuditViewer | authenticated Web session | `audit.global.read` | N/A | global | server-side | N/A | conditional: `authz.denial.significant` | RESOLVED | ACT-007 requires role |
| API-INV-001..002 | MOB | ACT-002 | access credential | `warehouse.receive` / `warehouse.putaway` | N/A | global | server-side | required | conditional: `authz.denial.significant` | RESOLVED | Receive / Put-away |
| API-INV-003, 004..007 | BO | ACT-003 | authenticated Web session | `inventory.read` / `adjust` / `location.block` | N/A | global | server-side | N/A | conditional: `authz.denial.significant` | RESOLVED | Inventory / Location |
| API-ORD-001 | CP | ACT-001 | authenticated Web session | `catalog.read` | N/A | global | server-side | N/A | conditional: `authz.denial.significant` | RESOLVED | Catalog |
| API-ORD-002 | CP | ACT-001 | authenticated Web session | `customer.order.create` | OWN data only | own | server-side | N/A | conditional: `authz.denial.significant` | RESOLVED | CustomerID forced |
| API-ORD-003 | BO | ACT-005 | authenticated Web session | `commercial.order.approve` | N/A | global | server-side | N/A | conditional: `authz.denial.significant` | RESOLVED | Approve |
| API-FUL-001 | BO | ACT-003 / Sys | authenticated Web session | `TBD` | N/A | global | server-side | N/A | conditional: `authz.denial.significant` | GAP | PERMISSION GAP |
| API-FUL-002..003 | MOB | ACT-002 | access credential | `warehouse.picking` / `warehouse.packing` | N/A | global | server-side | required | conditional: `authz.denial.significant` | RESOLVED | Picking / Packing |
| API-SHP-001..002 | BO | ACT-004 | authenticated Web session | `dispatch.plan` / `dispatch.confirm` | N/A | global | server-side | N/A | conditional: `authz.denial.significant` | RESOLVED | Shipping |
| API-FIN-001..003, 005 | BO | ACT-006 | authenticated Web session | `finance.receivables.read` / `register` / `apply` | N/A | global | server-side | N/A | conditional: `authz.denial.significant` | RESOLVED | Finance BO |
| API-FIN-004, 006 | CP | ACT-001 | authenticated Web session | `customer.finance.read` | OWN obligations/statement only | own | server-side | N/A | conditional: `authz.denial.significant` | RESOLVED | Finance CP |
| API-SYNC-001 | MOB | ACT-002 | access credential | capability of original operation | ownership/context policy of original operation | N/A | envelope authentication, current session/revocation check, per-operation identity resolution, capability of original operation, ownership/context policy of original operation, reject if current authorization no longer permits operation. | REQUIRED — per synchronized operation | conditional: `sync.authorization.failed`, and `authz.denial.significant` if security-significant | RESOLVED | Offline Envelope |
