# SENTAI - API Audit Event Mapping

**Artifact ID:** EVD-API-006
**Blueprint Phase:** API Contract Design (C3)
**Status:** READY_FOR_REVIEW

## 1. Mapping

| Contract ID | Intent | Event Classification | Canonical Event(s) | Trigger | Transaction Coupling | Actor Context | Correlation Required | Significant Denial | Status | Rationale |
|---|---|---|---|---|---|---|---|---|---|---|
| API-AUTH-001 | Web Login | CANONICAL_EVENT | `auth.login.success`, `auth.login.failure` | conditional | post-commit | System | YES | N/A | RESOLVED | Security tracking. |
| API-AUTH-002 | Web Logout | CANONICAL_EVENT | `auth.logout` | success | post-commit | Auth User | YES | N/A | RESOLVED | No session.revoked automatically. |
| API-AUTH-003 | Web CSRF | NO_SEPARATE_AUDIT_EVENT_REQUIRED | N/A | N/A | N/A | System | NO | N/A | RESOLVED | No architecture requirement. |
| API-AUTH-004 | Mobile Login | CANONICAL_EVENT | `auth.login.success`, `auth.login.failure` | conditional | post-commit | System | YES | N/A | RESOLVED | Security tracking. |
| API-AUTH-005 | Mobile Refresh | NO_SEPARATE_AUDIT_EVENT_REQUIRED | N/A | N/A | N/A | Auth User | NO | N/A | RESOLVED | Routine rotation. |
| API-AUTH-006 | Mobile Logout | CANONICAL_EVENT | `auth.logout` | success | post-commit | Auth User | YES | N/A | RESOLVED | User initiated logout. |
| API-MAST-002, 006, 013, 016, 019 | Create Master | CANONICAL_EVENT | `admin.masters.changed` | success | same TX | ACT-007 | YES | conditional | RESOLVED | Mutation. |
| API-MAST-003, 007, 014, 017, 020 | Update Master | CANONICAL_EVENT | `admin.masters.changed` | success | same TX | ACT-007 | YES | conditional | RESOLVED | Mutation. |
| API-MAST-001, 005, 012, 015, 018 | List Master | NO_SEPARATE_AUDIT_EVENT_REQUIRED | N/A | N/A | N/A | ACT-007 | NO | conditional | RESOLVED | Read-only. |
| API-ADM-001 | List Users | NO_SEPARATE_AUDIT_EVENT_REQUIRED | N/A | N/A | N/A | ACT-007 | NO | conditional | RESOLVED | Read-only. |
| API-ADM-002 | Create User | CANONICAL_EVENT | `admin.user.created` | success | same TX | ACT-007 | YES | conditional | RESOLVED | Security. |
| API-ADM-003 | Update User | CANONICAL_EVENT | `admin.user.updated` | success | same TX | ACT-007 | YES | conditional | RESOLVED | Security. |
| API-ADM-004 | Disable User | CANONICAL_EVENT | `admin.user.disabled` | success | same TX | ACT-007 | YES | conditional | RESOLVED | Security. |
| API-ADM-005 | Assign Role | CANONICAL_EVENT | `authz.role.assigned` | success | same TX | ACT-007 | YES | conditional | RESOLVED | Security. |
| API-ADM-006 | Revoke Role | CANONICAL_EVENT | `authz.role.revoked` | success | same TX | ACT-007 | YES | conditional | RESOLVED | Security. |
| API-AUDIT-001 | Global Audit | CANONICAL_EVENT | `authz.audit.access` | success | post-commit | AuditViewer | YES | conditional | RESOLVED | A3 requirement. |
| API-INV-001 | Receive | CANONICAL_EVENT | `inventory.asn.received` | success | same TX | ACT-002 | YES | conditional | RESOLVED | Mutation. |
| API-INV-002 | Put-away | CANONICAL_EVENT | `inventory.putaway.completed` | success | same TX | ACT-002 | YES | conditional | RESOLVED | Mutation. |
| API-INV-003 | List Inventory | NO_SEPARATE_AUDIT_EVENT_REQUIRED | N/A | N/A | N/A | ACT-003 | NO | conditional | RESOLVED | Read-only. |
| API-INV-004 | Adjust | CANONICAL_EVENT | `inventory.adjustment.applied` | success | same TX | ACT-003 | YES | conditional | RESOLVED | Mutation. |
| API-INV-005 | Move | AUDIT_CATALOG_GAP | N/A | success | same TX | ACT-003 | YES | conditional | GAP | `inventory.adjustment.applied` is not semantically a move. No `inventory.move` canonical event exists. |
| API-INV-006 | Block Location | CANONICAL_EVENT | `inventory.location.blocked` | success | same TX | ACT-003 | YES | conditional | RESOLVED | Mutation. |
| API-INV-007 | Unblock Loc | CANONICAL_EVENT | `inventory.location.unblocked` | success | same TX | ACT-003 | YES | conditional | RESOLVED | Mutation. |
| API-ORD-001 | Catalog | NO_SEPARATE_AUDIT_EVENT_REQUIRED | N/A | N/A | N/A | ACT-001 | NO | conditional | RESOLVED | Read-only. |
| API-ORD-002 | Create Order | AUDIT_CATALOG_GAP | N/A | success | same TX | ACT-001 | YES | conditional | GAP | No `order.created` event in canonical list. |
| API-ORD-003 | Confirm Order | AUDIT_CATALOG_GAP | N/A | success | same TX | ACT-005 | YES | conditional | GAP | No `order.confirmed` event in canonical list. |
| API-FUL-001 | Allocate | CANONICAL_EVENT | `fulfillment.allocation.created` | success | same TX | ACT-003/Sys | YES | conditional | RESOLVED | Mutation. |
| API-FUL-002 | Picking | AUDIT_CATALOG_GAP | N/A | success | same TX | ACT-002 | YES | conditional | GAP | No `picking.completed` event in canonical list. |
| API-FUL-003 | Packing | AUDIT_CATALOG_GAP | N/A | success | same TX | ACT-002 | YES | conditional | GAP | No `packing.completed` event in canonical list. |
| API-SHP-001 | Plan | AUDIT_CATALOG_GAP | N/A | success | same TX | ACT-004 | YES | conditional | GAP | No `shipping.plan.created` event. |
| API-SHP-002 | Dispatch | CANONICAL_EVENT | `dispatch.confirmed`, `finance.obligation.created` | success | same TX | ACT-004 | YES | conditional | RESOLVED | `finance.obligation.created` is conditional on obligation logic. |
| API-FIN-001 | Receivables | NO_SEPARATE_AUDIT_EVENT_REQUIRED | N/A | N/A | N/A | ACT-006 | NO | conditional | RESOLVED | Read-only. |
| API-FIN-002 | Reg Payment | CANONICAL_EVENT | `finance.payment.registered` | success | same TX | ACT-006 | YES | conditional | RESOLVED | Mutation. |
| API-FIN-003 | Apply Payment | CANONICAL_EVENT | `finance.payment.applied`, `finance.obligation.closed` | success | same TX | ACT-006 | YES | conditional | RESOLVED | `finance.obligation.closed` conditional if paid in full. |
| API-FIN-004 | AP | NO_SEPARATE_AUDIT_EVENT_REQUIRED | N/A | N/A | N/A | ACT-001 | NO | conditional | RESOLVED | Read-only. |
| API-FIN-005 | BO Statement | NO_SEPARATE_AUDIT_EVENT_REQUIRED | N/A | N/A | N/A | ACT-006 | NO | conditional | RESOLVED | Read-only. |
| API-FIN-006 | CP Statement | NO_SEPARATE_AUDIT_EVENT_REQUIRED | N/A | N/A | N/A | ACT-001 | NO | conditional | RESOLVED | Read-only. |
| API-SYNC-001 | Sync | CANONICAL_EVENT | `sync.operation.applied`, `sync.operation.rejected`, `sync.conflict.generated`, `sync.authorization.failed` | conditional | same TX / post-commit | ACT-002 | YES | conditional | RESOLVED | Applied is same TX. Rejected/Conflict/AuthFail are post-commit. |
