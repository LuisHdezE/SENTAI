# SENTAI - API Endpoint Inventory Reconciliation

**Artifact ID:** EVD-API-004
**Blueprint Phase:** API Contract Design (C2)
**Status:** READY_FOR_REVIEW

---

## 1. Objetivos de Reconciliación

Este documento define la matriz formal que reconcilia las operaciones provisorias declaradas en C1 y el Product Truth (EVD-REQ-001, EVD-UI-SCOPE-001, EVD-ARCH-SEC-001). Se clasifica la viabilidad de cada intención en el diseño del contrato, documentando la cobertura exhaustiva de todas las áreas de Interface Scope y las capacidades A2 aprobadas.

---

## 2. Matriz de Reconciliación Contractual (Exhaustiva)

| Source / Area / Capability | Classification | Contract Operation / Gap | Rationale / Capability Mapping |
|---|---|---|---|
| **Interface Scope: Backoffice** | | | |
| BO-AUTH | API_OPERATION_REQUIRED | API-AUTH-001 (Web Login), API-AUTH-002 (Web Logout), API-AUTH-003 (Web CSRF) | Browser backend-managed session lifecycle. |
| BO-MASTERS | API_OPERATION_REQUIRED | API-MAST-001..003, API-MAST-005..007, API-MAST-012..020 | Maintenance CRUD for Product, Customer, Warehouse, Zone, Location. Consumes `admin.masters.maintain`. |
| BO-MASTERS (Product Delete) | NO_SEPARATE_API_OPERATION_REQUIRED | RETIRED_FROM_DRAFT_C1 (API-MAST-004) | Delete unsupported by approved requirements. |
| BO-MASTERS (Customer Delete) | NO_SEPARATE_API_OPERATION_REQUIRED | RETIRED_FROM_DRAFT_C1 (API-MAST-008) | Delete unsupported by approved requirements. |
| ACT-007 / Admin Identity & Roles | API_OPERATION_REQUIRED | API-ADM-001..006 | Derived from ACT-007 responsibility + EVD-ARCH-SEC-001 capability model; no dedicated approved Interface Scope intent-area or explicit FR/UC currently exists. |
| BO-INVENTORY | API_OPERATION_REQUIRED | API-INV-003 | Inventory list query. Consumes `inventory.read`. |
| BO-MOVEMENTS | API_OPERATION_REQUIRED | API-INV-004, API-INV-005, API-INV-006, API-INV-007 | Adjust, Move, Block, Unblock. Consumes `inventory.adjust`, `inventory.location.block`. |
| BO-ALLOCATION | API_OPERATION_REQUIRED | API-FUL-001 | Order allocation. Capability contractual is TBD / gap A2. |
| BO-ORDER-VALIDATION | API_OPERATION_REQUIRED | API-ORD-003 | Commercial order approval. Consumes `commercial.order.approve`. |
| BO-SHIPPING-PLAN | API_OPERATION_REQUIRED | API-SHP-001 | Shipping plan creation. Consumes `dispatch.plan`. |
| BO-DISPATCH | API_OPERATION_REQUIRED | API-SHP-002 | Dispatch confirmation. Consumes `dispatch.confirm`. |
| BO-AR | API_OPERATION_REQUIRED | API-FIN-001 | Accounts Receivable query. Consumes `finance.receivables.read`. |
| BO-PAYMENT-REGISTER | API_OPERATION_REQUIRED | API-FIN-002 | Register received payment. Consumes `finance.payment.register`. |
| BO-PAYMENT-APPLY | API_OPERATION_REQUIRED | API-FIN-003 | Apply payment to debt. Consumes `finance.payment.apply`. |
| BO-STATEMENT | API_OPERATION_REQUIRED | API-FIN-005 | Global customer statement query. Consumes `finance.receivables.read`. |
| BO-AUDIT | API_OPERATION_REQUIRED | API-AUDIT-001 | Global audit events query. Consumes `audit.global.read`. |
| **Interface Scope: Customer Portal** | | | |
| CP-AUTH | API_OPERATION_REQUIRED | API-AUTH-001, API-AUTH-002, API-AUTH-003 | Reuses Web Auth Lifecycle (backend-managed). |
| CP-CATALOG | API_OPERATION_REQUIRED | API-ORD-001 | Eligible product catalog query. Consumes `catalog.read`. |
| CP-ORDER-CREATE | API_OPERATION_REQUIRED | API-ORD-002 | Customer order creation. Consumes `customer.order.create`. |
| CP-AP | API_OPERATION_REQUIRED | API-FIN-004 | Accounts Payable query. Consumes `customer.finance.read`. |
| CP-STATEMENT | API_OPERATION_REQUIRED | API-FIN-006 | Customer's own statement query. Consumes `customer.finance.read`. |
| **Interface Scope: Mobile** | | | |
| MOB-AUTH | API_OPERATION_REQUIRED | API-AUTH-004, API-AUTH-005, API-AUTH-006 | Mobile short-lived credentials & refresh rotation. |
| MOB-RECEPTION | API_OPERATION_REQUIRED | API-INV-001 | Receive merchandise. Consumes `warehouse.receive`. |
| MOB-PUTAWAY | API_OPERATION_REQUIRED | API-INV-002 | Put-away execution. Consumes `warehouse.putaway`. |
| MOB-PICKING | API_OPERATION_REQUIRED | API-FUL-002 | Picking execution. Consumes `warehouse.picking`. |
| MOB-PACKING | API_OPERATION_REQUIRED | API-FUL-003 | Packing execution. Consumes `warehouse.packing`. |
| MOB-OFFLINE-TASKS | UNRESOLVED_CONTRACT_GAP | Gap | Need for downloaded offline work exists, but exact server resource/collection model and heterogeneous authorization model are not sufficiently defined. |
| MOB-SYNC | API_OPERATION_REQUIRED | API-SYNC-001 | Envelope for offline operations. Reauthorization uses capability of each original operation. |
| MOB-CONFLICTS | UNRESOLVED_CONTRACT_GAP | Gap | Interface intent exists (FR-021/UC-020). `/sync` communicates per-operation outcomes. Evidence doesn't prove separate persistent/query endpoint is required. Gap specifically on independent query mechanism. |
| **A2 Canonical Capabilities** | | | |
| `catalog.read` | API_OPERATION_REQUIRED | API-ORD-001 | Consumed by CP-CATALOG via API-ORD-001. |
| `customer.order.create` | API_OPERATION_REQUIRED | API-ORD-002 | Consumed by CP-ORDER-CREATE. |
| `customer.order.read` | UNRESOLVED_CONTRACT_GAP | Gap | ACT-001 responsibility, A2 capability exists, UNRES-001. No FR/UC defines order read/tracking properly yet. |
| `customer.finance.read` | API_OPERATION_REQUIRED | API-FIN-004, API-FIN-006 | Consumed by CP-AP and CP-STATEMENT. |
| `warehouse.receive` | API_OPERATION_REQUIRED | API-INV-001 | Consumed by MOB-RECEPTION. |
| `warehouse.putaway` | API_OPERATION_REQUIRED | API-INV-002 | Consumed by MOB-PUTAWAY. |
| `warehouse.picking` | API_OPERATION_REQUIRED | API-FUL-002 | Consumed by MOB-PICKING. |
| `warehouse.packing` | API_OPERATION_REQUIRED | API-FUL-003 | Consumed by MOB-PACKING. |
| `inventory.read` | API_OPERATION_REQUIRED | API-INV-003 | Consumed by BO-INVENTORY. |
| `inventory.adjust` | API_OPERATION_REQUIRED | API-INV-004, API-INV-005 | Consumed by BO-MOVEMENTS (Adjust, Move). |
| `inventory.location.block` | API_OPERATION_REQUIRED | API-INV-006, API-INV-007 | Consumed by BO-MOVEMENTS (Block, Unblock). |
| `dispatch.plan` | API_OPERATION_REQUIRED | API-SHP-001 | Consumed by BO-SHIPPING-PLAN. |
| `dispatch.confirm` | API_OPERATION_REQUIRED | API-SHP-002 | Consumed by BO-DISPATCH. |
| `commercial.order.approve` | API_OPERATION_REQUIRED | API-ORD-003 | Consumed by BO-ORDER-VALIDATION. |
| `commercial.customer.maintain` | UNRESOLVED_CONTRACT_GAP | Gap | ACT-005 responsibility and capability exist. No dedicated approved intent-area currently defines this capability. No FR/UC explains maintained data elements. |
| `finance.payment.register` | API_OPERATION_REQUIRED | API-FIN-002 | Consumed by BO-PAYMENT-REGISTER. |
| `finance.payment.apply` | API_OPERATION_REQUIRED | API-FIN-003 | Consumed by BO-PAYMENT-APPLY. |
| `finance.receivables.read` | API_OPERATION_REQUIRED | API-FIN-001, API-FIN-005 | Consumed by BO-AR and BO-STATEMENT. |
| `admin.identity.manage` | API_OPERATION_REQUIRED | API-ADM-001..004 | Consumed by Admin Identity operations. |
| `admin.roles.manage` | API_OPERATION_REQUIRED | API-ADM-005, API-ADM-006 | Consumed by Admin Role operations. |
| `admin.masters.maintain` | API_OPERATION_REQUIRED | API-MAST-* CRUDs | Consumed by BO-MASTERS CRUD operations (excluding deleted concepts). |
| `audit.global.read` | API_OPERATION_REQUIRED | API-AUDIT-001 | Consumed by BO-AUDIT. |

---

## 3. Notas Adicionales de Reconciliación

- **Preservación de Contract IDs Históricos:** Los IDs `API-MAST-001` a `API-MAST-003`, y `API-MAST-005` a `API-MAST-007` han conservado su semántica exacta del draft C1. Los IDs `API-MAST-004` y `API-MAST-008` (Delete) fueron formalmente documentados como `RETIRED_FROM_DRAFT_C1` (`NO_SEPARATE_API_OPERATION_REQUIRED` under current approved requirements). Los entries de agrupación provisionales `009-011` también han sido reemplazados mediante expansión explícita a IDs no colisionantes (`012-020`).
- **Paths de Master Data:** Se preservan los root resources aprobados implícitamente en C1 (`/api/v1/warehouses`, `/api/v1/zones`, `/api/v1/locations`) omitiendo la anidación en `/masters/` por falta de autorización contractual explícita.
- **Admin Identity / Roles:** La operabilidad (API-ADM-*) no se deriva de UC-002 ni FR-022, sino de la responsabilidad explícita de ACT-007 (EVD-ARCH-SEC-001). No se asumen operaciones DELETE para Identity.
- **Transaccionalidad en Master Data:** Cualquier operación que emite `admin.masters.changed` fue actualizada a `Transaction = required` en el Baseline de C2 de acuerdo con el modelo de consistencia de A3.
