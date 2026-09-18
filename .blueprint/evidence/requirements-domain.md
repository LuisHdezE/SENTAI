# SENTAI - Requirements & Domain

**Blueprint Phase:** Requirements & Domain (R1)  
**Status:** READY_FOR_REVIEW  
**Artifact ID:** EVD-REQ-001  
**Type:** requirements_domain_evidence  

---

## 1. Actors & Authorization Intent

Las siguientes definiciones representan la autorización funcional a nivel de intención. **No** constituyen todavía un diseño técnico RBAC, claims JWT o roles de base de datos, lo cual se formalizará posteriormente.

* **ACT-001 Cliente:**
  * **Propósito:** Actor externo comercial.
  * **Responsabilidades:** Consultar catálogo, crear pedidos, rastrear despachos y revisar deudas.
  * **Capacidades Autorizadas:** Leer catálogo activo, crear pedidos propios, ver estado de cuenta propio.
  * **Restricciones:** No puede ver datos de otros clientes ni operar el almacén.
  * **Superficies:** Customer Portal Web.

* **ACT-002 Operario de almacén:**
  * **Propósito:** Fuerza laboral en el piso.
  * **Responsabilidades:** Ejecutar tareas físicas (recepción, put-away, picking, packing).
  * **Capacidades Autorizadas:** Recibir ASN asignados, mover inventario (put-away), hacer picking de órdenes asignadas.
  * **Restricciones:** No puede crear maestros, confirmar pedidos comerciales ni gestionar finanzas.
  * **Superficies:** Operator Mobile (Android/iOS).

* **ACT-003 Supervisor / Jefe de almacén:**
  * **Propósito:** Control operativo.
  * **Responsabilidades:** Monitorear productividad, controlar inventario, resolver excepciones, asignar tareas.
  * **Capacidades Autorizadas:** Ajustar inventario, bloquear ubicaciones, forzar asignaciones, ver dashboards operativos.
  * **Restricciones:** No aprueba créditos ni gestiona pagos.
  * **Superficies:** Backoffice Web.

* **ACT-004 Planificador de despacho:**
  * **Propósito:** Optimización de salida.
  * **Responsabilidades:** Agrupar pedidos empacados y organizar envíos.
  * **Capacidades Autorizadas:** Crear manifiestos de carga, asignar rutas básicas, autorizar despacho final.
  * **Restricciones:** Solo opera sobre pedidos ya liberados por almacén.
  * **Superficies:** Backoffice Web.

* **ACT-005 Comercial:**
  * **Propósito:** Gestión de la relación con el cliente.
  * **Responsabilidades:** Gestionar límites de crédito, resolver disputas de pedidos.
  * **Capacidades Autorizadas:** Aprobar pedidos retenidos por crédito, modificar datos comerciales del cliente.
  * **Restricciones:** No opera almacén ni consolida pagos (separación de funciones con Finanzas).
  * **Superficies:** Backoffice Web.

* **ACT-006 Finanzas:**
  * **Propósito:** Control de flujo de caja y cartera.
  * **Responsabilidades:** Administrar cobranza, registrar pagos, aplicar pagos a facturas/obligaciones.
  * **Capacidades Autorizadas:** Registrar transacciones bancarias, saldar obligaciones (Accounts Receivable).
  * **Restricciones:** No modifica pedidos comerciales ni opera almacén.
  * **Superficies:** Backoffice Web.

* **ACT-007 Administrador:**
  * **Propósito:** Mantenimiento del sistema.
  * **Responsabilidades:** Configuración global, alta/baja de usuarios, catálogos maestros críticos.
  * **Capacidades Autorizadas:** Totalidad de la configuración técnica (super-usuario).
  * **Superficies:** Backoffice Web.

---

## 2. Functional Requirements (FR)

Los siguientes requisitos definen el comportamiento exigido para el MVP, sin prescribir su implementación técnica ni diseño de base de datos.

* **FR-001 Maestros esenciales:** El sistema debe permitir la gestión (CRUD) de productos, clientes, almacenes, zonas y posiciones físicas.
* **FR-002 Inbound / Recepción:** El sistema debe permitir registrar la entrada de mercancía contra un aviso (ASN) o de manera ciega.
* **FR-003 Put-away:** El sistema debe proveer una funcionalidad para trasladar la mercancía recibida a ubicaciones definitivas, de forma manual o sugerida.
* **FR-004 Gestión granular de inventario:** El stock debe trazarse unívocamente por Ubicación, Producto, Lote, Número de Serie (si aplica) y Estado (Disponible, Bloqueado, etc.).
* **FR-005 Movimientos y ajustes:** Debe ser posible mover stock entre ubicaciones y realizar ajustes (mermas, sobrantes) con motivo de auditoría.
* **FR-006 Reserva (Allocation):** El sistema debe permitir reservar inventario específico para satisfacer un pedido comercial confirmado.
* **FR-007 Customer order creation:** El Customer Portal debe permitir a los clientes armar y guardar pedidos a partir del catálogo activo.
* **FR-008 Validación de pedido:** El Backoffice debe permitir revisar y aprobar (comercial/crédito) pedidos ingresados.
* **FR-009 Picking:** La aplicación móvil debe guiar al operario para recolectar el stock reservado de sus ubicaciones.
* **FR-010 Packing:** El sistema debe permitir registrar el empaquetado del picking en bultos (LPNs).
* **FR-011 Shipping planning:** Debe ser posible agrupar bultos empaquetados en un manifiesto de despacho (Shipment).
* **FR-012 Dispatch (Despacho):** El sistema debe registrar la salida física del almacén, deduciendo el stock OnHand.
* **FR-013 Accounts Receivable (Cuentas por cobrar):** Al despachar, el sistema debe generar la obligación financiera de cobro correspondiente al pedido.
* **FR-014 Pagos (Registro y Aplicación):** El sistema debe permitir registrar un pago recibido y aplicarlo parcial o totalmente a una o más obligaciones abiertas.
* **FR-015 Aging y Saldos:** El backoffice debe mostrar el estado general de la cartera (deuda vigente, vencida, saldos pendientes).
* **FR-016 Customer Accounts Payable (AP):** El cliente debe poder visualizar en su portal las obligaciones pendientes de pago.
* **FR-017 Estado de cuenta:** El cliente y finanzas deben poder visualizar un estado de cuenta consolidado con deudas y pagos aplicados.
* **FR-018 Auditoría:** Todo cambio crítico de inventario, estado de pedido o transacción financiera debe registrar quién, cuándo y por qué lo hizo.
* **FR-019 Operación móvil offline-first:** El operario debe poder seguir ejecutando tareas previamente descargadas (ej. picking) sin conexión a la red.
* **FR-020 Sincronización móvil:** La aplicación móvil debe transmitir las tareas ejecutadas localmente al recuperar la conexión, para su conciliación.

---

## 3. Non-Functional Requirements (NFR)

Cualidades del sistema necesarias para cumplir el alcance de SENTAI, evitando definir metas numéricas prematuras donde no hay aprobación de producto.

* **NFR-001 Seguridad (Autenticación y Autorización):** Todo endpoint y vista debe estar protegido y autorizar operaciones estrictamente basadas en la identidad del actor.
* **NFR-002 Integridad transaccional:** Transacciones críticas (ej. despachos, pagos) deben ser ACID, previniendo estados inconsistentes de base de datos.
* **NFR-003 Trazabilidad inmutable:** Los eventos de dominio relevantes (cambios de estado de pedidos, movimientos físicos) no deben ser borrados, sino apendizados o registrados en un log auditable.
* **NFR-004 Concurrencia segura:** El motor de base de datos y la lógica de negocio deben usar bloqueos (locks) optimistas o pesimistas apropiados para evitar condiciones de carrera (ej. sobre-reservas).
* **NFR-005 Idempotencia:** Operaciones de mutación propensas a reintento (ej. pago desde web o sincronización desde móvil) deben ser idempotentes para evitar duplicación.
* **NFR-006 Disponibilidad y degradación elegante:** Si el servidor está inaccesible, las terminales móviles deben continuar operando en modo desconectado.
* **NFR-007 Recuperación ante conflictos:** El sistema deberá definir mecanismos de resolución de conflictos cuando la sincronización móvil colisione con el estado del servidor.
* **NFR-008 Performance y Escalabilidad:** El backend debe responder con latencias razonables (objetivo pendiente de definir por negocio) bajo carga operativa normal.
* **NFR-009 Accesibilidad y Responsive:** El Customer Portal debe ser usable en dispositivos de escritorio y tabletas con prácticas modernas web.
* **NFR-010 Mantenibilidad arquitectónica:** El backend debe seguir el modelo Clean Architecture + Modular Monolith aprobado como directriz para el proyecto.
* **NFR-011 Compatibilidad Móvil:** La aplicación Operator Mobile debe compilar e instalarse nativamente en Android e iOS (KMP `cross_platform`).
* **NFR-012 Observabilidad:** La arquitectura debe facilitar la inyección de herramientas de logging centralizado y monitoreo (APM).

---

## 4. Business Rules (BR)

Formalización de las reglas de negocio reales (invariantes de dominio).

* **BR-001 Limite de Reserva:** El sistema nunca puede reservar más del inventario físicamente disponible. `Reserved <= OnHand`.
* **BR-002 Naturaleza de la Reserva:** Una reserva (Allocation) compromete el inventario lógicamente para un pedido, pero no deduce el `OnHand` ni constituye una salida física.
* **BR-003 Disponibilidad:** El stock disponible para futuras asignaciones es una fórmula derivada: `Available = OnHand - Reserved`.
* **BR-004 Estados de Bloqueo:** El inventario con estado Cuarentena, Dañado, Vencido o Bloqueado lógicamente no se considera "Available" para despachos comerciales estándar.
* **BR-005 Deuda Consolidada por Perspectiva:** Una misma obligación financiera generada por un despacho se refleja como "Cuentas por Cobrar" (AR) para Backoffice y "Cuentas por Pagar" (AP) para el cliente, sin duplicar los registros en el sistema core.
* **BR-006 Desacoplamiento de Pagos:** El registro de ingreso de dinero (Pago) y el cruce contable con la deuda (Aplicación de pago) son entidades separadas. Un pago puede quedar a cuenta sin aplicar.
* **BR-007 Obligación Saldada:** Una factura u obligación se considera cerrada/saldada únicamente cuando el total de pagos aplicados hacia ella es igual a su monto total original.
* **BR-008 Regla de Idempotencia de Reintentos:** Un fallo de red durante un despacho o un pago que provoque un reintento no debe generar dos despachos físicos ni dos ingresos de dinero lógico.
* **BR-009 Autoridad del Servidor (SSOT):** En las sincronizaciones móviles offline, el servidor es la fuente única de verdad. La operación desconectada no autoriza forzar un estado que viole las reglas BR-001 o BR-004 en el servidor; dichas transacciones deben ser rechazadas o puestas en excepción.

---

## 5. Use Cases (UC)

Casos de uso formales que estructuran los journeys de Target Definition sin prescribir diseño API.

* **UC-001 Autenticación (General)**
  * Actor: Todos
  * Objetivo: Iniciar sesión en el sistema.
  * Flujo: Usuario ingresa credenciales, sistema valida y otorga sesión/token.

* **UC-002 Mantener Catálogo de Productos**
  * Actor: Administrador / Supervisor
  * Objetivo: Crear o actualizar SKUs.
  * Flujo: Actor provee detalles del producto (código, nombre, uom). Sistema persiste y expone en Customer Portal.

* **UC-003 Recibir Mercancía (Inbound)**
  * Actor: Operario / Supervisor
  * Objetivo: Ingresar stock físico al sistema.
  * Flujo: Operario escanea o selecciona orden de compra, ingresa cantidades recibidas y producto entra al OnHand en zona de recepción.

* **UC-004 Ejecutar Put-away**
  * Actor: Operario
  * Objetivo: Mover stock desde recepción a estantería.
  * Flujo: El operario toma mercancía en recepción, la escanea, escanea la posición de destino y confirma el movimiento de stock.

* **UC-005 Crear Pedido Comercial**
  * Actor: Cliente
  * Objetivo: Registrar intención de compra.
  * Flujo: Cliente selecciona ítems del catálogo, define cantidades y confirma.
  * Postcondición: Pedido creado en estado "Draft" o "Pending Review".

* **UC-006 Validar y Confirmar Pedido**
  * Actor: Comercial
  * Objetivo: Aprobar despacho del pedido.
  * Flujo: Comercial revisa el pedido y la cartera del cliente. Si todo es correcto, aprueba el pedido.

* **UC-007 Reservar Inventario (Allocation)**
  * Actor: Sistema / Supervisor
  * Objetivo: Garantizar stock para un pedido confirmado.
  * Flujo: El sistema busca stock disponible, incrementa `Reserved` y asocia el stock al pedido.
  * Excepción: Si no hay `Available`, el pedido queda en "Backorder".

* **UC-008 Ejecutar Picking (Mobile)**
  * Actor: Operario
  * Objetivo: Recolectar stock reservado físicamente.
  * Flujo: Operario ve tareas asignadas en app móvil, se dirige a la ubicación, escanea producto/lote y confirma recolección de las cantidades.

* **UC-009 Planificar y Despachar Envío**
  * Actor: Planificador / Operario
  * Objetivo: Dar salida final a mercancía.
  * Flujo: Se consolidan cajas de varios pedidos en un envío. Se marca como despachado.
  * Postcondición: El stock `OnHand` y `Reserved` se deducen. Se genera obligación por cobrar.

* **UC-010 Registrar y Aplicar Pago**
  * Actor: Finanzas
  * Objetivo: Saldar deudas.
  * Flujo: Finanzas registra una transferencia bancaria, selecciona la(s) obligación(es) del cliente y aplica montos.
  * Postcondición: Saldo de la obligación se actualiza.

* **UC-011 Consultar Estado de Cuenta**
  * Actor: Cliente
  * Objetivo: Ver histórico financiero.
  * Flujo: Entra al portal y visualiza listado de obligaciones pasadas y presentes y saldo global.

* **UC-012 Operar y Sincronizar (Offline)**
  * Actor: Operario / Sistema
  * Objetivo: Proveer continuidad de negocio sin internet.
  * Flujo: Operario descarga tareas (ej. Picking). Pierde red. Ejecuta tareas localmente en SQLite. Recupera red. Sistema sincroniza de fondo los eventos al backend aplicando lógica de conciliación.

---

## 6. Acceptance Criteria (AC)

Criterios formales para considerar satisfechos los requisitos funcionales.

* **AC-001 (para FR-001):** La plataforma cuenta con interfaces operativas donde los administradores pueden listar, crear y deshabilitar zonas, posiciones y productos sin arrojar excepciones de base de datos o aplicación.
* **AC-002 (para FR-006, BR-001):** Si el sistema posee 100 unidades OnHand y 90 Reserved, un nuevo intento de reserva por 20 unidades de ese lote exacto debe fracasar de forma controlada y alertar sobre inventario insuficiente.
* **AC-003 (para FR-004):** Todas las pantallas y reportes de inventario deben segmentar los resultados explícitamente mostrando Ubicación, Lote y Estado; sumarizando el OnHand correctamente.
* **AC-004 (para FR-012, FR-013):** Al presionar "Confirmar Despacho", la base de datos debe, en una sola transacción atómica, descontar el inventario físico y generar un registro de obligación de pago asociado al cliente del pedido.
* **AC-005 (para FR-014, BR-007):** Un pago aplicado que cubra exactamente el total pendiente de una deuda debe cambiar automáticamente el estado de dicha deuda a "Pagado/Saldado".
* **AC-006 (para FR-018, NFR-006, UC-012):** La aplicación móvil (compilada nativa) debe poder cerrarse y volverse a abrir sin internet (ej. forzar "modo avión") y retener las tareas de picking previamente sincronizadas para su ejecución.

---

## 7. Traceability Matrix

| Actor / Source | Functional Req (FR) | Business Rule (BR) | Use Case (UC) | Acceptance Criteria (AC) |
|---|---|---|---|---|
| ACT-007 | FR-001 | - | UC-002 | AC-001 |
| ACT-002 | FR-002, FR-003 | - | UC-003, UC-004 | - |
| ACT-003 | FR-004, FR-005 | BR-004 | UC-005, UC-006 | AC-003 |
| ACT-001 | FR-007 | BR-009 | UC-005, UC-011 | - |
| Sistema / ACT-004 | FR-006 | BR-001, BR-002, BR-003 | UC-007 | AC-002 |
| ACT-002 | FR-009, FR-019 | BR-009, BR-011 | UC-008, UC-012 | AC-006 |
| ACT-004 | FR-011, FR-012, FR-013 | BR-005, BR-008 | UC-009 | AC-004 |
| ACT-006 | FR-014, FR-015 | BR-006, BR-007 | UC-010 | AC-005 |

**Non-Functional Relational Traceability:**
* `NFR-002 (Transaccionalidad)` e `NFR-005 (Idempotencia)` impactan directamente en `UC-009 (Despacho)` y `UC-010 (Pago)`.
* `NFR-006 (Degradación)` y `NFR-007 (Conflictos)` rigen por completo la viabilidad de `UC-012 (Sincronización)`.
* `NFR-001 (Seguridad)` rige transversalmente a todos los UC para garantizar el cumplimiento de restricciones de cada ACT.

---

## 8. Domain Validation

En la fase Discovery se plantearon 14 candidatos iniciales de dominios/bounded contexts funcionales. Tras la validación de requisitos y flujos operativos, se postula la siguiente validación estructural (las decisiones arquitectónicas finales, aggregates y módulos Laravel pertenecen estrictamente a la fase `architecture_security_data`):

* **Identity:** Requerido para Autenticación. Altamente transversal.
* **Customers / Catalog:** Necesarios como maestros Core. Su separación o unificación como "Maestros" será determinada arquitectónicamente.
* **Warehousing / Inventory:** Son altamente cohesivos. Warehousing provee la estructura y el Inventario representa las existencias dentro de ella. Comparten un ciclo de vida íntimamente ligado al piso físico.
* **Inbound / Fulfillment / Operations / Shipping:** Representan los procesos logísticos (Inbound, Outbound, Tareas Móviles). Es muy probable que arquitectónicamente Operations sea una orquestación transversal a Inbound y Fulfillment.
* **Orders:** Dominio netamente comercial (captura de demanda del Customer Portal). Independiente del stock físico hasta el momento de Allocation.
* **Finance:** Requiere una clara autonomía para manejar Accounts Receivable separadamente del movimiento logístico (alta separación de responsabilidades para facturación/pagos).
* **Audit / Reporting / Integrations:** Dominios de soporte transversal y observabilidad.

> **Conclusión de Validación:** Las hipótesis son razonables y cubren los requisitos, pero su granularidad (14 módulos) podría generar sobre-ingeniería para un MVP en Modular Monolith. Durante `architecture_security_data` se decidirá si se fusionan candidatos con alta cohesión (e.g. Inbound y Fulfillment en un gran "Logistics"). Ningún módulo está formalmente aprobado aún.

---

## 9. Mobile Licensing Decision

Blueprint v0.5.4 define un check condicional `requirements.mobile_licensing_decision` para los proyectos que tengan `android: true`.

**Decisión del proyecto SENTAI:**
* **`mobile_licensing: false`**

**Evidencia e Implicaciones:**
* SENTAI Operator Mobile tiene habilitada la compilación y soporte para Android.
* La decisión de producto (establecida en `.blueprint/project.yaml`) es **no requerir** ni implementar el sistema opcional de licensing de aplicaciones (DRM / verificación de licencias de app stores) documentado por el Blueprint.
* La distribución de la aplicación (e.g. MDM interno o sideloading en dispositivos de almacén) no depende de esquemas públicos de licenciamiento.
* Esta decisión formaliza el check en `PASS` y excluye el gate `mobile_licensing_ready` del ciclo de release de la aplicación, pero NO relaja ninguna regla sobre autenticación tradicional de operarios (`NFR-001`).
