# SENTAI - Requirements & Domain

**Blueprint Phase:** Requirements & Domain (R1)  
**Status:** READY_FOR_REVIEW  
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
  * **Propósito:** Optimización de salida.
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
* **FR-019 Auditoría funcional:** Todo cambio crítico de inventario, estado de pedido o transacción financiera debe registrar trazabilidad auditable (quién, cuándo, qué).
* **FR-020 Operación móvil offline-first:** El operario móvil debe poder continuar ejecutando tareas operativas descargadas durante pérdidas de conectividad.
* **FR-021 Sincronización posterior:** El sistema debe procesar y conciliar las tareas ejecutadas fuera de línea cuando el dispositivo recupere conexión.

---

## 3. Non-Functional Requirements (NFR)

* **NFR-001 Seguridad:** Toda operación debe requerir una sesión válida y verificar la autorización funcional del actor antes de ejecutarse.
* **NFR-002 Integridad transaccional:** Las operaciones críticas (e.g. despachos, pagos) deben exhibir consistencia atómica observable (todo o nada), previniendo estados parciales.
* **NFR-003 Trazabilidad inmutable:** Las acciones relevantes deben generar evidencia durable y auditable que no pueda ser alterada sin dejar rastro.
* **NFR-004 Concurrencia segura:** El sistema debe prevenir explícitamente condiciones de carrera y sobre-reservas bajo acceso concurrente, garantizando exactitud operativa.
* **NFR-005 Idempotencia:** Las operaciones de mutación susceptibles a reintentos (ej. originadas por pérdida de red) deben procesarse exactamente una vez para evitar duplicidades funcionales.
* **NFR-006 Disponibilidad y degradación elegante:** Ante indisponibilidad de red, la interfaz móvil debe degradarse permitiendo operaciones locales seguras sin bloquear al usuario.
* **NFR-007 Recuperación ante conflictos:** El sistema debe proveer mecanismos claros para resolver colisiones cuando la sincronización posterior contradiga el estado actualizado del servidor.
* **NFR-008 Performance y Escalabilidad:** El sistema debe mantener tiempos de respuesta interactivos bajo carga operativa estándar de un almacén.
* **NFR-009 Accesibilidad y Responsive:** El Customer Portal Web debe ser operable en múltiples tamaños de pantalla y dispositivos.
* **NFR-010 Mantenibilidad arquitectónica:** El diseño debe facilitar la evolución independiente de dominios (dirección aprobada: Clean Architecture + Modular Monolith).
* **NFR-011 Compatibilidad Móvil:** La aplicación debe soportar ecosistemas Android e iOS a partir de una base unificada.
* **NFR-012 Observabilidad:** El sistema debe exponer evidencia diagnóstica, correlación de eventos y logs para facilitar soporte y monitoreo.

---

## 4. Business Rules (BR)

* **BR-001 Límite y Elegibilidad de Reserva:** La reserva comercial está estrictamente limitada al inventario físicamente disponible y en estado elegible. `Reserved <= OnHand_Eligible`.
* **BR-002 Naturaleza de la Reserva:** Una asignación (Allocation) compromete stock lógicamente, pero no deduce el stock físico (`OnHand`) ni se considera una salida.
* **BR-003 Cálculo de Disponibilidad:** El stock reportado como libre (Available) se calcula deduciendo las reservas sobre el inventario físico elegible: `Available = OnHand_Eligible - Reserved`.
* **BR-004 Estados de Bloqueo:** El inventario marcado como bloqueado, en cuarentena, dañado o vencido no es elegible para satisfacer pedidos comerciales estándar.
* **BR-005 Deuda Consolidada por Perspectiva:** Una misma obligación financiera generada por un despacho existe como una única entidad, visible como "Cuentas por Cobrar" internamente y "Cuentas por Pagar" externamente, sin duplicación lógica.
* **BR-006 Desacoplamiento de Pagos:** El ingreso de un pago y la liquidación contable de una obligación son independientes. Un pago puede existir como saldo a favor sin aplicarse inmediatamente.
* **BR-007 Obligación Saldada:** Una obligación/documento financiero se considera cerrado únicamente cuando el total de sus pagos aplicados iguala el monto de la obligación.
* **BR-008 Regla de Idempotencia de Reintentos:** Un reintento técnico de una operación ya consumada (ej. un pago o un despacho) no debe generar un segundo despacho físico ni un segundo registro de pago.
* **BR-009 Restricciones de Crédito:** La aprobación comercial de un pedido debe respetar los límites de deuda tolerados para el cliente.
* **BR-010 Autoridad del Servidor (SSOT):** En sincronización offline, el servidor es la fuente única de la verdad. La operación desconectada que resulte inválida frente al estado del servidor (ej. stock ya consumido) debe generar un conflicto rastreable, no forzar una invalidez.

---

## 5. Use Cases (UC)

* **UC-001 Autenticación / Acceso**
  * Actor: Todos
  * Objetivo: Acceder a las funciones autorizadas.
  * Precondiciones: Usuario registrado.
  * Flujo: Usuario ingresa credenciales; el sistema establece una sesión funcional validada.
  * Excepciones: Credenciales inválidas.
  * Postcondición: El actor puede interactuar con el sistema.
  * Relaciones: FR-001, NFR-001

* **UC-002 Mantener Maestros Autorizados**
  * Actor: Administrador
  * Objetivo: Proveer datos de referencia.
  * Precondiciones: Sesión con privilegios de admin.
  * Flujo: Actor ingresa datos de entidades (clientes, productos, almacenes); sistema valida y persiste.
  * Postcondición: Los datos están disponibles para transacciones.
  * Relaciones: FR-001

* **UC-003 Recibir Mercancía**
  * Actor: Operario / Supervisor
  * Objetivo: Ingresar stock al almacén.
  * Precondiciones: Existe un ASN válido.
  * Flujo: Actor selecciona el ASN, verifica cantidades recibidas físicamente y confirma.
  * Excepciones: Discrepancias de cantidad.
  * Postcondición: Stock registrado en zona de recepción.
  * Relaciones: FR-002, BR-004

* **UC-004 Ejecutar Put-away**
  * Actor: Operario
  * Objetivo: Ubicar stock en estantería.
  * Precondiciones: Stock existente en recepción.
  * Flujo: Actor indica la mercancía y la ubicación destino, confirmando el traslado.
  * Postcondición: Stock actualizado con nueva ubicación.
  * Relaciones: FR-003, FR-004

* **UC-005 Consultar Inventario**
  * Actor: Supervisor / Comercial / Operario
  * Objetivo: Conocer disponibilidad.
  * Precondiciones: Ninguna particular.
  * Flujo: Actor consulta por producto/ubicación; sistema calcula y muestra OnHand, Reservas y Disponibilidad.
  * Postcondición: Visibilidad de stock exacto.
  * Relaciones: FR-004, BR-003, BR-004

* **UC-006 Mover / Ajustar Inventario**
  * Actor: Supervisor
  * Objetivo: Corregir realidad física.
  * Precondiciones: Privilegios suficientes.
  * Flujo: Actor declara ajuste (aumento/disminución) y justifica; sistema aplica el cambio.
  * Postcondición: Inventario actualizado, rastro de auditoría generado.
  * Relaciones: FR-005, FR-019

* **UC-007 Crear Pedido Comercial**
  * Actor: Cliente
  * Objetivo: Solicitar mercancía.
  * Precondiciones: Autenticado como cliente.
  * Flujo: Selecciona productos, especifica cantidades y guarda la solicitud.
  * Postcondición: Solicitud registrada a la espera de validación.
  * Relaciones: FR-007

* **UC-008 Validar / Confirmar Pedido**
  * Actor: Comercial
  * Objetivo: Autorizar ejecución operativa.
  * Precondiciones: Pedido no validado existente.
  * Flujo: Revisa pedido y reglas de crédito; aprueba la solicitud.
  * Excepciones: Rechaza por crédito insuficiente.
  * Postcondición: Pedido pasa a estado accionable por logística.
  * Relaciones: FR-008, BR-009

* **UC-009 Reservar Inventario (Allocation)**
  * Actor: Sistema / Supervisor
  * Objetivo: Comprometer stock para un pedido.
  * Precondiciones: Pedido validado comercialmente.
  * Flujo: Sistema intenta asignar stock elegible disponible a las líneas del pedido.
  * Excepciones: Stock insuficiente (queda pendiente de asignación total o parcial).
  * Postcondición: Stock reservado, pedido listo para recolección.
  * Relaciones: FR-006, BR-001, BR-002, NFR-004

* **UC-010 Ejecutar Picking**
  * Actor: Operario
  * Objetivo: Recolectar stock reservado.
  * Precondiciones: Pedido con stock reservado. Tareas descargadas en app móvil.
  * Flujo: Operario llega a ubicación, confirma producto/cantidad extraída.
  * Postcondición: Tarea de picking marcada como completada.
  * Relaciones: FR-009, FR-020

* **UC-011 Ejecutar Packing**
  * Actor: Operario / Supervisor
  * Objetivo: Empaquetar recolección en bultos de salida.
  * Precondiciones: Picking finalizado.
  * Flujo: Actor declara en qué paquetes/bultos se empaca la mercancía.
  * Postcondición: Bultos generados y listos para envío.
  * Relaciones: FR-010

* **UC-012 Planificar Envío**
  * Actor: Planificador
  * Objetivo: Consolidar entregas.
  * Precondiciones: Bultos disponibles.
  * Flujo: Agrupa bultos de múltiples pedidos en un plan de transporte.
  * Postcondición: Plan de envío creado.
  * Relaciones: FR-011

* **UC-013 Despachar Mercancía**
  * Actor: Planificador / Operario
  * Objetivo: Salida definitiva del almacén y generación de deuda.
  * Precondiciones: Plan de envío listo.
  * Flujo: Confirma la salida física de los bultos. Sistema descuenta stock definitivo y emite la obligación financiera.
  * Postcondición: Stock descontado; cuenta por cobrar creada.
  * Relaciones: FR-012, FR-013, NFR-002, BR-005

* **UC-014 Consultar Cartera**
  * Actor: Finanzas / Comercial
  * Objetivo: Revisar salud de cuentas por cobrar.
  * Precondiciones: Ninguna.
  * Flujo: Visualiza saldos pendientes agrupados por cliente o antigüedad.
  * Postcondición: Reporte de cartera obtenido.
  * Relaciones: FR-016

* **UC-015 Registrar Pago Recibido**
  * Actor: Finanzas
  * Objetivo: Asentar ingreso de fondos.
  * Precondiciones: Evidencia de pago externo.
  * Flujo: Ingresa monto, origen y fecha del pago recibido.
  * Postcondición: Registro de pago en sistema.
  * Relaciones: FR-014, BR-006

* **UC-016 Aplicar Pago a Deuda**
  * Actor: Finanzas
  * Objetivo: Disminuir saldo de obligaciones.
  * Precondiciones: Pago registrado con saldo y obligación abierta.
  * Flujo: Asocia monto del pago a una obligación concreta.
  * Postcondición: Saldo de la obligación se reduce, pudiendo quedar saldada.
  * Relaciones: FR-015, BR-007

* **UC-017 Consultar Obligaciones (Customer Portal)**
  * Actor: Cliente
  * Objetivo: Conocer deuda propia.
  * Precondiciones: Autenticado.
  * Flujo: Accede al portal y revisa deudas abiertas.
  * Postcondición: Obtiene vista clara de cuentas por pagar.
  * Relaciones: FR-017, BR-005

* **UC-018 Consultar Estado de Cuenta**
  * Actor: Cliente / Finanzas
  * Objetivo: Revisar historial.
  * Precondiciones: Autenticado.
  * Flujo: Genera reporte cruzando obligaciones generadas y pagos aplicados.
  * Postcondición: Resumen histórico obtenido.
  * Relaciones: FR-018

* **UC-019 Operar Tareas Móviles Offline**
  * Actor: Operario
  * Objetivo: Continuidad operativa sin red.
  * Precondiciones: Conexión previa exitosa para descarga.
  * Flujo: El usuario pierde conectividad; realiza registros operativos que se persisten localmente en su dispositivo de manera durable; la interfaz responde con normalidad.
  * Postcondición: Tareas completadas localmente.
  * Relaciones: FR-020, NFR-006

* **UC-020 Sincronizar Operaciones Pendientes**
  * Actor: Sistema / Operario
  * Objetivo: Conciliar operaciones offline.
  * Precondiciones: Conectividad recuperada, operaciones locales pendientes.
  * Flujo: El dispositivo emite los eventos hacia el servidor. El servidor procesa validando las reglas.
  * Excepciones: Operación rechazada por reglas de negocio; se notifica conflicto al usuario.
  * Postcondición: Operaciones conciliadas y reflejadas en fuente maestra.
  * Relaciones: FR-021, NFR-005, NFR-007, BR-010

---

## 6. Acceptance Criteria (AC)

* **AC-001 (FR-001):** Es posible realizar operaciones CRUD exitosas sobre entidades maestras sin errores funcionales, respetando las restricciones de acceso.
* **AC-002 (FR-002):** Un intento de recepción que no se referencie a un ASN válido es rechazado explícitamente por el sistema.
* **AC-003 (FR-003):** Se verifica visualmente el traslado de stock de zona de recepción a posición regular.
* **AC-004 (FR-004):** Toda consulta de inventario refleja consistentemente combinaciones de ubicación, lote y estado sin agregaciones erróneas.
* **AC-005 (FR-005):** Un ajuste de inventario actualiza el stock disponible y deja registro identificable de la acción.
* **AC-006 (FR-006):** Un intento de reserva comercial que excede el stock elegible (Available) no se materializa y alerta de insuficiencia.
* **AC-007 (FR-007):** Un cliente obtiene confirmación exitosa de la creación de su pedido para posterior evaluación.
* **AC-008 (FR-008):** La validación exitosa de un pedido por un comercial lo hace visible para tareas de asignación logística.
* **AC-009 (FR-009):** La finalización del picking confirma la extracción del stock de su ubicación original y lo asocia operativamente a la orden.
* **AC-010 (FR-010):** El resultado del packing es un registro consolidado de paquetes que encapsulan los productos recolectados.
* **AC-011 (FR-011):** Varios paquetes pueden agruparse en un plan de envío común sin conflictos de asignación.
* **AC-012 (FR-012):** Al confirmar despacho, el stock físico desaparece permanentemente del recuento total del almacén.
* **AC-013 (FR-013):** Simultáneamente al despacho, se crea de forma observable una obligación financiera por el monto acordado.
* **AC-014 (FR-014):** Se puede comprobar el aumento de saldo a favor al registrar un ingreso de dinero.
* **AC-015 (FR-015):** Aplicar un pago de igual valor a una obligación la marca automáticamente como resuelta/cerrada.
* **AC-016 (FR-016, FR-017, FR-018):** Los reportes de deuda (Aging y Estados de Cuenta) cuadran matemáticamente sumando obligaciones menos pagos aplicados.
* **AC-017 (FR-019):** Es posible identificar al autor, la fecha y el motivo en el registro de un ajuste de inventario manual.
* **AC-018 (FR-020):** La aplicación móvil permite registrar un picking completo mientras la red física del dispositivo está desactivada.
* **AC-019 (FR-021):** Tras reactivar la red, las transacciones locales aparecen en el servidor; los envíos duplicados por inestabilidad de red no duplican operaciones en el servidor.

---

## 7. Traceability Matrix

### Relaciones Funcionales

| Actor (ACT) | Functional Reqs (FR) | Business Rules (BR) | Use Cases (UC) | Acceptance Criteria (AC) |
|---|---|---|---|---|
| ACT-007 | FR-001 | - | UC-002 | AC-001 |
| ACT-002, ACT-003 | FR-002, FR-003 | - | UC-003, UC-004 | AC-002, AC-003 |
| ACT-003, ACT-005, ACT-002 | FR-004, FR-005 | BR-003, BR-004 | UC-005, UC-006 | AC-004, AC-005 |
| ACT-001 | FR-007, FR-017, FR-018 | BR-005 | UC-007, UC-017, UC-018 | AC-007, AC-016 |
| ACT-005 | FR-008 | BR-009 | UC-008 | AC-008 |
| Sistema, ACT-003 | FR-006 | BR-001, BR-002 | UC-009 | AC-006 |
| ACT-002 | FR-009, FR-010 | - | UC-010, UC-011 | AC-009, AC-010 |
| ACT-004 | FR-011, FR-012, FR-013 | BR-005 | UC-012, UC-013 | AC-011, AC-012, AC-013 |
| ACT-006 | FR-014, FR-015, FR-016 | BR-006, BR-007 | UC-014, UC-015, UC-016 | AC-014, AC-015, AC-016 |
| Todos, Sistema | FR-019 | - | - | AC-017 |
| ACT-002, Sistema | FR-020, FR-021 | BR-008, BR-010 | UC-019, UC-020 | AC-018, AC-019 |

### Impacto No Funcional (NFR Provenance)
* **NFR-001 (Seguridad):** Impacta directamente en UC-001 y transversalmente en FR-001 a FR-021 garantizando restricciones.
* **NFR-002, NFR-004 (Transaccionalidad y Concurrencia):** Críticos para asegurar el cumplimiento de BR-001 en UC-009 (Allocation) y evitar corrupción en UC-013 (Despacho).
* **NFR-003 (Trazabilidad):** Da soporte normativo a FR-019 (Auditoría funcional).
* **NFR-005 (Idempotencia):** Rige el comportamiento de UC-013, UC-015 y particularmente UC-020 ante fallos temporales.
* **NFR-006, NFR-007 (Disponibilidad y Conflictos):** Fundamentos directos de FR-020 y FR-021 y viabilizan BR-010.

---

## 8. Domain Validation

Revisión formal de los 14 candidatos funcionales del Discovery:

1. **Identity:** Fundamental y transversal para NFR-001.
2. **Customers / Catalog:** Cohesivos como base comercial (Maestros).
3. **Warehousing / Inventory:** Dominio core del sistema (Estructura vs. Contenido físico).
4. **Inbound / Fulfillment / Operations / Shipping:** Definen el flujo logístico. Las tareas móviles (Operations) operan sobre las entidades generadas por Inbound y Fulfillment.
5. **Orders:** Representa el ciclo comercial inicial, independiente del stock físico hasta el momento exacto de Allocation.
6. **Finance:** Dominio altamente desacoplado del movimiento físico de cajas, encargado exclusivamente del ciclo Accounts Receivable (FR-013 a FR-016).
7. **Audit / Reporting / Integrations:** Pilares transversales de soporte.

**Conclusión:** Se valida la procedencia funcional de los candidatos como soporte para los FR/UC. Sin embargo, no se consagran todavía como módulos arquitectónicos definitivos, agregados, ni boundaries técnicos. Su consolidación final y diseño de dependencias ocurrirá obligatoriamente en `architecture_security_data`.

---

## 9. Mobile Licensing Decision

Blueprint v0.5.4 define el check `requirements.mobile_licensing_decision` aplicable dado que `android: true`.

* **Decisión:** `mobile_licensing: false`
* **Sustento:** La distribución de la aplicación SENTAI Operator Mobile no utilizará ni dependerá del sistema opcional de licensing de aplicaciones documentado en el Blueprint.
* **Implicaciones:** El check aplicable queda `PASS`. El gate de release relacionado no bloqueará la salida a producción. No obstante, las medidas de autenticación tradicionales exigidas por `NFR-001` permanecen vigentes y obligatorias.
