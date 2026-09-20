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
| API-AUTH-001 | Identity & Access | BO, CP, MOB | Todos | UC-001 | FR-022 | N/A | Login / Establecer sesión | POST | `/api/v1/auth/login` | Command | required (credentials) | N/A | N/A | NOT_REQUIRED | auth.login_success | not required | Sesión/Token emitido | authentication | TBD: Estructura exacta payload offline. |
| API-MAST-001 | Master Data | BO | ACT-007 | UC-002 | FR-001 | N/A | Crear Producto | POST | `/api/v1/products` | Command | required | `masters:write` | N/A | REQUIRED | master.product_created | required | Producto creado | validation | |
| API-MAST-002 | Master Data | BO | ACT-007 | UC-002 | FR-001 | N/A | Crear Cliente | POST | `/api/v1/customers` | Command | required | `masters:write` | N/A | REQUIRED | master.customer_created | required | Cliente creado | validation | |
| API-INV-001 | Inventory | MOB | ACT-002 | UC-003 | FR-002 | BR-004 | Recibir Mercancía (Receipt) | POST | `/api/v1/reception/asn/{id}/receive` | Command | required | `inventory:receive` | N/A | REQUIRED | inventory.receipt_confirmed | required | Mercancía en recepción | validation, domain_conflict | UNRES-002 (origen de ASN) |
| API-INV-002 | Inventory | MOB | ACT-002 | UC-004 | FR-003 | N/A | Ejecutar Put-away | POST | `/api/v1/inventory/put-away` | Command | required | `inventory:putaway` | N/A | REQUIRED | inventory.putaway_confirmed | required | Mercancía reubicada | domain_conflict | |
| API-INV-003 | Inventory | BO | ACT-003 | UC-005 | FR-004 | BR-003, BR-004 | Consultar Inventario | GET | `/api/v1/inventory` | Query | required | `inventory:read` | N/A | NOT_REQUIRED | N/A | not required | Lista de InventoryItem | validation | Requiere pagination/filtering (TBD). |
| API-INV-004 | Inventory | BO | ACT-003 | UC-006 | FR-005 | N/A | Ajustar Inventario | POST | `/api/v1/inventory/adjust` | Command | required | `inventory:adjust` | N/A | REQUIRED | inventory.adjustment_applied | required | Inventario ajustado | domain_conflict | Requiere justificación. |
| API-ORD-001 | Commercial Orders | CP | ACT-001 | UC-007 | FR-007 | N/A | Consultar Catálogo | GET | `/api/v1/catalog` | Query | required | `catalog:read` | N/A | NOT_REQUIRED | N/A | not required | Lista de productos elegibles | N/A | |
| API-ORD-002 | Commercial Orders | CP | ACT-001 | UC-007 | FR-007 | N/A | Crear Pedido | POST | `/api/v1/orders` | Command | required | `orders:create_own` | CustomerID | REQUIRED | order.created | required | Pedido pendiente validación | validation | Ownership forzada por backend. |
| API-ORD-003 | Commercial Orders | BO | ACT-005 | UC-008 | FR-008 | BR-009 | Confirmar Pedido | POST | `/api/v1/orders/{id}/confirm` | Command | required | `orders:confirm` | N/A | REQUIRED | order.confirmed | required | Pedido comercialmente validado | domain_conflict | |
| API-FUL-001 | Fulfillment | BO / System | ACT-003 / Sys | UC-009 | FR-006 | BR-001, BR-002, BR-003 | Reservar Inventario (Allocate) | POST | `/api/v1/orders/{id}/allocate` | Command | required | `fulfillment:allocate` | N/A | REQUIRED | fulfillment.allocation_applied | required | Reservas incrementadas, OnHand intacto | concurrency_conflict | UNRES-003: triggering mode TBD. |
| API-FUL-002 | Fulfillment | MOB | ACT-002 | UC-010 | FR-009 | N/A | Ejecutar Picking | POST | `/api/v1/fulfillment/picking/confirm` | Command | required | `fulfillment:picking` | N/A | REQUIRED | fulfillment.picking_confirmed | required | Mercancía recolectada, OnHand intacto | domain_conflict | Tareas offline aplican acá (Sync). |
| API-FUL-003 | Fulfillment | MOB | ACT-002 | UC-011 | FR-010 | N/A | Ejecutar Packing | POST | `/api/v1/fulfillment/packing/confirm` | Command | required | `fulfillment:packing` | N/A | REQUIRED | fulfillment.packing_confirmed | required | Bultos generados, OnHand intacto | domain_conflict | |
| API-SHP-001 | Shipping / Dispatch | BO | ACT-004 | UC-012 | FR-011 | N/A | Planificar Envío | POST | `/api/v1/shipping/plans` | Command | required | `shipping:plan` | N/A | REQUIRED | shipping.plan_created | required | Plan de envío consolidado | validation | |
| API-SHP-002 | Shipping / Dispatch | BO | ACT-004 | UC-013 | FR-012, FR-013 | BR-005 | Confirmar Despacho (Dispatch) | POST | `/api/v1/shipping/dispatch/confirm` | Command | required | `shipping:dispatch` | N/A | REQUIRED | shipping.dispatch_confirmed | required | OnHand deducido, Obligación generada | domain_conflict, idempotency_conflict | Operación atómica estricta cross-module. |
| API-FIN-001 | Finance | BO | ACT-006 | UC-014 | FR-016 | BR-005 | Consultar Cartera (AR) | GET | `/api/v1/finance/receivables` | Query | required | `finance:read_ar` | N/A | NOT_REQUIRED | N/A | not required | Lista de obligaciones (vista interna) | N/A | Requiere pagination/filtering (TBD). |
| API-FIN-002 | Finance | BO | ACT-006 | UC-015 | FR-014 | BR-006 | Registrar Pago Recibido | POST | `/api/v1/finance/payments` | Command | required | `finance:register_payment` | N/A | REQUIRED | finance.payment_registered | required | Saldo a favor registrado | validation | Operación separada de Apply. |
| API-FIN-003 | Finance | BO | ACT-006 | UC-016 | FR-015 | BR-007 | Aplicar Pago a Deuda | POST | `/api/v1/finance/payments/{id}/apply` | Command | required | `finance:apply_payment` | N/A | REQUIRED | finance.payment_applied | required | Deuda reducida/saldada | domain_conflict, invariant_violation | Transaccional contra la obligación. |
| API-FIN-004 | Finance | CP | ACT-001 | UC-017 | FR-017 | BR-005 | Consultar Obligaciones (AP) | GET | `/api/v1/finance/payables` | Query | required | `finance:read_own_ap` | CustomerID | NOT_REQUIRED | N/A | not required | Lista de obligaciones (vista externa) | N/A | Ownership forzada por backend. |
| API-FIN-005 | Finance | BO | ACT-006 | UC-018 | FR-018 | N/A | Estado de Cuenta (Global) | GET | `/api/v1/finance/statements/{customerId}` | Query | required | `finance:read_statement` | N/A | NOT_REQUIRED | N/A | not required | Historial consolidado del cliente | resource_not_found | |
| API-FIN-006 | Finance | CP | ACT-001 | UC-018 | FR-018 | N/A | Estado de Cuenta Propio | GET | `/api/v1/finance/statement` | Query | required | `finance:read_own_statement`| CustomerID | NOT_REQUIRED | N/A | not required | Historial consolidado propio | N/A | Ownership forzada. |
| API-SYNC-001 | System | MOB | ACT-002 | UC-020 | FR-021 | BR-008, BR-010 | Sincronizar Operaciones | POST | `/api/v1/sync` | Command | required | `system:sync` | N/A | REQUIRED | system.sync_processed | required | Batch de operaciones offline procesado | idempotency_conflict, domain_conflict | Servidor es SSOT. Fallos = Conflictos. |

## 3. Notas Adicionales y Unresolved Needs

- Las capacidades exactas (`masters:write`, `inventory:receive`) son propuestas conceptuales alineadas al modelo de seguridad por capacidades.
- En `API-ORD-002`, `API-FIN-004`, `API-FIN-006` el ownership check se realizará obligatoriamente del lado del backend (Customer Portal no especifica un customer_id en la ruta para prevenir enumeración/BOLA).
- En `API-SHP-002` (Confirm Dispatch), la transacción debe asegurar el descuento definitivo del `OnHand`, la generación del audit event, y la creación de la obligación financiera de cobro (Finance) en una sola operación atómica.
- Se ha separado claramente `API-FIN-002` (Register Payment) y `API-FIN-003` (Apply Payment) según mandato estricto.
- Se deja pendiente la definición exacta de Pagination, Filtering, y Sorting para endpoints Query (GET) para una iteración posterior.
