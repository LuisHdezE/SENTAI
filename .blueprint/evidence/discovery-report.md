# SENTAI - Discovery Report

**Blueprint Phase:** Discovery (D0)  
**Date:** 17 de septiembre de 2026  
**Status:** COMPLETE  
**Primary Input:** `docs/product/SENTAI_Product_Definition_v0.1.md`  

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
2. **Ciclo de pedido:** Draft -> Confirmación -> Reserva (Allocation) -> Ejecución -> Despacho -> Facturación (Accounts Receivable).

---

## D0.3 - Context Map

Se han identificado 14 Bounded Contexts iniciales que compondrán el Modular Monolith:

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

Las verticales obligatorias para el Baseline y la secuencia sugerida de implementación técnica serán:
1. **Core:** Identity, Catalog, Customers y Warehousing.
2. **Inbound & Inventory:** Recepción y control de stock básico.
3. **Orders & Fulfillment:** Captura de pedido comercial y ejecución de picking.
4. **Shipping & Finance:** Planificación de salida y gestión de las cuentas por cobrar generadas.
5. **Mobile Operator App:** Sincronización offline e interfaz de picking/put-away para operarios de almacén.
6. **Portal Web de Clientes:** Cuentas por pagar, creación de pedidos y estado de cuenta.

*Nota:* Con este documento se da por concluida la fase de `discovery` y el proyecto se encuentra habilitado para iniciar formalmente la etapa de `target_definition` donde se validará y formalizará la arquitectura objetivo.*
