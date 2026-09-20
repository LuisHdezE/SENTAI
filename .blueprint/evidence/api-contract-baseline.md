# SENTAI - API Contract Baseline
**Blueprint Phase:** API Contract Design
**Status:** DRAFT (Baseline Inventory)
**Artifact ID:** EVD-API-002
**Type:** api_contract_baseline_evidence

Este documento define el inventario inicial del contrato API derivado estrictamente de EVD-REQ-001 (Requirements & Domain) y EVD-ARCH-001 (Architecture Domain Model). NO define implementación.

## 1. Alcance General

- **Base Path:** `/api/v1/`
- **Error Contract:** RFC 9457 Problem Details (validation, domain_conflict, idempotency_conflict, etc.)
- **Idempotency:** Según requerimiento para mutaciones críticas operacionales o financieras.

## 2. Inventario de Operaciones

| Contract ID | Module | Surface | Actor | UC | FR | BR | Intent | HTTP Method | Path | Command/Query | Authentication | Capability | Ownership | Idempotency | Audit Event | Transaction | Main Success | Main Errors | Notes / unresolved |
|---|---|---|---|---|---|---|---|---|---|---|---|---|---|---|---|---|---|---|---|
| API-AUTH-001 | Identity & Access | BO, CP, MOB | Todos | UC-001 | FR-022 | N/A | Login / Establecer sesión | POST | `/api/v1/auth/login` | Command | pre-auth (establishes session) | N/A | N/A | NOT_REQUIRED | `auth.login.success`, `auth.login.failure` | not required | Sesión/Token emitido | authentication | TBD: Estructura exacta payload offline. Éxito genera success, credenciales inválidas genera failure. |
| API-MAST-001 | Master Data | BO | ACT-007 | UC-002 | FR-001 | N/A | Listar Productos | GET | `/api/v1/products` | Query | required | `admin.masters.maintain` | N/A | NOT_REQUIRED | N/A | not required | Lista de Productos | validation | |
| API-MAST-002 | Master Data | BO | ACT-007 | UC-002 | FR-001 | N/A | Crear Producto | POST | `/api/v1/products` | Command | required | `admin.masters.maintain` | N/A | REQUIRED | `admin.masters.changed` | TBD | Producto creado | validation | |
| API-MAST-003 | Master Data | BO | ACT-007 | UC-002 | FR-001 | N/A | Actualizar Producto | PUT | `/api/v1/products/{id}` | Command | required | `admin.masters.maintain` | N/A | REQUIRED | `admin.masters.changed` | TBD | Producto actualizado | validation | |
| API-MAST-004 | Master Data | BO | ACT-007 | UC-002 | FR-001 | N/A | Eliminar Producto | DELETE | `/api/v1/products/{id}` | Command | required | `admin.masters.maintain` | N/A | REQUIRED | `admin.masters.changed` | TBD | Producto eliminado | domain_conflict | |
| API-MAST-005 | Master Data | BO | ACT-007 | UC-002 | FR-001 | N/A | Listar Clientes | GET | `/api/v1/customers` | Query | required | `admin.masters.maintain` | N/A | NOT_REQUIRED | N/A | not required | Lista de Clientes | validation | |
| API-MAST-006 | Master Data | BO | ACT-007 | UC-002 | FR-001 | N/A | Crear Cliente | POST | `/api/v1/customers` | Command | required | `admin.masters.maintain` | N/A | REQUIRED | `admin.masters.changed` | TBD | Cliente creado | validation | |
| API-MAST-007 | Master Data | BO | ACT-007 | UC-002 | FR-001 | N/A | Actualizar Cliente | PUT | `/api/v1/customers/{id}` | Command | required | `admin.masters.maintain` | N/A | REQUIRED | `admin.masters.changed` | TBD | Cliente actualizado | validation | |
| API-MAST-008 | Master Data | BO | ACT-007 | UC-002 | FR-001 | N/A | Eliminar Cliente | DELETE | `/api/v1/customers/{id}` | Command | required | `admin.masters.maintain` | N/A | REQUIRED | `admin.masters.changed` | TBD | Cliente eliminado | domain_conflict | |
| API-MAST-009 | Master Data | BO | ACT-007 | UC-002 | FR-001 | N/A | CRUD Warehouses | ANY | `/api/v1/warehouses` | Command/Query | required | `admin.masters.maintain` | N/A | REQUIRED | `admin.masters.changed` | TBD | Operación exitosa | validation | Expandir a endpoints CRUD individuales. |
| API-MAST-010 | Master Data | BO | ACT-007 | UC-002 | FR-001 | N/A | CRUD Zones | ANY | `/api/v1/zones` | Command/Query | required | `admin.masters.maintain` | N/A | REQUIRED | `admin.masters.changed` | TBD | Operación exitosa | validation | Expandir a endpoints CRUD individuales. |
| API-MAST-011 | Master Data | BO | ACT-007 | UC-002 | FR-001 | N/A | CRUD Locations | ANY | `/api/v1/locations` | Command/Query | required | `admin.masters.maintain` | N/A | REQUIRED | `admin.masters.changed` | TBD | Operación exitosa | validation | Expandir a endpoints CRUD individuales. |
| API-INV-001 | Inventory | MOB | ACT-002 | UC-003 | FR-002 | BR-004 | Recibir Mercancía (Receipt) | POST | `/api/v1/reception/asn/{id}/receive` | Command | required | `warehouse.receive` | N/A | REQUIRED | `inventory.asn.received` | TBD | Mercancía en recepción | validation, domain_conflict | UNRES-002 (origen de ASN) |
| API-INV-002 | Inventory | MOB | ACT-002 | UC-004 | FR-003 | N/A | Ejecutar Put-away | POST | `/api/v1/inventory/put-away` | Command | required | `warehouse.putaway` | N/A | REQUIRED | `inventory.putaway.completed` | TBD | Mercancía reubicada | domain_conflict | |
| API-INV-003 | Inventory | BO | ACT-003 | UC-005 | FR-004 | BR-003, BR-004 | Consultar Inventario | GET | `/api/v1/inventory` | Query | required | `inventory.read` | N/A | NOT_REQUIRED | N/A | not required | Lista de InventoryItem | validation | Requiere pagination/filtering (TBD). |
| API-INV-004 | Inventory | BO | ACT-003 | UC-006 | FR-005 | N/A | Ajustar Inventario | POST | `/api/v1/inventory/adjust` | Command | required | `inventory.adjust` | N/A | REQUIRED | `inventory.adjustment.applied` | required | Inventario ajustado | domain_conflict | Requiere justificación. |
| API-INV-005 | Inventory | BO | ACT-003 | UC-006 | FR-005 | N/A | Mover Inventario | POST | `/api/v1/inventory/move` | Command | required | `inventory.adjust` | N/A | REQUIRED | `TBD — audit catalog gap` | required | Stock reubicado lógicamente | domain_conflict | |
| API-INV-006 | Inventory | BO | ACT-003 | UC-006 | FR-005 | BR-004 | Bloquear Ubicación | POST | `TBD` | Command | required | `inventory.location.block` | N/A | REQUIRED | `inventory.location.blocked` | required | Ubicación bloqueada | domain_conflict | TBD: Path de bloqueo exacto no definido. |
| API-ORD-001 | Commercial Orders | CP | ACT-001 | UC-007 | FR-007 | N/A | Consultar Catálogo | GET | `/api/v1/catalog` | Query | required | `catalog.read` | N/A | NOT_REQUIRED | N/A | not required | Lista de productos elegibles | N/A | |
| API-ORD-002 | Commercial Orders | CP | ACT-001 | UC-007 | FR-007 | N/A | Crear Pedido | POST | `/api/v1/orders` | Command | required | `customer.order.create` | CustomerID | REQUIRED | `TBD — audit catalog gap` | TBD | Pedido pendiente validación | validation | Ownership forzada por backend. |
| API-ORD-003 | Commercial Orders | BO | ACT-005 | UC-008 | FR-008 | BR-009 | Confirmar Pedido | POST | `/api/v1/orders/{id}/confirm` | Command | required | `commercial.order.approve` | N/A | REQUIRED | `TBD — audit catalog gap` | TBD | Pedido comercialmente validado | domain_conflict | |
| API-FUL-001 | Fulfillment | BO / System | ACT-003 / Sys | UC-009 | FR-006 | BR-001, BR-002, BR-003 | Reservar Inventario (Allocate) | POST | `/api/v1/orders/{id}/allocate` | Command | required | `TBD` | N/A | REQUIRED | `fulfillment.allocation.created` | required | Reservas incrementadas, OnHand intacto | concurrency_conflict | Capability TBD (gap en A2). UNRES-003: triggering mode TBD. |
| API-FUL-002 | Fulfillment | MOB | ACT-002 | UC-010 | FR-009 | N/A | Ejecutar Picking | POST | `/api/v1/fulfillment/picking/confirm` | Command | required | `warehouse.picking` | N/A | REQUIRED | `TBD — audit catalog gap` | TBD | Mercancía recolectada, OnHand intacto | domain_conflict | Tareas offline aplican acá (Sync). |
| API-FUL-003 | Fulfillment | MOB | ACT-002 | UC-011 | FR-010 | N/A | Ejecutar Packing | POST | `/api/v1/fulfillment/packing/confirm` | Command | required | `warehouse.packing` | N/A | REQUIRED | `TBD — audit catalog gap` | TBD | Bultos generados, OnHand intacto | domain_conflict | |
| API-SHP-001 | Shipping / Dispatch | BO | ACT-004 | UC-012 | FR-011 | N/A | Planificar Envío | POST | `/api/v1/shipping/plans` | Command | required | `dispatch.plan` | N/A | REQUIRED | `TBD — audit catalog gap` | TBD | Plan de envío consolidado | validation | |
| API-SHP-002 | Shipping / Dispatch | BO | ACT-004 | UC-013 | FR-012, FR-013 | BR-005 | Confirmar Despacho (Dispatch) | POST | `/api/v1/shipping/dispatch/confirm` | Command | required | `dispatch.confirm` | N/A | REQUIRED | `dispatch.confirmed`, `finance.obligation.created` | required | OnHand deducido, Obligación generada | domain_conflict, idempotency_conflict | Operación atómica estricta cross-module. |
| API-FIN-001 | Finance | BO | ACT-006 | UC-014 | FR-016 | BR-005 | Consultar Cartera (AR) | GET | `/api/v1/finance/receivables` | Query | required | `finance.receivables.read` | N/A | NOT_REQUIRED | N/A | not required | Lista de obligaciones (vista interna) | N/A | Requiere pagination/filtering (TBD). |
| API-FIN-002 | Finance | BO | ACT-006 | UC-015 | FR-014 | BR-006 | Registrar Pago Recibido | POST | `/api/v1/finance/payments` | Command | required | `finance.payment.register` | N/A | REQUIRED | `finance.payment.registered` | required | Saldo a favor registrado | validation | Operación separada de Apply. |
| API-FIN-003 | Finance | BO | ACT-006 | UC-016 | FR-015 | BR-007 | Aplicar Pago a Deuda | POST | `/api/v1/finance/payments/{id}/apply` | Command | required | `finance.payment.apply` | N/A | REQUIRED | `finance.payment.applied`, `finance.obligation.closed` (condicional) | required | Deuda reducida/saldada | domain_conflict, invariant_violation | Transaccional. Obligation closed solo si el saldo llega a 0. |
| API-FIN-004 | Finance | CP | ACT-001 | UC-017 | FR-017 | BR-005 | Consultar Obligaciones (AP) | GET | `/api/v1/finance/payables` | Query | required | `customer.finance.read` | CustomerID | NOT_REQUIRED | N/A | not required | Lista de obligaciones (vista externa) | N/A | Ownership forzada por backend. |
| API-FIN-005 | Finance | BO | ACT-006 | UC-018 | FR-018 | N/A | Estado de Cuenta (Global) | GET | `/api/v1/finance/statements/{customerId}` | Query | required | `finance.receivables.read` | N/A | NOT_REQUIRED | N/A | not required | Historial consolidado del cliente | resource_not_found | |
| API-FIN-006 | Finance | CP | ACT-001 | UC-018 | FR-018 | N/A | Estado de Cuenta Propio | GET | `/api/v1/finance/statement` | Query | required | `customer.finance.read` | CustomerID | NOT_REQUIRED | N/A | not required | Historial consolidado propio | N/A | Ownership forzada. |
| API-SYNC-001 | System | MOB | ACT-002 | UC-020 | FR-021 | BR-008, BR-010 | Sincronizar Operaciones | POST | `/api/v1/sync` | Command | required | `TBD / Re-auth per operation` | N/A | REQUIRED | `sync.operation.applied`, `sync.operation.rejected`, `sync.conflict.generated`, `sync.authorization.failed` | required per applied operation | Batch de operaciones offline procesado | idempotency_conflict, domain_conflict | Servidor es SSOT. Eventos son outcome-dependent. |

## 3. Notas Adicionales y Unresolved Needs

- Las capabilities exactas son mandatorias de A2 `EVD-ARCH-SEC-001`.
- En `API-ORD-002`, `API-FIN-004`, `API-FIN-006` el ownership check se realizará obligatoriamente del lado del backend (Customer Portal no especifica un customer_id en la ruta para prevenir enumeración/BOLA).
- En `API-SHP-002` (Confirm Dispatch), la transacción debe asegurar el descuento definitivo del `OnHand`, la generación de eventos de auditoría y la creación de la obligación en una sola operación atómica.
- Se ha separado claramente `API-FIN-002` (Register Payment) y `API-FIN-003` (Apply Payment) según mandato estricto.
- Se deja pendiente la definición exacta de Pagination, Filtering, y Sorting para endpoints Query (GET) para una iteración posterior.
- **TBD/gap - Allocation Capability:** A2 no especifica la capability explícita para Allocation. Registrado como gap contractual.
- **TBD/gap - Sync Capability:** No se define un permiso global; cada mutación offline debe reautorizarse contra la capability de la operación original.
- **TBD/gap - Audit Catalog Gaps:** Las operaciones de Pedido Comercial, Picking, Packing y Shipping Plan no tienen un evento explícito en `EVD-ARCH-AUDIT-001`. Se registran como `TBD — audit catalog gap`.
- **Sync Semantics:** `/sync` puede actuar como envelope/batch de transporte; cada operación offline se reautentica y reautoriza individualmente, aplica su propia transaction boundary y su estrategia de concurrency. Un conflicto no inventa atomicidad global. Eventos de auditoría en Sync son outcome-dependent y no todos se generan para una misma operación.
