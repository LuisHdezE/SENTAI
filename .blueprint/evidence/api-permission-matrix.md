# SENTAI - API Permission Matrix

**Artifact ID:** EVD-API-005
**Blueprint Phase:** API Contract Design (C3)
**Status:** READY_FOR_REVIEW

## 1. Matrix

| Contract ID | Surface | Actor / Security Role | Authentication | Required Capability | Ownership / Context Policy | Resource Scope | Authorization Decision | Offline Revalidation | Significant Denial Audit | Status | Notes |
|---|---|---|---|---|---|---|---|---|---|---|---|
| API-AUTH-001 | BO, CP | Todos | pre-auth | N/A | N/A | N/A | N/A | N/A | N/A | RESOLVED | Web Login |
| API-AUTH-002 | BO, CP | Todos | authenticated Web session | N/A | solo invalidar propia sesión | own | server-side | N/A | N/A | RESOLVED | Web Logout |
| API-AUTH-003 | BO, CP | Todos | pre-auth/session-bootstrap | N/A | N/A | N/A | N/A | N/A | N/A | RESOLVED | Web CSRF |
| API-AUTH-004 | MOB | ACT-002 | pre-auth | N/A | N/A | N/A | N/A | N/A | N/A | RESOLVED | Mobile Login |
| API-AUTH-005 | MOB | ACT-002 | refresh credential | N/A | solo renovar propia sesión | own | server-side | N/A | N/A | RESOLVED | Mobile Refresh |
| API-AUTH-006 | MOB | ACT-002 | authenticated/revocable mobile session | N/A | N/A | own | server-side | N/A | N/A | RESOLVED | Mobile Logout |
| API-MAST-001..003 | BO | ACT-007 | access credential | `admin.masters.maintain` | N/A | global | server-side | N/A | `authz.denial.significant` | RESOLVED | Products |
| API-MAST-005..007 | BO | ACT-007 | access credential | `admin.masters.maintain` | N/A | global | server-side | N/A | `authz.denial.significant` | RESOLVED | Customers |
| API-MAST-012..014 | BO | ACT-007 | access credential | `admin.masters.maintain` | N/A | global | server-side | N/A | `authz.denial.significant` | RESOLVED | Warehouses |
| API-MAST-015..017 | BO | ACT-007 | access credential | `admin.masters.maintain` | N/A | global | server-side | N/A | `authz.denial.significant` | RESOLVED | Zones |
| API-MAST-018..020 | BO | ACT-007 | access credential | `admin.masters.maintain` | N/A | global | server-side | N/A | `authz.denial.significant` | RESOLVED | Locations |
| API-ADM-001..004 | BO | ACT-007 | access credential | `admin.identity.manage` | N/A | global | server-side | N/A | `authz.denial.significant` | RESOLVED | Identity |
| API-ADM-005..006 | BO | ACT-007 | access credential | `admin.roles.manage` | N/A | global | server-side | N/A | `authz.denial.significant` | RESOLVED | Roles |
| API-AUDIT-001 | BO | AuditViewer | access credential | `audit.global.read` | N/A | global | server-side | N/A | `authz.denial.significant` | RESOLVED | ACT-007 requires role |
| API-INV-001 | MOB | ACT-002 | access credential | `warehouse.receive` | N/A | global | server-side | required | `authz.denial.significant` | RESOLVED | Receive |
| API-INV-002 | MOB | ACT-002 | access credential | `warehouse.putaway` | N/A | global | server-side | required | `authz.denial.significant` | RESOLVED | Put-away |
| API-INV-003 | BO | ACT-003 | access credential | `inventory.read` | N/A | global | server-side | N/A | `authz.denial.significant` | RESOLVED | List |
| API-INV-004..005 | BO | ACT-003 | access credential | `inventory.adjust` | N/A | global | server-side | N/A | `authz.denial.significant` | RESOLVED | Adjust/Move |
| API-INV-006..007 | BO | ACT-003 | access credential | `inventory.location.block` | N/A | global | server-side | N/A | `authz.denial.significant` | RESOLVED | Block/Unblock |
| API-ORD-001 | CP | ACT-001 | access credential | `catalog.read` | N/A | global | server-side | N/A | `authz.denial.significant` | RESOLVED | Catalog |
| API-ORD-002 | CP | ACT-001 | access credential | `customer.order.create` | OWN data only | own | server-side | N/A | `authz.denial.significant` | RESOLVED | CustomerID forced |
| API-ORD-003 | BO | ACT-005 | access credential | `commercial.order.approve` | N/A | global | server-side | N/A | `authz.denial.significant` | RESOLVED | Approve |
| API-FUL-001 | BO | ACT-003 / Sys | access credential | `TBD` | N/A | global | server-side | N/A | `authz.denial.significant` | GAP | PERMISSION GAP |
| API-FUL-002 | MOB | ACT-002 | access credential | `warehouse.picking` | N/A | global | server-side | required | `authz.denial.significant` | RESOLVED | Picking |
| API-FUL-003 | MOB | ACT-002 | access credential | `warehouse.packing` | N/A | global | server-side | required | `authz.denial.significant` | RESOLVED | Packing |
| API-SHP-001 | BO | ACT-004 | access credential | `dispatch.plan` | N/A | global | server-side | N/A | `authz.denial.significant` | RESOLVED | Plan |
| API-SHP-002 | BO | ACT-004 | access credential | `dispatch.confirm` | N/A | global | server-side | N/A | `authz.denial.significant` | RESOLVED | Dispatch |
| API-FIN-001 | BO | ACT-006 | access credential | `finance.receivables.read` | N/A | global | server-side | N/A | `authz.denial.significant` | RESOLVED | AR |
| API-FIN-002 | BO | ACT-006 | access credential | `finance.payment.register` | N/A | global | server-side | N/A | `authz.denial.significant` | RESOLVED | Reg Payment |
| API-FIN-003 | BO | ACT-006 | access credential | `finance.payment.apply` | N/A | global | server-side | N/A | `authz.denial.significant` | RESOLVED | Apply Payment |
| API-FIN-004 | CP | ACT-001 | access credential | `customer.finance.read` | OWN obligations only | own | server-side | N/A | `authz.denial.significant` | RESOLVED | AP |
| API-FIN-005 | BO | ACT-006 | access credential | `finance.receivables.read` | N/A | global | server-side | N/A | `authz.denial.significant` | RESOLVED | BO Statement |
| API-FIN-006 | CP | ACT-001 | access credential | `customer.finance.read` | OWN statement only | own | server-side | N/A | `authz.denial.significant` | RESOLVED | CP Statement |
| API-SYNC-001 | MOB | ACT-002 | access credential | capability of original operation | revalidar identidad, verificar sesión, ownership de operación original | N/A | server-side | N/A | `sync.authorization.failed` / `authz.denial.significant` | RESOLVED | Offline Envelope |
