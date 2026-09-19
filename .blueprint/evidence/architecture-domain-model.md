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
- **Location**: Ubicación específica (pasillo, estante, posición) dentro de una zona.
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
- **Financial Obligation**: Deuda u obligación financiera generada (factura, cargo).
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
- **Principal FR/UC/BR**: Autorización cruzada (FR-011, BR-010).
- **Data ownership**: Credenciales y perfiles de acceso.
- **Permitted dependencies**: Ninguna (fundacional).
- **Prohibited dependencies**: Módulos de negocio.

### Master Data
- **Purpose**: Gestión de datos maestros transversales.
- **Owned concepts**: Product, Customer, Warehouse, Zone, Location.
- **Principal FR/UC/BR**: Configuración base de ubicaciones y productos.
- **Data ownership**: Catálogos centrales.
- **Permitted dependencies**: Identity & Access.
- **Prohibited dependencies**: Inventory, Orders, Finance (para evitar dependencias circulares, los módulos operativos referencian a Master Data, no al revés).

### Inventory / Warehouse Operations
- **Purpose**: Control de existencias físicas y sus movimientos internos.
- **Owned concepts**: Stock, ASN, Receipt, Put-away, Inventory Adjustment.
- **Principal FR/UC/BR**: FR-001, FR-002, UC-003.
- **Data ownership**: Cantidades físicas y movimientos de stock.
- **Permitted dependencies**: Master Data, Identity & Access.
- **Prohibited dependencies**: Finance.

### Commercial Orders
- **Purpose**: Gestión del ciclo de vida de los pedidos del cliente.
- **Owned concepts**: Customer Order.
- **Principal FR/UC/BR**: UC-008.
- **Data ownership**: Intención de compra del cliente.
- **Permitted dependencies**: Master Data, Identity & Access.
- **Prohibited dependencies**: Shipping / Dispatch (Orders no conoce el detalle de la logística física).

### Fulfillment
- **Purpose**: Ejecución operativa de la preparación de órdenes.
- **Owned concepts**: Allocation, Picking, Packing, Package.
- **Principal FR/UC/BR**: UC-009, UC-010, BR-003, BR-004.
- **Data ownership**: Estado de la preparación y reservas lógicas.
- **Permitted dependencies**: Commercial Orders, Inventory, Master Data.
- **Prohibited dependencies**: Finance.

### Shipping / Dispatch
- **Purpose**: Logística de salida y entrega.
- **Owned concepts**: Shipping Plan, Dispatch.
- **Principal FR/UC/BR**: BR-005.
- **Data ownership**: Planes de carga y confirmaciones de salida.
- **Permitted dependencies**: Fulfillment, Commercial Orders.
- **Prohibited dependencies**: Inventory.

### Finance
- **Purpose**: Gestión de cobros y pagos.
- **Owned concepts**: Financial Obligation (Accounts Receivable/Payable), Payment, Payment Application.
- **Principal FR/UC/BR**: FR-009, BR-008, BR-009.
- **Data ownership**: Saldos, estados de deuda, historial de pagos aplicados.
- **Permitted dependencies**: Commercial Orders (para generación de obligaciones), Master Data (Customer).
- **Prohibited dependencies**: Fulfillment, Shipping.

### Audit / Compliance (Cross-cutting Capability)
- **Purpose**: Trazabilidad global y auditoría inmutable.
- **Owned concepts**: Audit Event.
- **Principal FR/UC/BR**: FR-019, AC-017.
- **Data ownership**: Registro histórico y metadatos de auditoría.
- **Permitted dependencies**: Ninguna hacia negocio (los módulos de negocio envían eventos hacia Audit).
- **Prohibited dependencies**: Lógica operacional de negocio.

## 3. Aggregate Candidates

Identificación inicial de Aggregate Roots para transacciones consistentes:

- **Customer**: (Master Data) Consistencia de datos del cliente, límite transaccional para perfiles y configuraciones comerciales.
- **Product**: (Master Data) Consistencia de atributos base del SKU, dimensiones y clasificación.
- **Location**: (Master Data) Consistencia estructural (Zone, Tipo, Capacidad).
- **InventoryItem**: (Inventory) Agrupación transaccional de Stock por Product + Location. Invariante crítica de cantidades `OnHand` vs `Reserved`.
- **ASN**: (Inventory) Límite de consistencia para el proceso de recepción esperada (Receipt lines).
- **CustomerOrder**: (Commercial Orders) Agrupación de líneas de pedido y estado global del requerimiento del cliente.
- **FulfillmentPlan**: (Fulfillment) Agrega las operaciones de Allocation, Picking y Packing para una orden. Coordina el flujo operativo.
- **Dispatch**: (Shipping) Agrupación transaccional de Packages que salen del almacén en un solo evento.
- **FinancialObligation**: (Finance) Límite de consistencia para deuda facturada o generada. Invariante: no puede estar cerrada si el balance > 0.
- **Payment**: (Finance) Agrega la información de la transacción de pago y las `Payment Application` distribuidas a diferentes obligaciones.

## 4. Critical Invariants

Las siguientes reglas de negocio son invariantes críticas que la arquitectura debe garantizar por diseño:

1. **`Reserved <= OnHand`**: La cantidad de inventario reservado nunca puede exceder el inventario físico total.
2. **Allocation NO reduce `OnHand`**: El proceso de Allocation incrementa `Reserved` pero no descuenta el `OnHand` físico; el descuento real ocurre en el Dispatch (o Packing final, según diseño detallado).
3. **Elegibilidad de Reserva**: Solo el inventario con estado elegible puede reservarse. El stock bloqueado o en cuarentena no participa normalmente en el cálculo de `Available`.
4. **Unidad Financiera**: Una obligación financiera es una sola entidad de negocio. El Backoffice la visualiza como `Accounts Receivable`, mientras que el Customer Portal la visualiza como `Accounts Payable` (proyección de la misma verdad).
5. **Registro vs. Aplicación de Pagos**: Registrar un `Payment` no es lo mismo que aplicarlo. Un pago requiere una `Payment Application` para conciliar saldos.
6. **Cierre de Obligación**: Una obligación financiera cierra *únicamente* cuando la suma de los pagos aplicados alcanza su monto total.
7. **Idempotencia**: Los reintentos de operaciones (especialmente asíncronas o de red) no deben duplicar efectos de negocio (ej: no debitar dos veces el stock o aplicar dos veces el mismo pago).
8. **Resolución Offline**: El servidor es siempre el Single Source of Truth (SSOT) ante conflictos generados por sincronización de dispositivos móviles offline.

## 5. Module Dependency Map

- Los contratos públicos (Application Contracts) de cada módulo definen las operaciones permitidas.
- La comunicación entre módulos se realizará a través de llamadas de Application Services o Domain Events.
- Ningún módulo puede acceder directamente a los internals (Base de datos o Entities directas) de otro módulo.
- **Dependencias Estrictas**:
  - `Inventory`, `Orders`, `Finance`, `Fulfillment` dependen de `Master Data`.
  - `Fulfillment` depende de `Orders` y de `Inventory`.
  - `Shipping` depende de `Fulfillment`.
  - `Finance` depende de `Orders` para la generación de obligaciones, pero no de la logística.

## 6. Transaction Boundary Principles

Las operaciones de modificación de estado que requieran consistencia fuerte utilizarán los Aggregates definidos para aislar las transacciones en la capa de Aplicación:

- **Inventory Adjustment**: Transacción fuerte sobre `InventoryItem`.
- **Allocation**: Transacción coordinada. `Fulfillment` orquesta, validando reservas contra el Application Contract de `Inventory` para asegurar `Reserved <= OnHand`.
- **Picking Confirmation**: Actualización de estado en `Fulfillment` (y potencialmente ajuste de estado lógico en `Inventory`).
- **Dispatch**: Transacción fuerte en `Shipping` que emite un evento para que `Inventory` reduzca finalmente el `OnHand`.
- **Financial Obligation Creation**: Transacción fuerte en `Finance`.
- **Payment Registration & Application**: La creación de un `Payment` y sus sub-entidades `Payment Application` es una transacción única en `Finance` que actualiza concurrentemente el balance de las obligaciones afectadas.
- **Offline Sync Reconciliation**: Las transacciones offline entrantes se encolarán y procesarán secuencialmente contra el estado actual del SSOT (servidor), abortando transaccionalmente si las precondiciones han cambiado.

## 7. Domain Event Candidates

Los eventos de dominio se utilizarán (solo si se justifican) para comunicación asíncrona in-process o para desacoplar efectos secundarios, apoyados exclusivamente en requerimientos funcionales documentados:

- `OrderApproved`: Emitido por Commercial Orders.
- `InventoryAllocated`: Emitido por Fulfillment/Inventory al confirmar reserva.
- `PickingCompleted`: Emitido por Fulfillment.
- `PackageCompleted`: Emitido por Fulfillment.
- `DispatchConfirmed`: Emitido por Shipping; disparará la actualización de OnHand y la posible generación de facturación (si el flujo financiero lo dictamina).
- `FinancialObligationCreated`: Emitido por Finance.
- `PaymentRegistered`: Emitido por Finance.
- `PaymentApplied`: Emitido por Finance.

*(Nota: Estos son conceptos lógicos. No se asume infraestructura externa como Kafka o Redis Streams. Estos eventos serán procesados internamente por el ecosistema de Laravel).*
