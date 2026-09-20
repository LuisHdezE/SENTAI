# SENTAI - API Endpoint Inventory Reconciliation

**Artifact ID:** EVD-API-004
**Blueprint Phase:** API Contract Design (C2)
**Status:** READY_FOR_REVIEW

---

## 1. Objetivos de Reconciliación

Este documento define la matriz formal que reconcilia las operaciones provisorias declaradas en C1 y el Product Truth (EVD-REQ-001, EVD-UI-SCOPE-001, EVD-ARCH-SEC-001). Se clasifica la viabilidad de cada intención en el diseño del contrato, resolviendo IDs históricos y diagnosticando gaps.

---

## 2. Matriz de Reconciliación Contractual

| Source | Intent / Capability | Classification | Contract Operation / Gap | Rationale |
|---|---|---|---|---|
| BO-AUTH, CP-AUTH | Web Auth Lifecycle | API_OPERATION_REQUIRED | API-AUTH-001 (Login), API-AUTH-002 (Logout), API-AUTH-003 (CSRF) | Browser backend-managed session lifecycle (A2). |
| MOB-AUTH | Mobile Auth Lifecycle | API_OPERATION_REQUIRED | API-AUTH-004 (Login), API-AUTH-005 (Refresh), API-AUTH-006 (Logout) | Mobile short-lived credentials & refresh rotation (A2). |
| BO-MASTERS | Product List | API_OPERATION_REQUIRED | API-MAST-001 | FR-001 / UC-002 |
| BO-MASTERS | Product Create | API_OPERATION_REQUIRED | API-MAST-002 | FR-001 / UC-002 |
| BO-MASTERS | Product Update | API_OPERATION_REQUIRED | API-MAST-003 | FR-001 / UC-002 |
| BO-MASTERS | Product Delete (C1 API-MAST-004) | NO_SEPARATE_API_OPERATION_REQUIRED | RETIRED_FROM_DRAFT_C1 | Unsupported by approved requirements. No delete specified. |
| BO-MASTERS | Customer List | API_OPERATION_REQUIRED | API-MAST-005 | FR-001 / UC-002 |
| BO-MASTERS | Customer Create | API_OPERATION_REQUIRED | API-MAST-006 | FR-001 / UC-002 |
| BO-MASTERS | Customer Update | API_OPERATION_REQUIRED | API-MAST-007 | FR-001 / UC-002 |
| BO-MASTERS | Customer Delete (C1 API-MAST-008) | NO_SEPARATE_API_OPERATION_REQUIRED | RETIRED_FROM_DRAFT_C1 | Unsupported by approved requirements. No delete specified. |
| BO-MASTERS | Warehouse CRUD (C1 API-MAST-009) | API_OPERATION_REQUIRED | API-MAST-012..014 | FR-001 / UC-002. C1 grouped entry superseded by explicit List/Create/Update. Path: `/api/v1/warehouses`. |
| BO-MASTERS | Zone CRUD (C1 API-MAST-010) | API_OPERATION_REQUIRED | API-MAST-015..017 | FR-001 / UC-002. C1 grouped entry superseded by explicit List/Create/Update. Path: `/api/v1/zones`. |
| BO-MASTERS | Location CRUD (C1 API-MAST-011) | API_OPERATION_REQUIRED | API-MAST-018..020 | FR-001 / UC-002. C1 grouped entry superseded by explicit List/Create/Update. Path: `/api/v1/locations`. |
| BO-INVENTORY | `inventory.location.block` | API_OPERATION_REQUIRED | API-INV-006 (Block), API-INV-007 (Unblock) | BR-004 requires block until explicit unblock/release. |
| BO-AUDIT | `audit.global.read` | API_OPERATION_REQUIRED | API-AUDIT-001 | UNRES-006 resolved in A2. Read global audit events. |
| BO-MASTERS | `admin.identity.manage` / `admin.roles.manage` | API_OPERATION_REQUIRED | API-ADM-001..006 | Derived from ACT-007 responsibility and A2 capability model. Users (List/Create/Update/Disable) and Roles (Assign/Revoke). No specific FR/UC exists. |
| CP-ORDER-CREATE | `customer.order.read` | UNRESOLVED_CONTRACT_GAP | Gap | Missing explicit FR/UC defining customer order tracking scope (UNRES-001). |
| BO-MASTERS | `commercial.customer.maintain` | UNRESOLVED_CONTRACT_GAP | Gap | ACT-005 responsibility exists, but no FR/UC explicit about maintained commercial data elements. |
| MOB-OFFLINE-TASKS | Mobile Offline Tasks Download | UNRESOLVED_CONTRACT_GAP | Gap | Heterogeneous task collection model for picking/put-away undefined in A1. |
| MOB-CONFLICTS | Sync Conflicts | UNRESOLVED_CONTRACT_GAP | Gap | UI intent exists (FR-021/UC-020). Sync returns conflicts inline; evidence doesn't demonstrate this interface requires a separate endpoint, and no canonical capability for GET /sync/conflicts exists. Gap on query mechanism. |

---

## 3. Notas Adicionales de Reconciliación

- **Preservación de Contract IDs Históricos:** Los IDs `API-MAST-001` a `API-MAST-003`, y `API-MAST-005` a `API-MAST-007` han conservado su semántica exacta del draft C1. Los IDs `API-MAST-004` y `API-MAST-008` (Delete) fueron formalmente documentados como `RETIRED_FROM_DRAFT_C1` (`NO_SEPARATE_API_OPERATION_REQUIRED` under current approved requirements). Los entries de agrupación provisionales `009-011` también han sido reemplazados mediante expansión explícita a IDs no colisionantes (`012-020`).
- **Paths de Master Data:** Se preservan los root resources aprobados implícitamente en C1 (`/api/v1/warehouses`, `/api/v1/zones`, `/api/v1/locations`) omitiendo la anidación en `/masters/` por falta de autorización contractual explícita.
- **Admin Identity / Roles:** La operabilidad (API-ADM-*) no se deriva de UC-002 ni FR-022, sino de la responsabilidad explícita de ACT-007 (EVD-ARCH-SEC-001). No se asumen operaciones DELETE para Identity.
- **Transaccionalidad en Master Data:** Cualquier operación que emite `admin.masters.changed` fue actualizada a `Transaction = required` en el Baseline de C2 de acuerdo con el modelo de consistencia de A3.
