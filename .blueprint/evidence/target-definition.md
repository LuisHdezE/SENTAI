# SENTAI - Target Definition

**Blueprint Phase:** Target Definition
**Status:** READY_FOR_REVIEW  
**Artifact ID:** EVD-TARGET-001  

## 1. Target Product Statement
SENTAI es una plataforma integral de gestión de almacenes (WMS) y fulfillment que conecta la operación física con el ciclo comercial del cliente. El producto controla desde la recepción de la mercancía hasta el despacho y la trazabilidad financiera básica, manteniendo una clara distinción entre el ciclo **Order-to-Fulfillment** (ejecución física de pedidos) y el ciclo **Order-to-Cash** (obligación financiera y cobranza).

## 2. Target Outcomes
- Control trazable del flujo desde recepción hasta despacho.
- Integridad estricta de inventario y reservas.
- Ejecución operativa asistida de extremo a extremo mediante aplicación móvil.
- Creación y seguimiento autónomo de pedidos por parte de los clientes.
- Trazabilidad de cuentas por cobrar (Accounts Receivable) y cuentas por pagar (Customer Accounts Payable perspective).
- Operación móvil offline-first con posterior sincronización.
- Capacidad demostrable end-to-end de los ciclos core.

## 3. Target Solution Surfaces
Las siguientes superficies componen la solución y están formalmente aprobadas:
- Backend / REST API
- Backoffice Web
- Customer Portal Web
- Operator Mobile Android
- Operator Mobile iOS

## 4. Target Technology Truth
*(Decisiones de proyecto ya aprobadas, no correspondientes al Blueprint)*
- **Backend:** Laravel
- **Database:** MySQL
- **Web:** React + TypeScript + Tailwind
- **Mobile:** Kotlin Multiplatform
- **Estrategia móvil:** `cross_platform`
- **Android:** enabled
- **iOS:** enabled
- **Offline mobile:** enabled
- **Docker:** false
- **Redis:** false
- **SaaS:** false
- **Multi-tenant:** false
- **Mobile Licensing:** false

> **Nota arquitectónica:** La dirección arquitectónica aprobada del proyecto es **Clean Architecture + Modular Monolith**, pendiente de formalización en la fase `architecture_security_data`. Esto NO equivale todavía a `architecture_ready`.

## 5. Target Operational Journeys
*(Nivel de target, sin constituir use cases formales)*
- **Inbound / recepción / put-away:** Entrada física, conteo y ubicación sugerida o manual en almacén.
- **Inventory control:** Movimientos, consultas, bloqueos y ajustes de stock.
- **Customer order creation:** Captura de demanda a través del portal de clientes.
- **Allocation / picking / packing:** Reserva de stock, recolección en almacén y empacado para salida.
- **Shipment / dispatch:** Planificación y ejecución de salidas físicas.
- **Accounts Receivable / payment application:** Gestión de deuda generada y aplicación de pagos recibidos.
- **Customer Accounts Payable perspective:** Visibilidad del estado de cuenta desde el cliente.
- **Mobile offline operation and synchronization:** Ejecución de tareas operativas sin conexión y sincronización determinista de datos.

## 6. Target Actor Perspectives
- **Cliente:** Interactúa a través del Customer Portal para generar pedidos y revisar deudas.
- **Operario de almacén:** Ejecuta tareas (picking, put-away) a través de la aplicación móvil.
- **Supervisor / jefe de almacén:** Monitorea y controla desde el Backoffice.
- **Planificador de despacho:** Agrupa y agenda envíos.
- **Comercial / Finanzas:** Administra cobranza, cartera y pagos.
- **Administrador:** Configuración global del sistema.

## 7. Target Scope Boundaries
**Dentro del MVP:**
- Maestros esenciales.
- Inbound.
- Inventory.
- Orders.
- Fulfillment.
- Shipping básico.
- Accounts Receivable.
- Customer Portal.
- Operator mobile offline-first.

**Fuera del MVP:**
- Contabilidad general / libro mayor.
- Facturación fiscal.
- Optimización TMS avanzada.
- Robótica.
- IoT.
- Analítica predictiva.

## 8. Candidate Domain Boundaries
*(Los siguientes candidatos provienen de Discovery y sirven como hipótesis. NO son bounded contexts arquitectónicos aprobados. Sus límites definitivos se validarán en `requirements_domain` y se formalizarán en `architecture_security_data`)*
1. Identity
2. Customers
3. Catalog
4. Warehousing
5. Inbound
6. Inventory
7. Orders
8. Fulfillment
9. Operations
10. Shipping
11. Finance
12. Audit
13. Reporting
14. Integrations

## 9. Open Questions / Assumptions for Requirements
*(Cuestiones pendientes de resolución en la fase `requirements_domain`)*
- Reglas de negocio detalladas para asignación (allocation) y priorización.
- Matriz de autorización por actor (RBAC exacto).
- Requisitos funcionales detallados de cada journey.
- Requisitos no funcionales (performance, escalabilidad).
- Criterios de aceptación de historias de usuario / features.
- Casos de uso formales.
- Trazabilidad exigida por transacciones.
- Reglas exactas y resolución de conflictos para sincronización offline.
- Decisiones operativas pendientes de formalización (e.g. validaciones en recepción).

## 10. Exit Statement
La fase de Target Definition está completa como propuesta y queda lista para revisión humana.
La fase `requirements_domain` permanece **NOT_STARTED**.
Ningún gate posterior se considera aprobado por esta evidencia.
