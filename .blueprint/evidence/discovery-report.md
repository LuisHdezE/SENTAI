# SENTAI - Discovery Report

**Blueprint Phase:** Discovery (D0)  
**Date:** 17 de septiembre de 2026  
**Status:** READY_FOR_REVIEW  
**Primary Inputs:** 
- `docs/product/SENTAI_Product_Definition_v0.1.md` (Funcional y de negocio)
- `.blueprint/project.yaml` (Decisiones técnicas y capabilities congeladas)

> **Nota de precedencia (Project Truth):** El Product Definition v0.1 contiene referencias técnicas preliminares (ej. Flutter, Docker). En caso de conflicto, **`.blueprint/project.yaml` es la fuente canónica** y prevalece sobre el documento de producto. Se establecen explícitamente las siguientes Project Truths que serán formalizadas arquitectónicamente en la fase `architecture_security_data` (no constituyen `architecture_ready` en este momento):
> - SENTAI Operator será **Kotlin Multiplatform**, `cross_platform`, para `android` e `ios`, y `offline_first`.
> - **Docker** no forma parte de las capabilities del proyecto (`docker: false`).

---

## D0.1 - Product Definition & Scope

### Visión
SENTAI es una plataforma de gestión de almacenes (WMS) y fulfillment diseñada para conectar la operación física con el ciclo comercial del cliente. El producto controla desde la recepción de la mercancía hasta el despacho y la trazabilidad financiera básica.

### Alcance
- **Incluido (MVP):** Maestros (almacén, zonas, productos, clientes), recepción (put-away), gestión de inventario (lotes, estados, reservas), gestión de pedidos (creación y validación), fulfillment (picking, packing), planificación de despachos, cuentas por cobrar, y una aplicación móvil operativa offline-first.
- **Fuera de alcance (MVP):** Contabilidad general (libro mayor, asientos), facturación fiscal, optimización avanzada de rutas (TMS), robótica, IoT y analítica predictiva.

### Actores
1. **Cliente:** Consulta catálogo, crea pedidos, rastrea envíos y revisa obligaciones de pago.
2. **Operario de almacén:** Ejecuta tareas en almacén mediante app móvil.
3. **Supervisor / Jefe de almacén:** Monitorea y controla productividad e inventario.
4. **Planificador de despacho:** Agrupa y agenda envíos.
5. **Comercial / Finanzas:** Gestiona créditos, cartera, límites y pagos.
6. **Administrador:** Configuración del sistema y usuarios.

### Criterios de Éxito
- Operaciones completas y demostrables end-to-end (Order-to-Fulfillment y Order-to-Cash).
- Integridad en el control de stock (prevención de sobre-reservas).
- Sincronización offline exitosa para operarios de almacén.

---

## D0.2 - Domain Discovery

### Lenguaje Ubicuo y Conceptos Clave
- **InventoryStock:** Definido no solo por cantidad, sino por Ubicación, Lote/Serie y Estado (Available, Reserved, Quarantine, Damaged, Blocked, Expired).
- **Reserva:** El compromiso de inventario no rebaja inmediatamente el `OnHand`. Regla: `Reserved <= OnHand`.
- **Order-to-Fulfillment vs Order-to-Cash:** Dos ciclos de vida diferentes desencadenados por el mismo pedido comercial. Uno termina en la entrega física, el otro cuando la obligación financiera queda saldada (Paid).

### Procesos Principales
1. **Flujo físico:** ASN -> Recepción -> Put-away sugerido -> Ubicación -> Picking -> Packing -> Shipment -> Despacho.
2. **Ciclo de pedido:** Draft -> Confirmación -> Reserva (Allocation) -> Ejecución -> Despacho -> generación/gestión de obligación por cobrar -> pago -> aplicación del pago -> obligación saldada.
*Nota:* Accounts Receivable forma parte del alcance MVP. Facturación fiscal (fiscal invoicing) permanece fuera del MVP.

---

## D0.3 - Context Map

Se identifican 14 candidate domain/module boundaries como hipótesis inicial de descomposición funcional.
Estos son candidatos provenientes del Product Discovery, y deben validarse durante `requirements_domain`.
Sus límites definitivos se formalizarán posteriormente durante `architecture_security_data`. No constituyen todavía bounded contexts arquitectónicos aprobados:

1. **Identity:** Autenticación y RBAC.
2. **Customers:** Clientes y perfiles asociados.
3. **Catalog:** Productos, SKUs y atributos.
4. **Warehousing:** Estructura física (almacenes, zonas, posiciones).
5. **Inbound:** ASN, recepción y put-away.
6. **Inventory:** Stock, movimientos, ajustes y reservas.
7. **Orders:** Pedidos de cliente.
8. **Fulfillment:** Allocation, picking y packing.
9. **Operations:** Gestión de tareas operativas y ejecución móvil.
10. **Shipping:** Planificación y consolidación de despachos.
11. **Finance:** Cuentas por cobrar (Accounts Receivable), pagos y aging.
12. **Audit:** Historial inmutable y trazabilidad.
13. **Reporting:** Métricas y dashboards.
14. **Integrations:** Contratos externos.

---

## D0.4 - MVP Baseline

Las candidate MVP verticals / provisional delivery decomposition (sujetas a validación durante `target_definition` y `requirements_domain`) son:
1. **Core:** Identity, Catalog, Customers y Warehousing.
2. **Inbound & Inventory:** Recepción y control de stock básico.
3. **Orders & Fulfillment:** Captura de pedido comercial y ejecución de picking.
4. **Shipping & Finance:** Planificación de salida y gestión de las cuentas por cobrar generadas.
5. **Mobile Operator App:** Sincronización offline e interfaz de picking/put-away para operarios de almacén.
6. **Portal Web de Clientes:** Cuentas por pagar, creación de pedidos y estado de cuenta.

*Nota:* Discovery evidence is complete and ready for human review. `target_definition` remains NOT_STARTED until explicit human acceptance.*
