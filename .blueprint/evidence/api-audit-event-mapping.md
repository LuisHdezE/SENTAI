# SENTAI - API Audit Event Mapping

**Artifact ID:** EVD-API-006
**Blueprint Phase:** API Contract Design (C3)
**Status:** READY_FOR_REVIEW

## 1. Coverage Summary

* **Contract IDs Mapped:** 50/50
* **CANONICAL_EVENT:** 30 Contract IDs
* **NO_SEPARATE_AUDIT_EVENT_REQUIRED:** 14 Contract IDs
* **AUDIT_CATALOG_GAP:** 6 Contract IDs

The following matrix contains 26 grouping rows that exhaustively map all 50 Contract IDs.

## 2. Mapping

| Contract ID | Intent | Event Classification | Canonical Event(s) | Trigger | Transaction Coupling | Actor Context | Correlation Required | Significant Denial | Status | Rationale |
|---|---|---|---|---|---|---|---|---|---|---|
| API-AUTH-001 | Web Login | CANONICAL_EVENT | exactly one of: `auth.login.success`, `auth.login.failure` | conditional | post-commit | User account on success / attempted identity on failure | YES | N/A | RESOLVED | Security tracking. |
| API-AUTH-002 | Web Logout | CANONICAL_EVENT | `auth.logout` | success | post-commit | Auth User | YES | N/A | RESOLVED | No session.revoked automatically. |
| API-AUTH-003 | Web CSRF | NO_SEPARATE_AUDIT_EVENT_REQUIRED | N/A | N/A | N/A | System | N/A — no separate audit event; request correlation still required | N/A | RESOLVED | No architecture requirement. |
| API-AUTH-004 | Mobile Login | CANONICAL_EVENT | exactly one of: `auth.login.success`, `auth.login.failure` | conditional | post-commit | User account on success / attempted identity on failure | YES | N/A | RESOLVED | Security tracking. |
| API-AUTH-005 | Mobile Refresh | NO_SEPARATE_AUDIT_EVENT_REQUIRED | N/A | N/A | N/A | Auth User | N/A — no separate audit event; request correlation still required | N/A | RESOLVED | Routine rotation. |
| API-AUTH-006 | Mobile Logout | CANONICAL_EVENT | `auth.logout` | success | post-commit | Auth User | YES | N/A | RESOLVED | User initiated logout. |
| API-MAST-002, 006, 013, 016, 019 | Create Master | CANONICAL_EVENT | `admin.masters.changed` | success | same TX | ACT-007 | YES | conditional | RESOLVED | Mutation. |
| API-MAST-003, 007, 014, 017, 020 | Update Master | CANONICAL_EVENT | `admin.masters.changed` | success | same TX | ACT-007 | YES | conditional | RESOLVED | Mutation. |
| API-MAST-001, 005, 012, 015, 018 | List Master | NO_SEPARATE_AUDIT_EVENT_REQUIRED | N/A | N/A | N/A | ACT-007 | N/A — no separate audit event; request correlation still required | conditional | RESOLVED | Read-only. |
| API-ADM-001 | List Users | NO_SEPARATE_AUDIT_EVENT_REQUIRED | N/A | N/A | N/A | ACT-007 | N/A — no separate audit event; request correlation still required | conditional | RESOLVED | Read-only. |
| API-ADM-002..004 | CRUD User | CANONICAL_EVENT | `admin.user.created`, `admin.user.updated`, `admin.user.disabled` | success | same TX | ACT-007 | YES | conditional | RESOLVED | Security. |
| API-ADM-005..006 | Manage Roles | CANONICAL_EVENT | `authz.role.assigned`, `authz.role.revoked` | success | same TX | ACT-007 | YES | conditional | RESOLVED | Security. |
| API-AUDIT-001 | Global Audit | CANONICAL_EVENT | `authz.audit.access` | success | post-commit | AuditViewer | YES | conditional | RESOLVED | A3 requirement. |
| API-INV-001..002 | Receive / Put-away | CANONICAL_EVENT | `inventory.asn.received`, `inventory.putaway.completed` | success | same TX | ACT-002 | YES | conditional | RESOLVED | Mutation. |
| API-INV-003 | List Inventory | NO_SEPARATE_AUDIT_EVENT_REQUIRED | N/A | N/A | N/A | ACT-003 | N/A — no separate audit event; request correlation still required | conditional | RESOLVED | Read-only. |
| API-INV-004 | Adjust | CANONICAL_EVENT | `inventory.adjustment.applied` | success | same TX | ACT-003 | YES | conditional | RESOLVED | Mutation. |
| API-INV-005 | Move | AUDIT_CATALOG_GAP | N/A | success | same TX | ACT-003 | YES | conditional | GAP | `inventory.adjustment.applied` is not semantically a move. No `inventory.move` canonical event exists. |
| API-INV-006..007 | Block / Unblock | CANONICAL_EVENT | `inventory.location.blocked`, `inventory.location.unblocked` | success | same TX | ACT-003 | YES | conditional | RESOLVED | Mutation. |
| API-ORD-001, API-FIN-001, API-FIN-004..006 | CP/BO Queries | NO_SEPARATE_AUDIT_EVENT_REQUIRED | N/A | N/A | N/A | ACT-001 / ACT-006 | N/A — no separate audit event; request correlation still required | conditional | RESOLVED | Read-only. |
| API-ORD-002..003 | Order Create/Confirm | AUDIT_CATALOG_GAP | N/A | success | TBD — pending canonical audit requirement / transaction policy resolution | ACT-001 / ACT-005 | YES | conditional | GAP | No `order.created` / `confirmed` event in canonical list. |
| API-FUL-001 | Allocate | CANONICAL_EVENT | `fulfillment.allocation.created` | success | same TX | ACT-003/Sys | YES | conditional | RESOLVED | Mutation. |
| API-FUL-002..003 | Picking / Packing | AUDIT_CATALOG_GAP | N/A | success | TBD — pending canonical audit requirement / transaction policy resolution | ACT-002 | YES | conditional | GAP | No `picking.completed` / `packing.completed` event in canonical list. |
| API-SHP-001 | Plan | AUDIT_CATALOG_GAP | N/A | success | TBD — pending canonical audit requirement / transaction policy resolution | ACT-004 | YES | conditional | GAP | No `shipping.plan.created` event. |
| API-SHP-002 | Dispatch | CANONICAL_EVENT | `dispatch.confirmed`, and conditionally `finance.obligation.created` | success | same TX | ACT-004 | YES | conditional | RESOLVED | `finance.obligation.created` is conditional on obligation logic. |
| API-FIN-002..003 | Payment | CANONICAL_EVENT | `finance.payment.registered`, `finance.payment.applied`, and conditionally `finance.obligation.closed` | success | same TX | ACT-006 | YES | conditional | RESOLVED | `finance.obligation.closed` conditional if paid in full. |
| API-SYNC-001 | Sync | CANONICAL_EVENT | Outcome-dependent: `sync.operation.applied`, `sync.operation.rejected`, `sync.conflict.generated`, `sync.authorization.failed` | conditional | same TX / post-commit | ACT-002 | YES | conditional | RESOLVED | Applied is same TX. Rejected/Conflict/AuthFail are post-commit. |
