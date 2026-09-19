# Architecture Domain Model
**Artifact ID:** EVD-ARCH-001
**Status:** READY_FOR_REVIEW

Este documento establece el Domain Model y las directrices iniciales para la arquitectura del sistema, derivado estrictamente de EVD-REQ-001 (Requirements & Domain).

## 1. Ubiquitous Language

Los siguientes términos forman el lenguaje ubicuo del dominio, sustentados por los requerimientos:

- **Product**: Artículo físico o unidad gestionada en el inventario.
- **Customer**: Entidad cliente propietaria o destinataria de los productos/órdenes.
- **Warehouse**: Instalación física donde se almacena el inventario.
- **Zone**: Área lógica o física dentro de un almacén.
- **Location**: Ubicación específica dentro de una zona.
- **ASN** (Advanced Shipping Notice): Aviso anticipado de envío para la recepción de mercancía.
- **Receipt**: Proceso y registro de recepción física de mercancía.
- **Put-away**: Proceso de ubicar la mercancía recibida en una Location definitiva.
- **Inventory**: El conjunto global de stock gestionado.
- **Stock**: Existencias físicas de un producto.
- **OnHand**: Cantidad física total disponible en el almacén.
- **Reserved**: Cantidad de inventario bloqueada o comprometida para órdenes.
- **Available**: Cantidad de inventario libre (OnHand - Reserved).
- **Allocation**: Proceso de reservar inventario específico para una Customer Order.
- **Customer Order**: Pedido realizado por un cliente que requiere preparación y envío.
- **Picking**: Proceso de recolectar el inventario asignado desde sus ubicaciones.
- **Packing**: Proceso de embalaje del inventario recolectado.
- **Package**: Unidad embalada y lista para despacho.
- **Shipping Plan**: Planificación logística de envíos.
- **Dispatch**: Despacho físico de los paquetes hacia su destino.
- **Financial Obligation**: Deuda u obligación financiera generada.
- **Accounts Receivable**: Vista de las obligaciones financieras desde el Backoffice (cuentas por cobrar).
- **Accounts Payable**: Vista de las obligaciones financieras desde el Customer Portal (cuentas por pagar).
- **Payment**: Transacción de pago registrada.
- **Payment Application**: Aplicación de un pago parcial o total a una o múltiples obligaciones financieras.
- **Audit Event**: Registro inmutable de una acción auditable en el sistema.
- **Offline Operation**: Operación ejecutada en dispositivos móviles sin conexión.
- **Sync Conflict**: Conflicto generado al sincronizar operaciones offline con un estado base modificado.

## 2. Candidate Module Map

Se proponen los siguientes módulos cohesivos dentro del Modular Monolith:

### Identity & Access
- **Purpose**: Autenticación, gestión de sesiones y resolución de permisos.
- **Owned concepts**: User, Role, Permission, Session.
- **Principal FR/UC/BR**: FR-022, NFR-001, UC-001.
- **Data ownership**: Credenciales y perfiles de acceso.
- **Permitted dependencies**: Ninguna (fundacional).
- **Prohibited dependencies**: Módulos de negocio.

### Master Data
- **Purpose**: Gestión de datos maestros transversales.
- **Owned concepts**: Product, Customer, Warehouse, Zone, Location.
- **Principal FR/UC/BR**: FR-001, UC-002.
- **Data ownership**: Catálogos centrales.
- **Permitted dependencies**: Identity & Access.
- **Prohibited dependencies**: Inventory, Orders, Finance (los módulos operativos referencian a Master Data, no al revés).

### Inventory / Warehouse Operations
- **Purpose**: Control de existencias físicas y sus movimientos internos.
- **Owned concepts**: Stock, ASN, Receipt, Put-away, Inventory Adjustment.
- **Principal FR/UC/BR**: FR-002, FR-003, FR-004, FR-005, UC-003, UC-004, UC-005, UC-006, BR-001, BR-003, BR-004, FR-019 (cuando corresponda a auditoría de cambios críticos).
- **Data ownership**: Cantidades físicas y movimientos de stock.
- **Permitted dependencies**: Master Data, Identity & Access.
- **Prohibited dependencies**: Finance.

### Commercial Orders
- **Purpose**: Gestión del ciclo de vida de los pedidos del cliente.
- **Owned concepts**: Customer Order.
- **Principal FR/UC/BR**: FR-007, FR-008, UC-007, UC-008, BR-009.
- **Data ownership**: Intención de compra del cliente.
- **Permitted dependencies**: Master Data, Identity & Access.
- **Prohibited dependencies**: Shipping / Dispatch.

### Fulfillment
- **Purpose**: Ejecución operativa de la preparación de órdenes.
- **Owned concepts**: Allocation, Picking, Packing, Package.
- **Principal FR/UC/BR**: FR-006, FR-009, FR-010, UC-009, UC-010, UC-011, BR-001, BR-002, BR-003, BR-004 (cuando corresponda).
- **Data ownership**: Estado de la preparación y reservas lógicas.
- **Permitted dependencies**: Commercial Orders, Inventory, Master Data.
- **Prohibited dependencies**: Finance.

### Shipping / Dispatch
- **Purpose**: Logística de salida y entrega.
- **Owned concepts**: Shipping Plan, Dispatch.
- **Principal FR/UC/BR**: FR-011, FR-012, UC-012, UC-013 (la relación con FR-013 debe documentarse como consecuencia financiera del Dispatch, no confundirse con BR-005).
- **Data ownership**: Planes de carga y confirmaciones de salida.
- **Permitted dependencies**: Fulfillment, Commercial Orders.
- **Prohibited dependencies**: Inventory.

### Finance
- **Purpose**: Gestión de cobros y pagos.
- **Owned concepts**: Financial Obligation (Accounts Receivable/Payable), Payment, Payment Application.
- **Principal FR/UC/BR**: FR-013, FR-014, FR-015, FR-016, FR-017, FR-018, UC-014, UC-015, UC-016, UC-017, UC-018, BR-005, BR-006, BR-007, BR-008 (cuando corresponda), BR-009 (únicamente donde crédito comercial interactúe con Finance/Orders).
- **Data ownership**: Saldos, estados de deuda, historial de pagos aplicados.
- **Permitted dependencies**: Commercial Orders, Master Data.
- **Prohibited dependencies**: Fulfillment, Shipping.

### Audit / Compliance (Cross-cutting Capability)
- **Purpose**: Trazabilidad global y auditoría inmutable.
- **Owned concepts**: Audit Event.
- **Principal FR/UC/BR**: FR-019, NFR-003, NFR-012, AC-017.
- **Data ownership**: Registro histórico y metadatos de auditoría.
- **Permitted dependencies**: Ninguna hacia negocio.
- **Prohibited dependencies**: Lógica operacional de negocio.

## 3. Aggregate Candidates

Identificación inicial de Aggregate Roots para transacciones consistentes:

- **Customer**: (Master Data) Consistencia de datos del cliente, límite transaccional para perfiles y configuraciones comerciales.
- **Product**: (Master Data) Consistencia de atributos base del SKU (candidate/TBD: dimensions y classification).
- **Location**: (Master Data) Consistencia estructural (candidate/TBD: type, capacity).
- **InventoryItem**: (Inventory) Agrupación transaccional de Stock por Product + Location. Invariante crítica de cantidades `OnHand` vs `Reserved`.
- **ASN**: (Inventory) Límite de consistencia para el proceso de recepción esperada (Receipt lines).
- **CustomerOrder**: (Commercial Orders) Agrupación de líneas de pedido y estado global del requerimiento del cliente.
- **FulfillmentPlan**: (Fulfillment) Agrega las operaciones de Allocation, Picking y Packing para una orden. Coordina el flujo operativo.
- **Dispatch**: (Shipping) Agrupación transaccional de Packages que salen del almacén en un solo evento.
- **FinancialObligation**: (Finance) Límite de consistencia para deuda (candidate/TBD: invoice/cargo). Invariante: no puede estar cerrada si el balance > 0.
- **Payment**: (Finance) Operación independiente que crea/registra Payment; puede dejar saldo disponible/no aplicado; no exige una obligación destino inmediata.
- **PaymentApplication**: (Finance) Operación posterior e independiente que consume saldo disponible de un Payment; crea una o más Payment Applications; reduce las obligaciones afectadas.

## 4. Critical Invariants

Las siguientes reglas de negocio son invariantes críticas que la arquitectura debe garantizar por diseño:

1. **`Reserved <= OnHand`**: La cantidad de inventario reservado nunca puede exceder el inventario físico total.
2. **OnHand y Dispatch**: Allocation no reduce OnHand. Picking no constituye salida definitiva del almacén. Packing no constituye salida definitiva. Dispatch confirmado reduce definitivamente OnHand. (Derivado de FR-012, AC-012, BR-002).
3. **Elegibilidad de Reserva**: Solo el inventario con estado elegible puede reservarse. El stock bloqueado o en cuarentena no participa normalmente en el cálculo de `Available`.
4. **Unidad Financiera**: Una obligación financiera es una sola entidad de negocio. El Backoffice la visualiza como `Accounts Receivable`, mientras que el Customer Portal la visualiza como `Accounts Payable` (proyección de la misma verdad).
5. **Register Payment vs Apply Payment**: 
   - **Register Payment** es una operación independiente que crea/registra Payment, puede dejar saldo disponible/no aplicado y no exige una obligación destino inmediata.
   - **Apply Payment** es una operación posterior e independiente que consume saldo disponible de un Payment, crea una o más Payment Applications, reduce las obligaciones afectadas.
6. **Cierre de Obligación**: Una obligación cierra únicamente según BR-007 (cuando pagos aplicados alcanzan el total).
7. **Idempotencia**: Los reintentos de operaciones no deben duplicar efectos de negocio.
8. **Resolución Offline**: Mobile offline-first; operaciones pendientes persisten localmente; cuando vuelve conectividad son enviadas al backend; servidor es SSOT; reintentos son idempotentes; violaciones de invariantes generan conflicto rastreable. (Candidate/TBD: mecanismos técnicos específicos de procesamiento).

## 5. Module Dependency Map

- Los contratos públicos (Application Contracts) de cada módulo definen las operaciones permitidas.
- Ningún módulo puede acceder directamente a los internals de otro módulo.
- **Dependencias Estrictas**:
  - `Inventory`, `Orders`, `Finance`, `Fulfillment` dependen de `Master Data`.
  - `Fulfillment` depende de `Orders` y de `Inventory`.
  - `Shipping` depende de `Fulfillment`.
  - `Finance` depende de `Orders` y `Master Data`.

## 6. Transaction Boundary Principles

Las operaciones de modificación de estado que requieran consistencia fuerte utilizarán los Aggregates definidos para aislar las transacciones en la capa de Aplicación:

- **Inventory Adjustment**: Transacción fuerte sobre `InventoryItem`.
- **Allocation**: Transacción coordinada para asegurar `Reserved <= OnHand`.
- **Picking Confirmation**: Actualización de estado en `Fulfillment`.
- **Dispatch**: Operación cross-module crítica. Shipping, Inventory y Finance deben preservar la postcondición completa de UC-013. El mecanismo concreto de coordinación/transaction boundary se formalizará en A3 Data/Transaction Architecture. No se permitirá estado observable de Dispatch completado sin los efectos obligatorios consistentes.
- **Financial Obligation Creation**: Transacción fuerte en `Finance`.
- **Register Payment**: Transacción fuerte en `Finance` que deja el pago disponible.
- **Apply Payment**: Transacción fuerte en `Finance` que concilia saldos.
- **Offline Sync Reconciliation**: El servidor aplica el SSOT sobre el estado actual.

## 7. Domain Event Candidates

Los módulos pueden coordinarse mediante Application Contracts y, cuando sea apropiado, Domain Events internos. La semántica síncrona, deferred/after-commit o eventual se decidirá según las garantías de consistencia de cada caso de uso. No usar eventos para debilitar invariantes o postcondiciones obligatorias.
No introducir infraestructura externa no aprobada.

## 8. Unresolved Needs
Los siguientes elementos no resueltos (heredados de Requirements) se conservan explícitamente y se resolverán en las fases indicadas:
- **UNRES-001, UNRES-002, UNRES-003** (Impactarán interacciones futuras de módulos)
- **UNRES-004, UNRES-005**: No afectan directamente los module boundaries de A1 y se resolverán en Interface Inventory.
- **UNRES-006**: Permanece pendiente para A2 Security/Auth.
No se asume la resolución de ninguno en esta fase.
