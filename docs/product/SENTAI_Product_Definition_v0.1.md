# SENTAI

**Warehouse Management & Fulfillment Platform**

Documento inicial de definición de producto, alcance, arquitectura y experiencia

| Campo | Definición |
| --- | --- |
| Versión | 0.1 - Baseline inicial |
| Fecha | 16 de septiembre de 2026 |
| Estado | Documento de descubrimiento y definición preliminar |
| Producto | SENTAI |
| Enfoque | WMS + Fulfillment + Portal de clientes + Cuentas por cobrar |
| Identidad visual | Verde principal #53B559 |

> Propósito del documento
> Consolidar en una primera línea base todo lo analizado hasta el momento sobre SENTAI. Este documento no constituye todavía la especificación final del Blueprint; sirve como fuente de partida para D0 - Product Discovery & Scope y para las decisiones posteriores de dominio, arquitectura, UX, seguridad, pruebas y despliegue.

> “Un WMS pequeño pero auténtico: mercancía que entra, se ubica, se reserva, se prepara, sale y deja una huella trazable.”

# Contenido

- 1. Contexto y razón de ser

- 2. Visión del producto

- 3. Alcance funcional y fronteras

- 4. Actores y experiencias

- 5. Procesos principales

- 6. Módulos y bounded contexts

- 7. Modelo de inventario y reglas críticas

- 8. Pedidos, fulfillment y planificación de envíos

- 9. Finanzas: cuentas por cobrar y perspectiva del cliente

- 10. Aplicación móvil Android/iOS

- 11. Arquitectura y stack tecnológico

- 12. Seguridad, trazabilidad y confiabilidad

- 13. Identidad visual y UX

- 14. Demo objetivo para portafolio

- 15. Roadmap y próximos pasos del Blueprint

> Principio rector
> SENTAI se desarrollará como un producto demostrable y desplegable, no como una colección de pantallas ni como una implementación sobredimensionada. La prioridad será cerrar flujos de negocio completos con reglas reales, trazabilidad, pruebas y una demo funcional accesible desde el portafolio.

# 1. Contexto y razón de ser

SENTAI nace como proyecto de portafolio orientado al sector logístico. Su propósito es demostrar capacidad para comprender procesos operativos, traducir necesidades del negocio a soluciones técnicas, diseñar integraciones, construir APIs, modelar datos relacionales, trabajar con control de calidad y desplegar una solución utilizable.

El proyecto toma como punto de partida la idea de un Warehouse Management System (WMS), pero evita intentar cubrir de inmediato todo el universo logístico. La estrategia consiste en concentrarse en almacén, fulfillment, interacción con clientes, planificación de despachos y control de la cartera asociada a esos clientes.

## 1.1 Posicionamiento

- No se presentará como un ERP completo ni como un TMS completo.

- No se afirmará conocer la operación interna de una empresa específica.

- Sí demostrará comprensión de problemas comunes: recepción, ubicación, inventario, reserva, picking, packing, despacho, trazabilidad, crédito y pagos.

- La implementación será incremental, gobernada por Blueprint y orientada a resultados visibles desde las primeras verticales.

> Nombre del producto
> SENTAI se utilizará principalmente como marca. Conceptualmente, el término japonés “sentai” remite a una fuerza o equipo coordinado, una metáfora apropiada para módulos que cooperan para controlar la operación del almacén. No se forzará un acrónimo técnico como requisito del producto.

# 2. Visión del producto

SENTAI será una plataforma de gestión de almacenes y fulfillment que conecte la operación física con el ciclo comercial del cliente. Permitirá administrar productos, ubicaciones e inventario; recibir mercancía; gestionar pedidos; reservar stock; crear y ejecutar picking; preparar pedidos; planificar despachos; mantener trazabilidad completa; y controlar las cuentas por cobrar de los clientes.

## 2.1 Propuesta de valor

| Dimensión | Valor que debe demostrar SENTAI |
| --- | --- |
| Operación | Controlar el ciclo físico de la mercancía desde recepción hasta despacho. |
| Inventario | Conocer existencia, ubicación, lote/serie, estado, reserva y disponibilidad. |
| Cliente | Permitir crear pedidos, consultar su progreso, envíos y obligaciones de pago. |
| Fulfillment | Transformar pedidos confirmados en reservas, tareas de picking, packing y envíos. |
| Finanzas | Controlar cartera de clientes, vencimientos, pagos y aplicación de pagos. |
| Tecnología | Mostrar arquitectura mantenible, APIs, testing, CI/CD, seguridad e idempotencia. |
| Portafolio | Ofrecer una demo coherente que pueda explicarse y defenderse técnicamente. |

# 3. Alcance funcional y fronteras

## 3.1 Incluido en la visión

- Datos maestros de almacenes, zonas, ubicaciones, productos, clientes, proveedores y transportistas.

- Recepción y put-away.

- Inventario por ubicación, lote/serie y estado.

- Pedidos creados directamente por clientes y pedidos internos/integrados.

- Reserva y asignación de inventario.

- Picking, packing y despacho.

- Planificación de envíos dentro del alcance del WMS.

- Tareas operativas y aplicación móvil para operarios.

- Cuentas por cobrar, condiciones de pago, crédito, pagos y aging.

- Auditoría, reportes, dashboards e integraciones por API.

## 3.2 Fuera del MVP

- Contabilidad general, libro mayor, plan de cuentas y asientos contables.

- Facturación fiscal completa; podrá integrarse posteriormente con un ERP o sistema de e-factura.

- Optimización avanzada de rutas, GPS, gestión de combustible, mantenimiento de flota y geofencing propios de un TMS.

- Robótica, voz, RFID masivo e IoT avanzado en la primera fase.

- Microservicios distribuidos por defecto.

- Analítica predictiva e IA operativa como requisito inicial.

> Frontera clave
> SENTAI puede crecer hacia una plataforma logística más amplia, pero el MVP debe conservar foco. Shipping Planning formará parte del WMS; la optimización avanzada del transporte quedará fuera. Finance cubrirá Accounts Receivable, no contabilidad general.

# 4. Actores y experiencias

| Actor | Responsabilidad principal | Canal |
| --- | --- | --- |
| Cliente | Consulta catálogo/disponibilidad, crea pedidos, consulta envíos, pagos y estado de cuenta. | Portal web |
| Operario de almacén | Ejecuta put-away, picking, conteos, movimientos y validaciones por escaneo. | App móvil |
| Supervisor | Controla tareas, excepciones, productividad e incidencias. | Web + móvil |
| Jefe de almacén | Supervisa capacidad, inventario, inbound/outbound y KPIs. | Web |
| Planificador de despacho | Agrupa pedidos preparados y programa envíos/muelles/horarios. | Web |
| Comercial | Consulta clientes, pedidos y condición crediticia; puede gestionar bloqueos según permisos. | Web |
| Finanzas | Gestiona cartera, vencimientos, pagos, aplicaciones y límites de crédito. | Web |
| Administrador | Configuración, usuarios, roles, permisos y parámetros. | Web |

## 4.1 Portal del cliente

> Inicio
> Catálogo
> Pedidos
> Envíos
> Cuentas por pagar
> Pagos
> Estado de cuenta

La terminología es deliberada: el cliente ve sus obligaciones como cuentas por pagar. El backoffice de SENTAI ve la misma obligación como cuenta por cobrar. No se duplicará la deuda en el modelo; se utilizará una única fuente de verdad con proyecciones distintas según el actor.

# 5. Procesos principales

## 5.1 Flujo físico end-to-end

> ASN / Recepción
>        ↓
> Control físico y validación
>        ↓
> Ingreso de mercancía
>        ↓
> Put-away sugerido
>        ↓
> Ubicación e inventario
>        ↓
> Pedido confirmado
>        ↓
> Reserva de stock
>        ↓
> Picking
>        ↓
> Packing
>        ↓
> Planificación de envío
>        ↓
> Despacho
>        ↓
> Trazabilidad completa

## 5.2 Order-to-Fulfillment

> Cliente crea pedido → Validación → Confirmación → Allocation → Picking → Packing → Shipment Planning → Dispatch → Delivery

## 5.3 Order-to-Cash

> Pedido / despacho → Obligación financiera → Cuenta por cobrar → Pago → Payment Allocation → Saldo 0 / PAID

> Dos procesos, una misma operación
> El Blueprint deberá modelar desde D0 dos procesos paralelos: Order-to-Fulfillment termina en la entrega física; Order-to-Cash termina cuando la obligación financiera del cliente queda saldada.

# 6. Módulos y bounded contexts

| # | Contexto | Responsabilidad |
| --- | --- | --- |
| 01 | Identity | Usuarios, roles, permisos, autenticación y acceso. |
| 02 | Customers | Clientes, direcciones, contactos, cuentas y usuarios asociados. |
| 03 | Catalog | Productos, SKU, unidades, familias y atributos logísticos. |
| 04 | Warehousing | Almacenes, zonas, pasillos, racks, niveles, posiciones y capacidades. |
| 05 | Inbound | ASN, recepción, incidencias y put-away. |
| 06 | Inventory | Stock, lotes/series, estados, reservas, movimientos, ajustes y conteos. |
| 07 | Orders | Pedido comercial del cliente y su ciclo de aprobación. |
| 08 | Fulfillment | Allocation, picking, packing y preparación del pedido. |
| 09 | Operations | Tareas, operarios, prioridades y ejecución de trabajo. |
| 10 | Shipping | Planificación, agrupación, muelles, transportistas y despacho. |
| 11 | Finance | Accounts Receivable: crédito, documentos, pagos, aplicaciones y aging. |
| 12 | Audit | Historial de operaciones críticas y trazabilidad de cambios. |
| 13 | Reporting | KPIs, dashboards y exportaciones. |
| 14 | Integrations | Contratos con ERP, TMS, e-commerce, transportistas y otros sistemas. |

La primera implementación será un monolito modular. Los límites anteriores representan separación de responsabilidades y contratos internos; no implican microservicios independientes desde el inicio.

# 7. Modelo de inventario y reglas críticas

## 7.1 Inventario no es solo “producto + cantidad”

> InventoryStock
>   Warehouse
>   Location
>   Product
>   Lot / SerialNumber
>   StockStatus
>   QuantityOnHand
>   QuantityReserved
>   QuantityAvailable

| Estado de stock | Significado |
| --- | --- |
| AVAILABLE | Disponible para reserva y cumplimiento. |
| RESERVED | Comprometido con pedidos confirmados. |
| QUARANTINE | Retenido a la espera de control o liberación. |
| DAMAGED | Dañado, no disponible para fulfillment. |
| BLOCKED | Bloqueado por regla operativa o administrativa. |
| EXPIRED | Vencido o no utilizable. |

## 7.2 Reserva no equivale a salida

> Antes del pedido: OnHand 100 | Reserved 25 | Available 75
> Pedido nuevo: 20 unidades
> Después de reservar: OnHand 100 | Reserved 45 | Available 55
> Después del despacho: OnHand 80 | Reserved 25 | Available 55

## 7.3 Concurrencia

SENTAI deberá impedir sobre-reservas. Si quedan 10 unidades y dos pedidos intentan reservar 8 y 7 simultáneamente, el sistema debe mantener la invariante Reserved <= OnHand mediante transacciones y control de concurrencia.

## 7.4 Reglas de put-away

> IF temperature = CHILLED → prefer COLD_STORAGE
> IF turnover = HIGH → prefer locations near dispatch
> IF hazardous = true → require HAZMAT zone
> IF capacity < required → reject location

# 8. Pedidos, fulfillment y planificación de envíos

## 8.1 Pedido comercial y fulfillment son conceptos distintos

> CustomerOrder
>       ↓
> InventoryAllocation
>       ↓
> FulfillmentOrder
>       ├─ PickingWave
>       ├─ PickingTask
>       ├─ Packing
>       └─ Shipment

## 8.2 Estados iniciales del pedido

> DRAFT → SUBMITTED → VALIDATING → CONFIRMED → ALLOCATED → PICKING → PACKING → READY_TO_SHIP → SHIPPED → DELIVERED → COMPLETED
> Alternativos: CANCELLED | ON_HOLD | CREDIT_HOLD | PARTIALLY_ALLOCATED | PARTIALLY_SHIPPED

## 8.3 Shipment Planning

SENTAI incluirá planificación de despacho sin convertirse en TMS completo. El planificador podrá agrupar pedidos preparados por fecha, cliente, zona o transportista; crear envíos; asignar muelle y ventana de salida; y registrar carga y despacho.

| Dato de envío | Ejemplo de uso |
| --- | --- |
| ShipmentNumber | Identificador único del envío. |
| DeliveryAddress | Destino del cliente. |
| Carrier | Transportista asignado. |
| PlannedDispatchDate | Fecha y hora previstas. |
| DeliveryWindow | Ventana esperada de entrega. |
| Dock | Muelle asignado. |
| Packages / Weight / Volume | Capacidad y consolidación. |
| Status | PLANNED, READY, LOADING, DISPATCHED, DELIVERED. |

# 9. Finanzas: cuentas por cobrar y perspectiva del cliente

SENTAI controlará las cuentas por cobrar generadas por la relación comercial con sus clientes. No se construirá un módulo contable general. El objetivo es conocer exposición crediticia, saldos, vencimientos, pagos y aplicación de pagos.

## 9.1 Modelo de cartera

> CustomerAccount
>   CreditLimit
>   PaymentTerms
>   CurrentBalance
>   AvailableCredit
>   OverdueBalance
>   AccountStatus

## 9.2 Documento por cobrar

> CustomerReceivable
>   CustomerId
>   DocumentNumber
>   SourceOrder / Shipment
>   OriginalAmount
>   OutstandingAmount
>   IssueDate
>   DueDate
>   Status

## 9.3 Estados propuestos

| Estado | Interpretación |
| --- | --- |
| OPEN | Saldo pendiente sin pagos aplicados suficientes. |
| PARTIALLY_PAID | Existe pago parcial y saldo remanente. |
| PAID | Saldo pendiente igual a cero. |
| OVERDUE | Vencido con saldo pendiente. |
| CANCELLED | Documento anulado según reglas autorizadas. |

## 9.4 Pagos y aplicación

Un pago podrá aplicarse a uno o varios documentos. La entidad Payment estará separada de PaymentAllocation para permitir, por ejemplo, distribuir un pago de $100.000 entre tres documentos por $30.000, $25.000 y $45.000.

## 9.5 Control de crédito

> Credit Limit 100.000
> Current Balance 90.000
> New Order 25.000
> Projected Exposure 115.000
> → CREDIT_HOLD → revisión / aprobación autorizada

## 9.6 Perspectiva del cliente

El portal no mostrará “Cuentas por cobrar”. Desde el punto de vista del cliente, la misma obligación aparecerá como “Cuentas por pagar”, con saldo pendiente, saldo vencido, próximos vencimientos, pagos y estado de cuenta.

# 10. Aplicación móvil Android/iOS

La solución móvil deberá contemplar desde el Blueprint las dos plataformas: Android e iOS. La aplicación operativa no será una copia del backoffice web; estará diseñada específicamente para el trabajo de almacén, con acciones rápidas, controles grandes y flujo basado en tareas y escaneo.

## 10.1 Tecnología propuesta

> SENTAI Operator
> Aplicación multiplataforma con Flutter, compartiendo una base de código para Android e iOS. Esta decisión se ajusta al carácter operacional de la app y reduce duplicación sin impedir integración con cámara, escáner, notificaciones o capacidades nativas cuando sean necesarias.

## 10.2 Casos de uso principales

- Inicio de sesión y selección de almacén.

- Consulta de tareas asignadas.

- Put-away y reubicaciones.

- Picking por tarea/ola.

- Conteos cíclicos.

- Validación de producto, lote, ubicación y cantidad mediante código de barras o QR.

- Packing operativo cuando corresponda.

- Sincronización de operaciones pendientes.

## 10.3 Offline-first

> Operario escanea
>       ↓
> Persistencia local
>       ↓
> PendingOperations
>       ↓
> Conectividad recuperada
>       ↓
> SyncEngine + Idempotency-Key
>       ↓
> Laravel API
>       ↓
> Confirmación / resolución de conflicto

# 11. Arquitectura y stack tecnológico

| Área | Decisión base |
| --- | --- |
| Backend / API | Laravel - versión estable vigente al iniciar implementación. |
| Base de datos | MySQL. |
| Arquitectura | Clean Architecture + Modular Monolith. |
| API | REST versionada bajo /api/v1. |
| Web | React + TypeScript + Tailwind CSS. |
| Portal de clientes | Parte de la solución web, aislado por roles y permisos. |
| Mobile | Flutter para Android e iOS, offline-first. |
| Autorización | RBAC granular por módulo, almacén y acción. |
| Testing | Unit, integration, feature/API y architecture tests. |
| CI/CD | GitHub Actions. |
| Hosting demo | Compatible con hosting PHP + MySQL del usuario. |
| Docker | Soportado cuando aporte valor, pero no requisito para desarrollo o despliegue inicial. |

## 11.1 Decisión arquitectónica principal

No se adoptarán microservicios prematuramente. Los bounded contexts se implementarán en un monolito modular con contratos claros. Esta arquitectura podrá evolucionar si una necesidad real de escalabilidad, aislamiento o despliegue independiente justifica extraer servicios.

## 11.2 Despliegue demostrable

La solución debe poder publicarse como demo real en la infraestructura disponible. Esto condiciona positivamente el stack: Laravel + MySQL permiten aprovechar el hosting actual sin introducir complejidad operativa innecesaria.

# 12. Seguridad, trazabilidad y confiabilidad

## 12.1 RBAC

| Rol | Acceso característico |
| --- | --- |
| Warehouse Operator | Picking, put-away, conteos y movimientos; sin acceso financiero. |
| Supervisor | Tareas, incidencias, excepciones y control operativo. |
| Shipping Planner | Planificación de envíos y muelles. |
| Commercial | Pedidos, clientes y consulta de condición crediticia según permisos. |
| Finance | Cuentas por cobrar, pagos, aplicación, mora y crédito. |
| Administrator | Configuración general, usuarios, roles y permisos. |

## 12.2 Idempotencia

Operaciones críticas deberán aceptar claves de idempotencia. Un reintento por doble escaneo o conectividad inestable no puede duplicar una recepción, un movimiento, un pago o una confirmación de tarea.

## 12.3 Auditoría y trazabilidad

SENTAI conservará el historial de operaciones críticas: quién ejecutó la acción, cuándo, desde qué contexto, qué cambió y cuál fue el resultado. Un lote o serie deberá poder reconstruir su recorrido desde recepción hasta pedido y despacho.

> LOT-2026-0916-A
>   ASN-000031
>   Receipt RCV-000081
>   Location A-03-04
>   Movement MOV-002134
>   Pick PK-000341
>   Order SO-000733
>   Shipment SHP-000312

# 13. Identidad visual y UX

## 13.1 Color principal

> Primary Green - #53B559
> RGB 83, 181, 89. Será el color principal de la identidad visual de SENTAI: logotipo/wordmark, botones primarios, estados activos, navegación seleccionada, acentos, gráficos y KPIs. Se definirán variantes accesibles para hover, fondos claros y modo oscuro sin perder la identidad de marca.

## 13.2 Principios de interfaz

- Interfaz profesional, moderna y limpia, evitando apariencia genérica de panel administrativo.

- Jerarquía visual clara entre operación, alerta y acción.

- Dashboard orientado a situación actual del almacén.

- Portal del cliente simplificado y centrado en pedidos, envíos y obligaciones.

- App móvil adaptada a uso con una mano, escaneo rápido y feedback visual/sonoro/háptico.

- Estados críticos diferenciados por texto e iconografía, no únicamente por color.

- Diseño preparado para modo claro y oscuro si se incorpora en la fase visual.

## 13.3 Dashboard objetivo

> SENTAI | Warehouse Operations
> Montevideo DC-01
> 
> Inventory accuracy      99.7%
> Warehouse occupancy     76%
> Open inbound             12
> Orders awaiting picking  34
> 
> Operational alerts
>   3 inventory discrepancies
>   2 lots nearing expiration
>   4 high-priority orders
> 
> Finance snapshot
>   Accounts receivable
>   Overdue balance
>   Due this week

# 14. Demo objetivo para portafolio

La demo debe contar una historia completa y comprensible sin necesidad de explicar decenas de pantallas. El escenario de referencia conectará cliente, inventario, operación, despacho y finanzas.

Un cliente inicia sesión y consulta catálogo y disponibilidad.

Crea un pedido por una cantidad concreta de un SKU.

SENTAI valida condición comercial y disponibilidad, confirma el pedido y reserva stock.

El sistema divide la necesidad entre ubicaciones y genera tareas de picking.

El operario abre SENTAI Operator, navega a la ubicación, escanea y confirma cantidades.

Packing recibe el pedido y lo deja listo para despacho.

El planificador agrupa el pedido dentro de un Shipment, asigna transportista, muelle y hora.

Se registra la salida y el cliente ve el pedido como enviado.

El backoffice genera/relaciona la obligación en cuentas por cobrar; el cliente la ve como cuenta por pagar.

Se registra un pago, se aplica al documento y el saldo se actualiza.

> Resultado esperado
> En una sola demo se evidencian análisis de negocio, modelo de dominio, APIs, base de datos, concurrencia, UX por rol, mobile, trazabilidad, seguridad, finanzas operativas y despliegue. El proyecto deja de parecer un CRUD y se convierte en una solución con narrativa empresarial.

# 15. Roadmap y próximos pasos del Blueprint

Este documento es la línea base inicial. El siguiente trabajo no debe comenzar por escribir entidades o migraciones aisladas, sino por convertir estas decisiones en documentación gobernada por Blueprint.

| Paso | Resultado esperado |
| --- | --- |
| D0.1 - Product Definition & Scope | Problema, objetivos, actores, alcance, fuera de alcance y criterios de éxito. |
| D0.2 - Domain Discovery | Lenguaje ubicuo, procesos, eventos, reglas y excepciones. |
| D0.3 - Context Map | Límites y relaciones entre bounded contexts. |
| D0.4 - MVP Baseline | Verticales obligatorias y secuencia de implementación. |
| D1 - Architecture | Estructura Laravel modular, contratos, persistencia, seguridad e integración. |
| D2 - Data & API | Modelo lógico, invariantes, endpoints y contratos. |
| D3 - UX | Mapa de navegación, portal cliente, backoffice y operador móvil. |
| D4 - Delivery | Testing, CI/CD, hosting, observabilidad y estrategia de demo. |

## 15.1 MVP propuesto

- Identity + Customers + Catalog + Warehousing.

- Inbound básico con recepción y put-away.

- Inventory con ubicaciones, reservas, movimientos y estados.

- Orders con pedido de cliente y validación comercial.

- Fulfillment con allocation, picking y packing.

- Shipping Planning y despacho.

- Finance con cuentas por cobrar, pagos y aging básico.

- Portal de cliente con pedido, tracking, cuentas por pagar y estado de cuenta.

- SENTAI Operator con tareas, picking/put-away y sincronización offline inicial.

- Auditoría, pruebas y CI/CD desde las primeras verticales.

> Decisiones ya congeladas
> Laravel + MySQL; React + TypeScript + Tailwind; app Android/iOS multiplataforma; hosting compatible con PHP/MySQL; clientes crean pedidos; fulfillment deriva de esos pedidos; Shipping Planning pertenece al WMS; cuentas por cobrar se gestionan en backoffice; el cliente ve cuentas por pagar; color principal #53B559.

Fin del documento - SENTAI v0.1
