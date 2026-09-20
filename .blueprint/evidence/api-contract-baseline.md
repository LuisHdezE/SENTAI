# SENTAI - API Contract Baseline
**Blueprint Phase:** API Contract Design
**Status:** DRAFT (Baseline Inventory)
**Artifact ID:** EVD-API-002
**Type:** api_contract_baseline_evidence

Este documento define el inventario inicial del contrato API derivado estrictamente del Product Truth y Architecture Truth aprobados, incluyendo: EVD-REQ-001, EVD-ARCH-001, EVD-UI-SCOPE-001, EVD-ARCH-SEC-001, EVD-ARCH-TXN-001, EVD-ARCH-AUDIT-001 y EVD-ARCH-API-001. NO define implementación.
Reconciliado en C2 con EVD-API-003 y EVD-API-004.

## 1. Alcance General

- **Base Path:** `/api/v1/`
- **Error Contract:** RFC 9457 Problem Details (validation, domain_conflict, idempotency_conflict, etc.)
- **Idempotency:** Según requerimiento para mutaciones críticas operacionales o financieras.

## 2. Inventario de Operaciones

| Contract ID | Module | Surface | Actor | UC | FR | BR | Intent | HTTP Method | Path | Command/Query | Authentication | Capability | Ownership | Idempotency | Audit Event | Transaction | Main Success | Main Errors | Notes / unresolved |
|---|---|---|---|---|---|---|---|---|---|---|---|---|---|---|---|---|---|---|---|
| API-AUTH-001 | Identity | BO, CP | Todos | UC-001 | FR-022 | N/A | Web Login | POST | `/api/v1/auth/web/login` | Command | pre-auth | N/A | N/A | NOT_REQUIRED | `auth.login.success`, `auth.login.failure` | not required | Sesión backend creada | authentication | Backend-managed session |
| API-AUTH-002 | Identity | BO, CP | Todos | UC-001 | FR-022 | N/A | Web Logout | POST | `/api/v1/auth/web/logout` | Command | required | N/A | N/A | NOT_REQUIRED | `auth.logout` | not required | Sesión invalidada | authentication | Purga cookies |
| API-AUTH-003 | Identity | BO, CP | Todos | UC-001 | FR-022 | N/A | Web CSRF | GET | `/api/v1/auth/web/csrf` | Query | pre-auth | N/A | N/A | NOT_REQUIRED | N/A | not required | Material CSRF listo | authentication | |
| API-AUTH-004 | Identity | MOB | ACT-002 | UC-001 | FR-022 | N/A | Mobile Login | POST | `/api/v1/auth/mobile/login` | Command | pre-auth | N/A | N/A | NOT_REQUIRED | `auth.login.success`, `auth.login.failure` | not required | Access + Refresh token | authentication | |
| API-AUTH-005 | Identity | MOB | ACT-002 | UC-001 | FR-022 | N/A | Mobile Refresh | POST | `/api/v1/auth/mobile/refresh` | Command | pre-auth | N/A | N/A | NOT_REQUIRED | N/A | not required | Nuevos tokens emitidos | authentication | Requiere refresh_token válido |
| API-AUTH-006 | Identity | MOB | ACT-002 | UC-001 | FR-022 | N/A | Mobile Logout | POST | `/api/v1/auth/mobile/logout` | Command | required | N/A | N/A | NOT_REQUIRED | `auth.logout` | not required | Sesión/tokens revocados | authentication | |
| API-MAST-001 | Master Data | BO | ACT-007 | UC-002 | FR-001 | N/A | Listar Productos | GET | `/api/v1/products` | Query | required | `admin.masters.maintain` | N/A | NOT_REQUIRED | N/A | not required | Lista de Productos | validation | |
| API-MAST-002 | Master Data | BO | ACT-007 | UC-002 | FR-001 | N/A | Crear Producto | POST | `/api/v1/products` | Command | required | `admin.masters.maintain` | N/A | REQUIRED | `admin.masters.changed` | TBD | Producto creado | validation | |
| API-MAST-003 | Master Data | BO | ACT-007 | UC-002 | FR-001 | N/A | Actualizar Producto | PUT | `/api/v1/products/{id}` | Command | required | `admin.masters.maintain` | N/A | REQUIRED | `admin.masters.changed` | TBD | Producto actualizado | validation | |
| API-MAST-004 | Master Data | BO | ACT-007 | UC-002 | FR-001 | N/A | Listar Clientes | GET | `/api/v1/customers` | Query | required | `admin.masters.maintain` | N/A | NOT_REQUIRED | N/A | not required | Lista de Clientes | validation | |
| API-MAST-005 | Master Data | BO | ACT-007 | UC-002 | FR-001 | N/A | Crear Cliente | POST | `/api/v1/customers` | Command | required | `admin.masters.maintain` | N/A | REQUIRED | `admin.masters.changed` | TBD | Cliente creado | validation | |
| API-MAST-006 | Master Data | BO | ACT-007 | UC-002 | FR-001 | N/A | Actualizar Cliente | PUT | `/api/v1/customers/{id}` | Command | required | `admin.masters.maintain` | N/A | REQUIRED | `admin.masters.changed` | TBD | Cliente actualizado | validation | |
| API-MAST-007 | Master Data | BO | ACT-007 | UC-002 | FR-001 | N/A | Listar Warehouses | GET | `/api/v1/masters/warehouses` | Query | required | `admin.masters.maintain` | N/A | NOT_REQUIRED | N/A | not required | Lista | validation | |
| API-MAST-008 | Master Data | BO | ACT-007 | UC-002 | FR-001 | N/A | Crear Warehouse | POST | `/api/v1/masters/warehouses` | Command | required | `admin.masters.maintain` | N/A | REQUIRED | `admin.masters.changed` | TBD | Creado | validation | |
| API-MAST-009 | Master Data | BO | ACT-007 | UC-002 | FR-001 | N/A | Actualizar Warehouse | PUT | `/api/v1/masters/warehouses/{id}` | Command | required | `admin.masters.maintain` | N/A | REQUIRED | `admin.masters.changed` | TBD | Actualizado | validation | |
| API-MAST-010 | Master Data | BO | ACT-007 | UC-002 | FR-001 | N/A | Listar Zones | GET | `/api/v1/masters/zones` | Query | required | `admin.masters.maintain` | N/A | NOT_REQUIRED | N/A | not required | Lista | validation | |
| API-MAST-011 | Master Data | BO | ACT-007 | UC-002 | FR-001 | N/A | Crear Zone | POST | `/api/v1/masters/zones` | Command | required | `admin.masters.maintain` | N/A | REQUIRED | `admin.masters.changed` | TBD | Creado | validation | |
| API-MAST-012 | Master Data | BO | ACT-007 | UC-002 | FR-001 | N/A | Actualizar Zone | PUT | `/api/v1/masters/zones/{id}` | Command | required | `admin.masters.maintain` | N/A | REQUIRED | `admin.masters.changed` | TBD | Actualizado | validation | |
| API-MAST-013 | Master Data | BO | ACT-007 | UC-002 | FR-001 | N/A | Listar Locations | GET | `/api/v1/masters/locations` | Query | required | `admin.masters.maintain` | N/A | NOT_REQUIRED | N/A | not required | Lista | validation | |
| API-MAST-014 | Master Data | BO | ACT-007 | UC-002 | FR-001 | N/A | Crear Location | POST | `/api/v1/masters/locations` | Command | required | `admin.masters.maintain` | N/A | REQUIRED | `admin.masters.changed` | TBD | Creado | validation | |
| API-MAST-015 | Master Data | BO | ACT-007 | UC-002 | FR-001 | N/A | Actualizar Location | PUT | `/api/v1/masters/locations/{id}` | Command | required | `admin.masters.maintain` | N/A | REQUIRED | `admin.masters.changed` | TBD | Actualizado | validation | |
| API-ADM-001 | Admin | BO | ACT-007 | UC-002 | FR-022 | N/A | Listar Usuarios | GET | `/api/v1/admin/users` | Query | required | `admin.identity.manage` | N/A | NOT_REQUIRED | N/A | not required | Lista de usuarios | validation | |
| API-ADM-002 | Admin | BO | ACT-007 | UC-002 | FR-022 | N/A | Crear Usuario | POST | `/api/v1/admin/users` | Command | required | `admin.identity.manage` | N/A | REQUIRED | `admin.user.created` | required | Usuario creado | validation | |
| API-ADM-003 | Admin | BO | ACT-007 | UC-002 | FR-022 | N/A | Actualizar Usuario | PUT | `/api/v1/admin/users/{id}` | Command | required | `admin.identity.manage` | N/A | REQUIRED | `admin.user.updated` | required | Usuario actualizado | validation | |
| API-ADM-004 | Admin | BO | ACT-007 | UC-002 | FR-022 | N/A | Desactivar Usuario | PUT | `/api/v1/admin/users/{id}/disable` | Command | required | `admin.identity.manage` | N/A | REQUIRED | `admin.user.disabled` | required | Usuario desactivado | validation | |
| API-ADM-005 | Admin | BO | ACT-007 | UC-002 | FR-022 | N/A | Asignar Rol | POST | `/api/v1/admin/users/{userId}/roles` | Command | required | `admin.roles.manage` | N/A | REQUIRED | `authz.role.assigned` | required | Rol asignado | validation | |
| API-ADM-006 | Admin | BO | ACT-007 | UC-002 | FR-022 | N/A | Revocar Rol | DELETE | `/api/v1/admin/users/{userId}/roles/{roleId}` | Command | required | `admin.roles.manage` | N/A | REQUIRED | `authz.role.revoked` | required | Rol revocado | domain_conflict | |
| API-AUDIT-001 | Audit | BO | Interno | N/A | FR-019 | N/A | Global Audit | GET | `/api/v1/audit/events` | Query | required | `audit.global.read` | N/A | NOT_REQUIRED | `authz.audit.access` | not required | Lista de eventos | validation | Actor: AuditViewer |
| API-INV-001 | Inventory | MOB | ACT-002 | UC-003 | FR-002 | BR-004 | Recibir Mercancía (Receipt) | POST | `/api/v1/reception/asn/{id}/receive` | Command | required | `warehouse.receive` | N/A | REQUIRED | `inventory.asn.received` | required | Mercancía en recepción | validation, domain_conflict | UNRES-002 (origen de ASN) |
| API-INV-002 | Inventory | MOB | ACT-002 | UC-004 | FR-003 | N/A | Ejecutar Put-away | POST | `/api/v1/inventory/put-away` | Command | required | `warehouse.putaway` | N/A | REQUIRED | `inventory.putaway.completed` | required | Mercancía reubicada | domain_conflict | |
| API-INV-003 | Inventory | BO | ACT-003 | UC-005 | FR-004 | BR-003, BR-004 | Consultar Inventario | GET | `/api/v1/inventory` | Query | required | `inventory.read` | N/A | NOT_REQUIRED | N/A | not required | Lista de InventoryItem | validation | Requiere pagination/filtering. |
| API-INV-004 | Inventory | BO | ACT-003 | UC-006 | FR-005 | N/A | Ajustar Inventario | POST | `/api/v1/inventory/adjust` | Command | required | `inventory.adjust` | N/A | REQUIRED | `inventory.adjustment.applied` | required | Inventario ajustado | domain_conflict | |
| API-INV-005 | Inventory | BO | ACT-003 | UC-006 | FR-005 | N/A | Mover Inventario | POST | `/api/v1/inventory/move` | Command | required | `inventory.adjust` | N/A | REQUIRED | `TBD — audit catalog gap` | required | Stock reubicado lógicamente | domain_conflict | |
| API-INV-006 | Inventory | BO | ACT-003 | UC-006 | FR-005 | BR-004 | Bloquear Ubicación | POST | `/api/v1/locations/{id}/block` | Command | required | `inventory.location.block` | N/A | REQUIRED | `inventory.location.blocked` | required | Ubicación bloqueada | domain_conflict | |
| API-INV-007 | Inventory | BO | ACT-003 | UC-006 | FR-005 | BR-004 | Desbloquear Ubicación | POST | `/api/v1/locations/{id}/unblock` | Command | required | `inventory.location.block` | N/A | REQUIRED | `inventory.location.unblocked` | required | Ubicación desbloqueada | domain_conflict | |
| API-ORD-001 | Orders | CP | ACT-001 | UC-007 | FR-007 | N/A | Consultar Catálogo | GET | `/api/v1/catalog` | Query | required | `catalog.read` | N/A | NOT_REQUIRED | N/A | not required | Lista de productos elegibles | N/A | |
| API-ORD-002 | Orders | CP | ACT-001 | UC-007 | FR-007 | N/A | Crear Pedido | POST | `/api/v1/orders` | Command | required | `customer.order.create` | CustomerID | REQUIRED | `TBD — audit catalog gap` | TBD | Pedido pendiente validación | validation | Ownership forzada por backend. |
| API-ORD-003 | Orders | BO | ACT-005 | UC-008 | FR-008 | BR-009 | Confirmar Pedido | POST | `/api/v1/orders/{id}/confirm` | Command | required | `commercial.order.approve` | N/A | REQUIRED | `TBD — audit catalog gap` | TBD | Pedido comercialmente validado | domain_conflict | |
| API-FUL-001 | Fulfillment | BO / System | ACT-003 / Sys | UC-009 | FR-006 | BR-001, BR-002, BR-003 | Reservar Inventario (Allocate) | POST | `/api/v1/orders/{id}/allocate` | Command | required | `TBD` | N/A | REQUIRED | `fulfillment.allocation.created` | required | Reservas incrementadas, OnHand intacto | concurrency_conflict | Capability TBD (gap en A2). |
| API-FUL-002 | Fulfillment | MOB | ACT-002 | UC-010 | FR-009 | N/A | Ejecutar Picking | POST | `/api/v1/fulfillment/picking/confirm` | Command | required | `warehouse.picking` | N/A | REQUIRED | `TBD — audit catalog gap` | TBD | Mercancía recolectada, OnHand intacto | domain_conflict | |
| API-FUL-003 | Fulfillment | MOB | ACT-002 | UC-011 | FR-010 | N/A | Ejecutar Packing | POST | `/api/v1/fulfillment/packing/confirm` | Command | required | `warehouse.packing` | N/A | REQUIRED | `TBD — audit catalog gap` | TBD | Bultos generados, OnHand intacto | domain_conflict | |
| API-SHP-001 | Shipping | BO | ACT-004 | UC-012 | FR-011 | N/A | Planificar Envío | POST | `/api/v1/shipping/plans` | Command | required | `dispatch.plan` | N/A | REQUIRED | `TBD — audit catalog gap` | TBD | Plan de envío consolidado | validation | |
| API-SHP-002 | Shipping | BO | ACT-004 | UC-013 | FR-012, FR-013 | BR-005 | Confirmar Despacho (Dispatch) | POST | `/api/v1/shipping/dispatch/confirm` | Command | required | `dispatch.confirm` | N/A | REQUIRED | `dispatch.confirmed`, `finance.obligation.created` | required | OnHand deducido, Obligación generada | domain_conflict, idempotency_conflict | Operación atómica estricta cross-module. |
| API-FIN-001 | Finance | BO | ACT-006 | UC-014 | FR-016 | BR-005 | Consultar Cartera (AR) | GET | `/api/v1/finance/receivables` | Query | required | `finance.receivables.read` | N/A | NOT_REQUIRED | N/A | not required | Lista de obligaciones (vista interna) | N/A | Requiere pagination/filtering. |
| API-FIN-002 | Finance | BO | ACT-006 | UC-015 | FR-014 | BR-006 | Registrar Pago Recibido | POST | `/api/v1/finance/payments` | Command | required | `finance.payment.register` | N/A | REQUIRED | `finance.payment.registered` | required | Saldo a favor registrado | validation | Operación separada de Apply. |
| API-FIN-003 | Finance | BO | ACT-006 | UC-016 | FR-015 | BR-007 | Aplicar Pago a Deuda | POST | `/api/v1/finance/payments/{id}/apply` | Command | required | `finance.payment.apply` | N/A | REQUIRED | `finance.payment.applied`, `finance.obligation.closed` | required | Deuda reducida/saldada | domain_conflict, invariant_violation | Transaccional. Obligation closed solo si el saldo = 0. |
| API-FIN-004 | Finance | CP | ACT-001 | UC-017 | FR-017 | BR-005 | Consultar Obligaciones (AP) | GET | `/api/v1/finance/payables` | Query | required | `customer.finance.read` | CustomerID | NOT_REQUIRED | N/A | not required | Lista de obligaciones (vista externa) | N/A | Ownership forzada por backend. |
| API-FIN-005 | Finance | BO | ACT-006 | UC-018 | FR-018 | N/A | Estado de Cuenta (Global) | GET | `/api/v1/finance/statements/{customerId}` | Query | required | `finance.receivables.read` | N/A | NOT_REQUIRED | N/A | not required | Historial consolidado del cliente | resource_not_found | |
| API-FIN-006 | Finance | CP | ACT-001 | UC-018 | FR-018 | N/A | Estado de Cuenta Propio | GET | `/api/v1/finance/statement` | Query | required | `customer.finance.read` | CustomerID | NOT_REQUIRED | N/A | not required | Historial consolidado propio | N/A | Ownership forzada. |
| API-SYNC-001 | System | MOB | ACT-002 | UC-020 | FR-021 | BR-008, BR-010 | Sincronizar Operaciones | POST | `/api/v1/sync` | Command | required | Re-auth per operation | N/A | REQUIRED | `sync.operation.applied`, `sync.operation.rejected`, `sync.conflict.generated`, `sync.authorization.failed` | required per operation | Batch de operaciones offline procesado | idempotency_conflict, domain_conflict | Servidor es SSOT. Eventos son outcome-dependent. |

## 3. Gaps Contractuales y Bloqueantes (UNRESOLVED_CONTRACT_GAP)

- **`customer.order.read`**: Operaciones de lectura de pedidos (`GET /orders`) bloqueadas por indefinición de funcionalidad de tracking (UNRES-001).
- **`commercial.customer.maintain`**: Operación `PUT commercial-terms` bloqueada por indefinición de los términos comerciales en FR-008.
- **Mobile Offline Tasks**: Endpoints para descarga de trabajo offline bloqueados por carecer de modelo de recolección heterogénea definido en A1.
- **Offline Conflicts API**: La lectura a demanda de conflictos (`GET /api/v1/sync/conflicts`) está bloqueada al no poseer capability canónica ni requerimiento formal. `/sync` comunicará conflictos inline.
- **Master Data Deletion**: Todo requerimiento de borrado de Master Data carece de justificación documental aprobada.
- **TBD/gap - Allocation Capability:** A2 no especifica la capability explícita para Allocation.
- **TBD/gap - Audit Catalog Gaps:** Las operaciones de Pedido Comercial, Picking, Packing y Shipping Plan no tienen un evento explícito en `EVD-ARCH-AUDIT-001`.

## 4. Notas Adicionales

- En `API-ORD-002`, `API-FIN-004`, `API-FIN-006` el ownership check se realizará obligatoriamente del lado del backend.
- En `API-SHP-002` (Confirm Dispatch), la transacción asegura el descuento definitivo del `OnHand`, la generación de eventos de auditoría y la creación de la obligación en una sola operación atómica.
- Se deja pendiente la definición exacta de Pagination, Filtering, y Sorting para endpoints Query (GET) para una iteración posterior.
