# SENTAI - Requirements & Domain

**Blueprint Phase:** Requirements & Domain (R1)  
**Status:** COMPLETE  
**Artifact ID:** EVD-REQ-001  
**Type:** requirements_domain_evidence  

---

## 1. Actors & Authorization Intent

Las siguientes definiciones representan la autorización funcional a nivel de intención. **No** prescriben todavía un diseño técnico de permisos o roles definitivos.

* **ACT-001 Cliente:**
  * **Propósito:** Actor externo comercial.
  * **Responsabilidades:** Consultar catálogo, crear pedidos, rastrear despachos y revisar obligaciones.
  * **Capacidades Autorizadas:** Leer catálogo activo, crear pedidos propios, ver estado de cuenta propio.
  * **Restricciones:** No puede ver datos de otros clientes ni operar tareas físicas de almacén.
  * **Superficies:** Customer Portal Web.

* **ACT-002 Operario de almacén:**
  * **Propósito:** Fuerza laboral en el piso.
  * **Responsabilidades:** Ejecutar tareas físicas (recepción, put-away, picking, packing).
  * **Capacidades Autorizadas:** Recibir mercancía según ASN, mover inventario (put-away), ejecutar recolección y empaque.
  * **Restricciones:** No puede crear maestros, confirmar pedidos comerciales ni gestionar finanzas.
  * **Superficies:** Operator Mobile (Android/iOS).

* **ACT-003 Supervisor / Jefe de almacén:**
  * **Propósito:** Control operativo.
  * **Responsabilidades:** Monitorear productividad, controlar inventario, resolver excepciones.
  * **Capacidades Autorizadas:** Ajustar inventario, bloquear ubicaciones, consultar métricas operativas.
  * **Restricciones:** No aprueba créditos ni gestiona pagos.
  * **Superficies:** Backoffice Web.

* **ACT-004 Planificador de despacho:**
  * **Propósito:** Planificación de salida/despacho.
  * **Responsabilidades:** Agrupar pedidos empaquetados y organizar envíos.
  * **Capacidades Autorizadas:** Crear planes de envío, asignar despachos, confirmar salida física.
  * **Restricciones:** Solo opera sobre pedidos ya validados y procesados por el almacén.
  * **Superficies:** Backoffice Web.

* **ACT-005 Comercial:**
  * **Propósito:** Gestión de la relación con el cliente y ventas.
  * **Responsabilidades:** Validar pedidos y gestionar condiciones comerciales.
  * **Capacidades Autorizadas:** Aprobar pedidos ingresados, modificar datos comerciales del cliente.
  * **Restricciones:** No opera almacén ni registra/aplica pagos de obligaciones.
  * **Superficies:** Backoffice Web.

* **ACT-006 Finanzas:**
  * **Propósito:** Control de flujo de caja y cartera.
  * **Responsabilidades:** Administrar cobranza, registrar pagos, aplicar pagos a obligaciones.
  * **Capacidades Autorizadas:** Registrar cobros recibidos, saldar obligaciones (Accounts Receivable).
  * **Restricciones:** No modifica pedidos comerciales ni opera almacén.
  * **Superficies:** Backoffice Web.

* **ACT-007 Administrador:**
  * **Propósito:** Mantenimiento del sistema.
  * **Responsabilidades:** Configuración global del sistema, gestión de usuarios, roles, permisos y parámetros.
  * **Capacidades Autorizadas:** Configuración, gestión de identidad y catálogos maestros estrictamente según su autorización.
  * **Restricciones:** No está pensado como un bypass universal para operaciones comerciales/financieras cotidianas sin trazabilidad.
  * **Superficies:** Backoffice Web.

---

## 2. Functional Requirements (FR)

* **FR-001 Maestros esenciales:** El sistema debe permitir la gestión de productos, clientes, almacenes, zonas y posiciones físicas.
* **FR-002 Inbound / Recepción:** El sistema debe permitir registrar la entrada de mercancía validándola obligatoriamente contra un aviso previo (ASN) aprobado.
* **FR-003 Put-away:** El sistema debe permitir registrar el traslado de la mercancía recibida a ubicaciones definitivas en el almacén.
* **FR-004 Gestión granular de inventario:** El stock debe ser trazable unívocamente por Ubicación, Producto, Lote, Número de Serie (cuando aplique) y Estado.
* **FR-005 Movimientos y ajustes:** El sistema debe permitir mover stock entre ubicaciones y realizar ajustes de inventario para corregir discrepancias o cambiar estados.
* **FR-006 Reserva (Allocation):** El sistema debe permitir asignar/reservar inventario elegible para satisfacer un pedido comercial confirmado.
* **FR-007 Customer order creation:** El Customer Portal debe permitir a los clientes armar y confirmar intención de pedidos a partir del catálogo disponible.
* **FR-008 Validación de pedido:** El Backoffice debe proveer la capacidad de revisar y aprobar los pedidos ingresados por los clientes según condiciones comerciales.
* **FR-009 Picking:** La aplicación móvil debe guiar y permitir confirmar la recolección del stock reservado desde sus ubicaciones.
* **FR-010 Packing:** El sistema debe permitir registrar el empaquetado del stock recolectado en bultos/paquetes identificables.
* **FR-011 Shipping planning:** El sistema debe permitir agrupar pedidos/bultos listos en un plan de envío consolidado.
* **FR-012 Dispatch (Despacho):** El sistema debe permitir registrar la salida física del almacén, deduciendo definitivamente el stock.
* **FR-013 Accounts Receivable:** Al completar un despacho, el sistema debe generar una obligación financiera de cobro asociada al cliente.
* **FR-014 Pagos (Registro):** El sistema debe permitir registrar ingresos de pagos recibidos de clientes.
* **FR-015 Pagos (Aplicación):** El sistema debe permitir aplicar parcial o totalmente un pago registrado contra obligaciones abiertas.
* **FR-016 Aging y Saldos:** El backoffice debe proveer vistas para consultar el estado general de la cartera (deuda, vencimientos, saldos).
* **FR-017 Customer Accounts Payable (AP):** El Customer Portal debe permitir al cliente visualizar sus obligaciones financieras pendientes.
* **FR-018 Estado de cuenta:** El Customer Portal debe proveer al cliente un historial consolidado de sus deudas y pagos.
* **FR-019 Auditoría funcional:** Todo cambio crítico de inventario, estado de pedido o transacción financiera debe registrar trazabilidad auditable (quién, cuándo, qué, y motivo).
* **FR-020 Operación móvil offline-first:** El operario móvil debe poder continuar ejecutando tareas operativas descargadas durante pérdidas de conectividad.
* **FR-021 Sincronización posterior:** El sistema debe procesar y conciliar las tareas ejecutadas fuera de línea cuando el dispositivo recupere conexión.
* **FR-022 Acceso autenticado y autorizado:** Los actores registrados deben poder autenticarse y acceder únicamente a las funciones operativas que su autorización permite.

---

## 3. Non-Functional Requirements (NFR)

* **NFR-001 Seguridad:** Toda operación debe requerir autenticación y verificar la autorización funcional del actor antes de ejecutarse.
* **NFR-002 Integridad transaccional:** Las operaciones críticas deben exhibir consistencia atómica observable (todo o nada), previniendo estados parciales visibles.
* **NFR-003 Trazabilidad inmutable:** Las acciones relevantes deben generar evidencia durable y auditable que conserve su historial operativo.
* **NFR-004 Concurrencia segura:** El sistema debe prevenir explícitamente condiciones de carrera y sobre-reservas bajo acceso concurrente.
* **NFR-005 Idempotencia:** Los reintentos de una misma operación lógica no deben producir efectos de negocio duplicados.
* **NFR-006 Disponibilidad y degradación elegante:** Ante indisponibilidad de red, la interfaz móvil debe degradarse permitiendo operaciones locales seguras sin bloquear al usuario.
* **NFR-007 Recuperación ante conflictos:** El sistema debe proveer mecanismos claros para resolver colisiones cuando la sincronización posterior contradiga el estado actualizado del servidor.
* **NFR-008 Performance y Escalabilidad:** El sistema debe mantener tiempos de respuesta interactivos bajo carga operativa estándar de un almacén.
* **NFR-009 Accesibilidad y Responsive:** La navegación y acciones principales del Customer Portal deben ser utilizables sin depender exclusivamente de puntero; la información crítica no debe comunicarse únicamente mediante color, y los controles deben ser comprensibles para tecnologías asistivas cuando corresponda. Adicionalmente, la interfaz debe adaptarse a múltiples tamaños de pantalla.
* **NFR-010 Mantenibilidad arquitectónica:** El diseño debe facilitar la evolución independiente de dominios (dirección aprobada: Clean Architecture + Modular Monolith).
* **NFR-011 Compatibilidad Móvil:** La aplicación debe soportar ecosistemas Android e iOS a partir de una base unificada.
* **NFR-012 Observabilidad:** El sistema debe exponer evidencia diagnóstica, correlación de eventos y capacidad de trazabilidad transversal para facilitar soporte.

---

## 4. Business Rules (BR)

* **BR-001 Límite de Reserva:** El sistema siempre debe cumplir la invariante `Reserved <= OnHand`.
* **BR-002 Naturaleza de la Reserva:** Una asignación (Allocation) compromete stock lógicamente, pero no deduce el stock físico (`OnHand`) ni se considera una salida.
* **BR-003 Cálculo de Disponibilidad Comercial:** Una nueva reserva comercial solamente puede consumir inventario elegible. El stock bloqueado, cuarentena, dañado o vencido no participa en la disponibilidad comercial. El `Available` representa el inventario elegible no comprometido.
* **BR-004 Estados de Bloqueo:** El inventario marcado explícitamente como bloqueado o cuarentena debe inhibir su manipulación estándar hasta su liberación.
* **BR-005 Deuda Consolidada por Perspectiva:** Una misma obligación financiera generada por un despacho existe como una única entidad, visible como "Cuentas por Cobrar" internamente y "Cuentas por Pagar" externamente, sin duplicación lógica.
* **BR-006 Desacoplamiento de Pagos:** El ingreso de un pago y su aplicación a una obligación financiera son independientes. Un pago puede existir como saldo a favor sin aplicarse inmediatamente.
* **BR-007 Obligación Saldada:** Una obligación/documento financiero se considera cerrado únicamente cuando el total de sus pagos aplicados iguala el monto de la obligación.
* **BR-008 Regla de Idempotencia de Reintentos:** Un reintento técnico de una operación ya consumada no debe aplicar repetidas deducciones físicas ni registros financieros paralelos.
* **BR-009 Restricciones de Crédito:** La aprobación comercial de un pedido debe respetar los límites de deuda tolerados para el cliente.
* **BR-010 Autoridad del Servidor (SSOT):** En sincronización offline, el servidor es la fuente única de la verdad. La operación desconectada que resulte inválida frente al estado del servidor debe generar un conflicto rastreable, no forzar una invalidez que viole BR-001 o BR-003.

---

## 5. Use Cases (UC)

* **UC-001 Autenticación / Acceso**
  * **Actor:** Todos
  * **Objetivo:** Acceder a las funciones autorizadas del sistema.
  * **Precondiciones:** Usuario registrado en el sistema.
  * **Flujo:** Usuario ingresa credenciales funcionales; el sistema establece una sesión de acceso validada según sus privilegios.
  * **Excepciones:** Credenciales inválidas rechazan el acceso.
  * **Postcondición:** El actor puede interactuar con el sistema según su autorización.
  * **Relaciones:** FR-022, NFR-001

* **UC-002 Mantener Maestros Autorizados**
  * **Actor:** Administrador
  * **Objetivo:** Proveer datos de referencia operativa.
  * **Precondiciones:** Sesión con privilegios de administración de maestros.
  * **Flujo:** Actor ingresa/actualiza datos de entidades (clientes, productos, almacenes); sistema valida reglas y persiste.
  * **Excepciones:** Ninguna específica adicional definida en este nivel.
  * **Postcondición:** Los datos están disponibles para transacciones.
  * **Relaciones:** FR-001

* **UC-003 Recibir Mercancía**
  * **Actor:** Operario de almacén (ACT-002)
  * **Objetivo:** Ingresar stock al almacén.
  * **Precondiciones:** Existe un ASN (Aviso Anticipado de Envío) válido.
  * **Flujo:** Actor selecciona el ASN, verifica cantidades recibidas físicamente contra el documento y confirma la entrada.
  * **Excepciones:** Discrepancias de cantidad (se registra sobrante/faltante según políticas).
  * **Postcondición:** Stock registrado en zona de recepción.
  * **Relaciones:** FR-002, BR-004

* **UC-004 Ejecutar Put-away**
  * **Actor:** Operario de almacén (ACT-002)
  * **Objetivo:** Ubicar stock en estantería desde recepción.
  * **Precondiciones:** Stock existente en zona de recepción.
  * **Flujo:** Actor indica la mercancía, la ubicación destino (sugerida o manual), confirmando el traslado.
  * **Excepciones:** Ninguna específica adicional definida en este nivel.
  * **Postcondición:** Stock actualizado con nueva ubicación de almacenaje.
  * **Relaciones:** FR-003, FR-004

* **UC-005 Consultar Inventario**
  * **Actor:** Supervisor / Jefe de almacén (ACT-003)
  * **Objetivo:** Conocer visibilidad y estado de stock real.
  * **Precondiciones:** Acceso al módulo de inventario.
  * **Flujo:** Actor consulta por producto/ubicación; sistema calcula y expone OnHand, Reservas, Estados y Disponibilidad.
  * **Excepciones:** Ninguna específica adicional definida en este nivel.
  * **Postcondición:** Resumen exacto obtenido.
  * **Relaciones:** FR-004, BR-003, BR-004

* **UC-006 Mover / Ajustar Inventario**
  * **Actor:** Supervisor / Jefe de almacén (ACT-003)
  * **Objetivo:** Corregir realidad física o cambiar de ubicación lógica.
  * **Precondiciones:** Privilegios de supervisor.
  * **Flujo:** Actor declara ajuste (aumento/disminución/cambio de estado/movimiento) y justifica; sistema aplica.
  * **Excepciones:** Ninguna específica adicional definida en este nivel.
  * **Postcondición:** Inventario actualizado y rastro de auditoría generado.
  * **Relaciones:** FR-005, FR-019

* **UC-007 Crear Pedido Comercial**
  * **Actor:** Cliente (ACT-001)
  * **Objetivo:** Solicitar mercancía.
  * **Precondiciones:** Autenticado en Customer Portal.
  * **Flujo:** Selecciona productos del catálogo, especifica cantidades y envía la solicitud funcional.
  * **Excepciones:** Ninguna específica adicional definida en este nivel.
  * **Postcondición:** Pedido registrado a la espera de validación comercial.
  * **Relaciones:** FR-007

* **UC-008 Validar / Confirmar Pedido**
  * **Actor:** Comercial (ACT-005)
  * **Objetivo:** Autorizar ejecución operativa de una orden cliente.
  * **Precondiciones:** Pedido existente pendiente de aprobación.
  * **Flujo:** Comercial revisa el pedido y reglas de crédito; aprueba la solicitud para el almacén.
  * **Excepciones:** Se rechaza o retiene el pedido por crédito insuficiente o condiciones comerciales incumplidas.
  * **Postcondición:** Pedido confirmado para procesamiento logístico.
  * **Relaciones:** FR-008, BR-009

* **UC-009 Reservar Inventario (Allocation)**
  * **Actor:** Sistema / Supervisor (ACT-003)
  * **Objetivo:** Comprometer stock físico elegible para un pedido.
  * **Precondiciones:** Pedido validado comercialmente.
  * **Flujo:** Sistema localiza e intenta asignar stock elegible disponible a las líneas del pedido, incrementando reservas.
  * **Excepciones:** Stock elegible insuficiente (asigna parcialmente o detiene el flujo según regla).
  * **Postcondición:** Stock reservado; picking puede comenzar.
  * **Relaciones:** FR-006, BR-001, BR-002, BR-003

* **UC-010 Ejecutar Picking**
  * **Actor:** Operario de almacén (ACT-002)
  * **Objetivo:** Recolectar stock reservado físicamente en almacén.
  * **Precondiciones:** Tarea de picking asignada/descargada en app móvil.
  * **Flujo:** Operario se dirige a la ubicación indicada, confirma producto extraído y cantidad recolectada.
  * **Excepciones:** La mercancía no se encuentra físicamente (genera excepción de faltante).
  * **Postcondición:** Tarea de recolección completada.
  * **Relaciones:** FR-009, FR-020

* **UC-011 Ejecutar Packing**
  * **Actor:** Operario de almacén (ACT-002)
  * **Objetivo:** Empaquetar mercancía recolectada en bultos de salida.
  * **Precondiciones:** Tarea de picking finalizada.
  * **Flujo:** Actor declara en qué bultos/paquetes consolidados se embala la mercancía del pedido.
  * **Excepciones:** Ninguna específica adicional definida en este nivel.
  * **Postcondición:** Bultos generados listos para planificación de envío.
  * **Relaciones:** FR-010

* **UC-012 Planificar Envío**
  * **Actor:** Planificador de despacho (ACT-004)
  * **Objetivo:** Consolidar entregas.
  * **Precondiciones:** Bultos empaquetados disponibles.
  * **Flujo:** Actor selecciona bultos de uno o múltiples pedidos y los agrupa en un plan consolidado de envío/transporte.
  * **Excepciones:** Ninguna específica adicional definida en este nivel.
  * **Postcondición:** Plan de envío consolidado creado.
  * **Relaciones:** FR-011

* **UC-013 Despachar Mercancía**
  * **Actor:** Planificador de despacho (ACT-004)
  * **Objetivo:** Registrar la salida física y legal del almacén.
  * **Precondiciones:** Plan de envío validado y consolidado.
  * **Flujo:** Actor confirma la salida definitiva de los bultos; el sistema deduce el stock físico e informa finanzas.
  * **Excepciones:** Ninguna específica adicional definida en este nivel.
  * **Postcondición:** Stock descontado permanentemente; obligación financiera de cobro generada.
  * **Relaciones:** FR-012, FR-013, BR-005

* **UC-014 Consultar Cartera**
  * **Actor:** Finanzas (ACT-006)
  * **Objetivo:** Revisar salud y vencimiento de cuentas por cobrar.
  * **Precondiciones:** Acceso al backoffice financiero.
  * **Flujo:** Actor visualiza saldos pendientes agrupados, identificando estado de deuda global.
  * **Excepciones:** Ninguna específica adicional definida en este nivel.
  * **Postcondición:** Información financiera analizada.
  * **Relaciones:** FR-016

* **UC-015 Registrar Pago Recibido**
  * **Actor:** Finanzas (ACT-006)
  * **Objetivo:** Asentar el ingreso de fondos externos.
  * **Precondiciones:** Evidencia comercial de pago.
  * **Flujo:** Actor asienta el monto recibido y el cliente origen en el sistema.
  * **Excepciones:** Ninguna específica adicional definida en este nivel.
  * **Postcondición:** Registro de pago creado (como saldo a favor).
  * **Relaciones:** FR-014, BR-006

* **UC-016 Aplicar Pago a Deuda**
  * **Actor:** Finanzas (ACT-006)
  * **Objetivo:** Disminuir el saldo adeudado de obligaciones financieras.
  * **Precondiciones:** Pago registrado con saldo libre y obligación u obligaciones abiertas.
  * **Flujo:** Actor asocia el monto del pago contra obligaciones específicas.
  * **Excepciones:** Pago insuficiente para saldar deuda (se aplica parcialmente).
  * **Postcondición:** El saldo pendiente de la obligación disminuye.
  * **Relaciones:** FR-015, BR-007

* **UC-017 Consultar Obligaciones (Customer Portal)**
  * **Actor:** Cliente (ACT-001)
  * **Objetivo:** Conocer obligaciones y deudas propias activas.
  * **Precondiciones:** Acceso válido al Customer Portal.
  * **Flujo:** Actor revisa el listado de obligaciones pendientes de pago.
  * **Excepciones:** Ninguna específica adicional definida en este nivel.
  * **Postcondición:** Visibilidad de Accounts Payable desde la perspectiva del cliente obtenida.
  * **Relaciones:** FR-017, BR-005

* **UC-018 Consultar Estado de Cuenta**
  * **Actor:** Cliente (ACT-001) / Finanzas (ACT-006)
  * **Objetivo:** Revisar el historial cruzado de obligaciones y liquidaciones.
  * **Precondiciones:** Acceso válido.
  * **Flujo:** El sistema emite un reporte histórico que consolida deudas contraídas, pagos aplicados y saldos de período.
  * **Excepciones:** Ninguna específica adicional definida en este nivel.
  * **Postcondición:** Estado de cuenta emitido.
  * **Relaciones:** FR-018

* **UC-019 Operar Tareas Móviles Offline**
  * **Actor:** Operario de almacén (ACT-002)
  * **Objetivo:** Proveer continuidad operativa en zonas sin conectividad.
  * **Precondiciones:** Tareas (ej. picking) previamente descargadas en el dispositivo móvil con conexión.
  * **Flujo:** El usuario pierde red; continúa registrando movimientos físicamente; los datos persisten localmente en el dispositivo permitiendo continuar el flujo.
  * **Excepciones:** Pérdida total de energía o destrucción del dispositivo físico.
  * **Postcondición:** Tareas registradas temporalmente en el dispositivo.
  * **Relaciones:** FR-020, NFR-006

* **UC-020 Sincronizar Operaciones Pendientes**
  * **Actor:** Sistema / Operario de almacén (ACT-002)
  * **Objetivo:** Conciliar transacciones locales con la fuente de la verdad (backend).
  * **Precondiciones:** Conectividad recuperada, operaciones en cola de sincronización local.
  * **Flujo:** Dispositivo envía eventos locales. El servidor procesa, resuelve estado final e informa confirmación al cliente.
  * **Excepciones:** Operación rechazada porque el servidor detecta violación de invariantes (ej. stock ya utilizado); genera conflicto funcional.
  * **Postcondición:** Operaciones conciliadas de forma consistente.
  * **Relaciones:** FR-021, NFR-005, NFR-007, BR-010

---

## 6. Acceptance Criteria (AC)

* **AC-020 (FR-022):** Un usuario sin credenciales válidas no puede acceder a las funciones del sistema; un usuario autenticado solo puede ejecutar acciones acordes a su autorización (ej. un cliente no puede ver inventario del almacén).
* **AC-001 (FR-001):** Es posible realizar operaciones CRUD exitosas sobre entidades maestras sin errores funcionales, respetando las restricciones de acceso.
* **AC-002 (FR-002):** Un intento de recepción que no se referencie a un ASN válido es rechazado explícitamente por el sistema.
* **AC-003 (FR-003):** Después de la confirmación de put-away, el stock deja de figurar funcionalmente en la zona de recepción y queda asociado a la ubicación destino especificada.
* **AC-004 (FR-004):** Toda consulta de inventario refleja consistentemente combinaciones de ubicación, lote y estado sin agregaciones erróneas.
* **AC-005 (FR-005):** Un ajuste de inventario manual actualiza el stock físico disponible y deja evidencia rastreable de la acción.
* **AC-006 (FR-006):** Un intento de reserva comercial que excede el inventario elegible no se materializa y alerta de insuficiencia.
* **AC-007 (FR-007):** Un cliente obtiene confirmación exitosa de la creación de su pedido para posterior validación.
* **AC-008 (FR-008):** La validación exitosa de un pedido por un rol comercial lo hace visible para tareas de logística.
* **AC-009 (FR-009):** La finalización del picking confirma la extracción del stock de su ubicación original y lo vincula al pedido.
* **AC-010 (FR-010):** El resultado del packing es un registro de bultos/paquetes que encapsulan los productos recolectados.
* **AC-011 (FR-011):** Múltiples bultos se agrupan funcionalmente en un plan de envío común sin conflictos de asignación.
* **AC-012 (FR-012):** Al confirmar un despacho, el sistema reduce el `OnHand` del almacén en la cantidad efectivamente despachada, conservando la trazabilidad de la salida definitiva.
* **AC-013 (FR-013):** Consecuente al despacho, se crea de forma observable una obligación financiera por el monto pertinente.
* **AC-014 (FR-014):** Se puede comprobar el aumento de saldo a favor de un cliente al asentar un ingreso de dinero.
* **AC-015 (FR-015):** Aplicar pagos que cubran la totalidad del valor pendiente de una obligación la marca funcionalmente como cerrada.
* **AC-016 (FR-016, FR-017, FR-018):** El sistema muestra consistencia funcional comprobando que: (1) el backoffice refleja saldos acumulados de la cartera de clientes, (2) el cliente ve estas obligaciones idénticas como cuentas por pagar en su portal, y (3) el estado de cuenta consolida correctamente las obligaciones y los pagos aplicados.
* **AC-017 (FR-019):** El registro de auditoría funcional almacena y permite consultar quién realizó el cambio, cuándo se ejecutó, qué datos cambiaron y el motivo del cambio (ej. justificación de ajuste de inventario).
* **AC-018 (FR-020):** El cliente móvil soporta el registro continuo de recolecciones y movimientos físicos a pesar de desconexiones forzadas de la red de datos.
* **AC-019 (FR-021):** Al reactivar la red, las operaciones offline se procesan en el backend; transacciones repetidas no aplican efectos múltiples.

---

## 7. Traceability Matrix

### Relaciones Funcionales

| Actor (ACT) | Functional Reqs (FR) | Business Rules (BR) | Use Cases (UC) | Acceptance Criteria (AC) |
|---|---|---|---|---|
| Todos | FR-022 | - | UC-001 | AC-020 |
| ACT-007 | FR-001 | - | UC-002 | AC-001 |
| ACT-002 | FR-002, FR-003 | BR-004 | UC-003, UC-004 | AC-002, AC-003 |
| ACT-003 | FR-004, FR-005 | BR-003, BR-004 | UC-005, UC-006 | AC-004, AC-005 |
| ACT-001 | FR-007, FR-017, FR-018 | BR-005 | UC-007, UC-017, UC-018 | AC-007, AC-016 |
| ACT-005 | FR-008 | BR-009 | UC-008 | AC-008 |
| Sistema, ACT-003 | FR-006 | BR-001, BR-002, BR-003 | UC-009 | AC-006 |
| ACT-002 | FR-009, FR-010 | - | UC-010, UC-011 | AC-009, AC-010 |
| ACT-004 | FR-011, FR-012, FR-013 | BR-005 | UC-012, UC-013 | AC-011, AC-012, AC-013 |
| ACT-006 | FR-014, FR-015, FR-016 | BR-006, BR-007 | UC-014, UC-015, UC-016 | AC-014, AC-015, AC-016 |
| Todos, Sistema | FR-019 | - | - | AC-017 |
| ACT-002, Sistema | FR-020, FR-021 | BR-008, BR-010 | UC-019, UC-020 | AC-018, AC-019 |

### Impacto No Funcional (NFR Provenance)
* **NFR-001 (Seguridad):** Impacta directamente en UC-001 y transversalmente en todos los FR/UC como restricción de acceso funcional.
* **NFR-002 (Integridad Transaccional):** Crítico para el éxito atómico de UC-009 (Allocation), UC-013 (Despacho) y UC-016 (Aplicación de pago).
* **NFR-003 (Trazabilidad Inmutable):** Soporta estructuralmente el requisito FR-019 (Auditoría funcional).
* **NFR-004 (Concurrencia Segura):** Indispensable para sostener BR-001 durante la ejecución concurrente de UC-009.
* **NFR-005 (Idempotencia):** Soporta BR-008 e impacta la viabilidad y corrección de UC-020 (Sincronización posterior).
* **NFR-006 (Degradación Elegante) y NFR-007 (Conflictos):** Fundamentos funcionales y técnicos de FR-020, FR-021, UC-019 y UC-020.
* **NFR-008 (Performance y Escalabilidad):** Transversal. Afecta directamente la UX de UC-007 (Creación de pedidos) y UC-010 (Picking).
* **NFR-009 (Accesibilidad y Responsive):** Impacta directamente la superficie del Customer Portal (ACT-001) para UC-007, UC-017 y UC-018.
* **NFR-010 (Mantenibilidad), NFR-011 (Compatibilidad Móvil), NFR-012 (Observabilidad):** Requisitos estructurales y arquitectónicos transversales (system-wide) a todo el flujo de negocio, sin limitar casos de uso concretos.

### Requirements Provenance (Orígenes de Verdad)

| Artefacto / Fuente Base | Grupos de Requisitos Justificados |
|---|---|
| **EVD-DISCOVERY-001 (MVP Scope)** | FR-001 a FR-018 (Operaciones de flujo físico y AR). |
| **EVD-TARGET-001 (Target Outcomes)** | FR-019, FR-020, FR-021, NFR-006, NFR-007, UC-019, UC-020. |
| **Product Truth (Product Definition)** | ACT-001 a ACT-007, BR-001 a BR-010 (Invariantes core), FR-022 (Identity/Accesos). |

---

## 8. Domain Validation

Revisión formal de los 14 candidatos funcionales del Discovery:

1. **Identity:** Fundamental y transversal para NFR-001.
2. **Customers / Catalog:** Cohesivos como base comercial (Maestros).
3. **Warehousing / Inventory:** Dominio core del sistema (Estructura vs. Contenido físico).
4. **Inbound / Fulfillment / Operations / Shipping:** Definen el flujo logístico. Las tareas móviles operan sobre las entidades generadas aquí.
5. **Orders:** Representa el ciclo comercial inicial, independiente del stock físico hasta el momento exacto de Allocation.
6. **Finance:** Dominio altamente desacoplado del movimiento físico de mercancía, encargado exclusivamente del ciclo Accounts Receivable (FR-013 a FR-016).
7. **Audit / Reporting / Integrations:** Pilares transversales de soporte.

**Conclusión:** Se valida la procedencia funcional de los candidatos como soporte para los FR/UC. Sin embargo, no se consagran todavía como módulos arquitectónicos definitivos, agregados, ni boundaries técnicos. Su consolidación final y diseño de dependencias ocurrirá obligatoriamente en `architecture_security_data`.

---

## 9. Mobile Licensing Decision

Blueprint v0.5.4 define el check `requirements.mobile_licensing_decision` aplicable dado que `android: true`.

* **Decisión explícita:** `mobile_licensing: false`
* **Sustento:** La aplicación SENTAI Operator Mobile tiene habilitado soporte Android. Sin embargo, la decisión del producto es que SENTAI no utilizará ni dependerá del sistema opcional de mobile licensing definido por Blueprint.
* **Implicaciones:** Esta exclusión resuelve satisfactoriamente el check condicional para el Blueprint, dejando `PASS` el requisito y excluyendo este control específico de los bloqueos de release. Las obligaciones normativas para garantizar la identidad del operario siguen cubiertas estrictamente por `NFR-001` y FR-022.

---

## 10. Approval & Closure

* **Evidencia:** `EVD-REQ-001` ha sido revisada y aprobada humanamente.
* **Estado de Fase:** La fase `requirements_domain` queda formalmente cerrada y finalizada.
* **Gate de Calidad:** El gate `requirements_ready` ha sido aprobado (PASS).
* **Restricción de Alcance:** Ninguna fase posterior (incluyendo `interface_scope_baseline`, `architecture_security_data`, etc.) ha comenzado a la fecha de este cierre.
