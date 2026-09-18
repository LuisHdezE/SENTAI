# SENTAI - Interface Scope Baseline

**Blueprint Phase:** Interface Scope Baseline  
**Status:** READY_FOR_REVIEW  
**Artifact ID:** EVD-UI-SCOPE-001  
**Type:** interface_scope_baseline_evidence  
**Canonical JSON:** `.blueprint/ui/interface-scope-baseline.json`  
**Derived from:** EVD-REQ-001  
**Blueprint version:** 0.5.4 @ `8d29ba4c6caf0a382b80310dc0e88c8f1e7fb3c4`

---

## Nota de Alcance

Esta evidencia es **DESCRIPTIVA y de PLANIFICACION**. No constituye backlog ejecutable. No define:

- Routes definitivas
- Endpoints ni operationIds
- Payloads de API
- Componentes React/KMP
- Tokens de diseno o layout visual definitivo
- Permisos tecnicos definitivos
- Aprobacion de los 102 mockups preexistentes

Todo lo anterior queda diferido a fases posteriores: `interface_inventory`, `api_contract_design`, `client_architecture`, `design_system`.

Los contenidos de esta fase se derivan **exclusivamente** de ACT, FR, NFR, BR, UC y AC aprobados en EVD-REQ-001.

---

## 1. Superficies Canonicas

SENTAI opera sobre tres superficies canonicas:

| ID       | Nombre               | Plataforma       | Actores Primarios                        |
|----------|----------------------|------------------|------------------------------------------|
| SURF-BO  | Backoffice Web       | Web              | ACT-003, ACT-004, ACT-005, ACT-006, ACT-007 |
| SURF-CP  | Customer Portal Web  | Web              | ACT-001                                  |
| SURF-MOB | Operator Mobile KMP  | Android / iOS    | ACT-002                                  |

Android e iOS son **plataformas** de la misma superficie funcional movil (KMP), no superficies separadas.

---

## 2. Shells Reutilizables

### Regla Obligatoria

Ninguna vista debe concebir Sidebar, Topbar o BottomBar como parte privada de la propia vista. Toda navegacion global pertenece al shell de la superficie.

### 2.1 BackofficeShell (SURF-BO)

Contenedor global compartido por todas las areas del Backoffice.

| Region         | Rol                                                                                  |
|----------------|--------------------------------------------------------------------------------------|
| Sidebar        | Navegacion principal compartida. Consume el Catalogo Central de Navegacion (surface=backoffice). |
| Topbar         | Barra superior compartida. Identidad, sesion, notificaciones y acciones globales.    |
| ContentOutlet  | Zona de contenido intercambiable. Las vistas renderizan unicamente aqui.             |
| BottomBar      | Bloque inferior compartido cuando aplique. Necesidad concreta a determinar en interface_inventory. |

### 2.2 CustomerPortalShell (SURF-CP)

Shell global propio e independiente del BackofficeShell. La forma visual definitiva queda diferida a Design System; no se asume que Sidebar, Topbar o BottomBar seran visualmente identicos al Backoffice.

| Region              | Rol                                                                                      |
|---------------------|------------------------------------------------------------------------------------------|
| Topbar              | Barra superior compartida. Identidad de marca, sesion del cliente, acceso a navegacion.  |
| NavigationRegion    | Zona de navegacion global (Sidebar, menu colapsable, barra horizontal u otra forma). Forma visual diferida a Design System. |
| ContentOutlet       | Zona de contenido intercambiable. Las vistas renderizan unicamente aqui.                 |
| FooterRegion        | Bloque inferior compartido cuando aplique. Necesidad concreta a determinar en interface_inventory. |

### 2.3 OperatorMobileNavContext (SURF-MOB)

Contexto y navegacion global reutilizable de la app movil. Ninguna pantalla individual recrea la navegacion global.

| Region          | Rol                                                                                      |
|-----------------|------------------------------------------------------------------------------------------|
| TopAppBar       | Barra de aplicacion superior compartida. Titulo, estado de conexion, notificaciones de conflicto, acciones globales. |
| BottomNavigation | Navegacion inferior compartida cuando corresponda. Necesidad y estructura concreta a determinar en interface_inventory. |
| ContentArea     | Area de contenido de cada pantalla. Las pantallas renderizan unicamente aqui.            |

---

## 3. Areas de Intencion por Superficie

### 3.1 Backoffice Web (SURF-BO)

| ID                  | Intencion                         | Actores        | FR Primarios            | UC Primarios    |
|---------------------|-----------------------------------|----------------|-------------------------|-----------------|
| BO-AUTH             | Autenticacion y acceso autorizado | ACT-003..007   | FR-022                  | UC-001          |
| BO-MASTERS          | Maestros autorizados              | ACT-007        | FR-001                  | UC-002          |
| BO-INVENTORY        | Consulta y control de inventario  | ACT-003        | FR-004                  | UC-005          |
| BO-MOVEMENTS        | Movimientos y ajustes             | ACT-003        | FR-005, FR-019          | UC-006          |
| BO-ALLOCATION       | Allocation / Reserva              | ACT-003        | FR-006                  | UC-009          |
| BO-ORDER-VALIDATION | Validacion comercial de pedidos   | ACT-005        | FR-008                  | UC-008          |
| BO-SHIPPING-PLAN    | Planificacion de envio            | ACT-004        | FR-011                  | UC-012          |
| BO-DISPATCH         | Despacho                          | ACT-004        | FR-012, FR-013          | UC-013          |
| BO-AR               | Accounts Receivable / Cartera     | ACT-006        | FR-016                  | UC-014          |
| BO-PAYMENT-REGISTER | Registro de pagos                 | ACT-006        | FR-014                  | UC-015          |
| BO-PAYMENT-APPLY    | Aplicacion de pagos               | ACT-006        | FR-015                  | UC-016          |
| BO-STATEMENT        | Estado de cuenta (Finanzas)       | ACT-006        | FR-018                  | UC-018          |
| BO-AUDIT            | Trazabilidad / Auditoria          | ACT-003, 006, 007 | FR-019               | -               |

### 3.2 Customer Portal Web (SURF-CP)

| ID              | Intencion                                  | Actor   | FR Primarios   | UC Primarios     |
|-----------------|--------------------------------------------|---------|----------------|------------------|
| CP-AUTH         | Autenticacion                              | ACT-001 | FR-022         | UC-001           |
| CP-CATALOG      | Catalogo                                   | ACT-001 | FR-007         | UC-007           |
| CP-ORDER-CREATE | Creacion de pedidos                        | ACT-001 | FR-007         | UC-007           |
| CP-AP           | Obligaciones financieras (Accounts Payable)| ACT-001 | FR-017         | UC-017           |
| CP-STATEMENT    | Estado de cuenta propio                    | ACT-001 | FR-018         | UC-018           |

**Nota:** No se introduce "Cuentas por Cobrar" en el Customer Portal. La misma obligacion financiera se ve como AR en el Backoffice (ACT-006) y como AP en el Customer Portal (ACT-001), segun BR-005.

### 3.3 Operator Mobile KMP (SURF-MOB)

| ID                | Intencion                                         | Actor   | FR Primarios        | UC Primarios        |
|-------------------|---------------------------------------------------|---------|---------------------|---------------------|
| MOB-AUTH          | Autenticacion                                     | ACT-002 | FR-022              | UC-001              |
| MOB-RECEPTION     | Recepcion contra ASN                              | ACT-002 | FR-002              | UC-003              |
| MOB-PUTAWAY       | Put-away                                          | ACT-002 | FR-003, FR-004      | UC-004              |
| MOB-PICKING       | Picking                                           | ACT-002 | FR-009, FR-020      | UC-010, UC-019      |
| MOB-PACKING       | Packing                                           | ACT-002 | FR-010              | UC-011              |
| MOB-OFFLINE-TASKS | Operacion con tareas descargadas / Estado offline | ACT-002 | FR-020              | UC-019              |
| MOB-SYNC          | Cola / Sincronizacion posterior                   | ACT-002 | FR-021              | UC-020              |
| MOB-CONFLICTS     | Visibilidad de conflictos de sincronizacion       | ACT-002 | FR-021              | UC-020              |

---

## 4. Matriz de Superficies versus FR/UC

| FR / UC   | SURF-BO (area)       | SURF-CP (area)    | SURF-MOB (area)         |
|-----------|----------------------|-------------------|-------------------------|
| FR-001    | BO-MASTERS           | -                 | -                       |
| FR-002    | -                    | -                 | MOB-RECEPTION           |
| FR-003    | -                    | -                 | MOB-PUTAWAY             |
| FR-004    | BO-INVENTORY         | -                 | MOB-PUTAWAY             |
| FR-005    | BO-MOVEMENTS         | -                 | -                       |
| FR-006    | BO-ALLOCATION        | -                 | -                       |
| FR-007    | -                    | CP-CATALOG, CP-ORDER-CREATE | -             |
| FR-008    | BO-ORDER-VALIDATION  | -                 | -                       |
| FR-009    | -                    | -                 | MOB-PICKING             |
| FR-010    | -                    | -                 | MOB-PACKING             |
| FR-011    | BO-SHIPPING-PLAN     | -                 | -                       |
| FR-012    | BO-DISPATCH          | -                 | -                       |
| FR-013    | BO-DISPATCH          | -                 | -                       |
| FR-014    | BO-PAYMENT-REGISTER  | -                 | -                       |
| FR-015    | BO-PAYMENT-APPLY     | -                 | -                       |
| FR-016    | BO-AR                | -                 | -                       |
| FR-017    | -                    | CP-AP             | -                       |
| FR-018    | BO-STATEMENT         | CP-STATEMENT      | -                       |
| FR-019    | BO-AUDIT, BO-MOVEMENTS | -               | -                       |
| FR-020    | -                    | -                 | MOB-PICKING, MOB-OFFLINE-TASKS |
| FR-021    | -                    | -                 | MOB-SYNC, MOB-CONFLICTS |
| FR-022    | BO-AUTH              | CP-AUTH           | MOB-AUTH                |
| UC-001    | BO-AUTH              | CP-AUTH           | MOB-AUTH                |
| UC-002    | BO-MASTERS           | -                 | -                       |
| UC-003    | -                    | -                 | MOB-RECEPTION           |
| UC-004    | -                    | -                 | MOB-PUTAWAY             |
| UC-005    | BO-INVENTORY         | -                 | -                       |
| UC-006    | BO-MOVEMENTS         | -                 | -                       |
| UC-007    | -                    | CP-CATALOG, CP-ORDER-CREATE | -             |
| UC-008    | BO-ORDER-VALIDATION  | -                 | -                       |
| UC-009    | BO-ALLOCATION        | -                 | -                       |
| UC-010    | -                    | -                 | MOB-PICKING             |
| UC-011    | -                    | -                 | MOB-PACKING             |
| UC-012    | BO-SHIPPING-PLAN     | -                 | -                       |
| UC-013    | BO-DISPATCH          | -                 | -                       |
| UC-014    | BO-AR                | -                 | -                       |
| UC-015    | BO-PAYMENT-REGISTER  | -                 | -                       |
| UC-016    | BO-PAYMENT-APPLY     | -                 | -                       |
| UC-017    | -                    | CP-AP             | -                       |
| UC-018    | BO-STATEMENT         | CP-STATEMENT      | -                       |
| UC-019    | -                    | -                 | MOB-PICKING, MOB-OFFLINE-TASKS |
| UC-020    | -                    | -                 | MOB-SYNC, MOB-CONFLICTS |

---

## 5. Catalogo Central de Navegacion (Intencion Futura)

Se registra la **intencion** de una unica fuente canonica de destinos de navegacion, compartida entre superficies. **No se implementa en esta fase.**

Campos conceptuales previstos:

| Campo               | Descripcion                                                                                  |
|---------------------|----------------------------------------------------------------------------------------------|
| `id`                | Identificador unico del destino                                                               |
| `label`             | Etiqueta legible del destino                                                                  |
| `icon`              | Referencia al icono. Tokens diferidos a Design System                                         |
| `route`             | Ruta logica. Definicion concreta diferida a interface_inventory                               |
| `section`           | Agrupacion dentro de la superficie                                                            |
| `surface`           | `backoffice` / `customer_portal` / `operator_mobile`                                          |
| `requiredPermission`| Permiso funcional requerido. Definicion tecnica diferida a architecture_security_data         |
| `maturity`          | Madurez de la interfaz: `pending` / `ready` (minimo)                                          |
| `enabled`           | Si el destino esta activo/visible en la navegacion actual                                      |

### Regla de Independencia Critica

> **`requiredPermission` != `maturity`**

La autorizacion funcional y la madurez de la interfaz son dimensiones **completamente independientes**.

- Una capacidad puede estar autorizada aunque su interfaz siga en estado `pending`.
- No se amplian permisos para hacer coincidir la navegacion con las vistas existentes.

---

## 6. Necesidades No Resueltas (Unresolved Needs)

Los siguientes puntos son explicitamente pendientes y **no se han convertido en backlog ejecutable** en esta fase:

| ID         | Descripcion                                                                                                              | Fuente                        | Accion Requerida                                                                                             |
|------------|--------------------------------------------------------------------------------------------------------------------------|-------------------------------|--------------------------------------------------------------------------------------------------------------|
| UNRES-001  | ACT-001 menciona "rastreo de despachos del cliente", pero EVD-REQ-001 no tiene FR/UC explicito equivalente.              | ACT-001 Responsabilidades     | Reconciliar en requirements_domain o crear FR/UC explicito antes de incluir en interface_inventory.          |
| UNRES-002  | UC-003 exige ASN valido, pero EVD-REQ-001 no define quien crea o aprueba el ASN. Esta baseline no inventa ese flujo.     | UC-003 Precondiciones         | Definir origen del ASN (actor, flujo, superficie) en interface_inventory o api_contract_design.              |
| UNRES-003  | UC-009 permite Allocation por Sistema o Supervisor, pero la interaccion automatica/manual exacta queda indefinida.       | UC-009 Actor                  | Definir modo de triggering en Architecture/API/Interface Inventory.                                          |
| UNRES-004  | Los 102 mockups preexistentes son inputs GENERATED, no fuente contractual. No quedan aprobados por esta fase.            | status.yaml mockups.note      | Reconciliar en mockup_planning y mockup_review, despues de interface_inventory.                              |
| UNRES-005  | IDs legacy WEB-BO-###, WEB-CP-###, MOB-### no se canonizan. Son referencias informales preexistentes.                   | status.yaml interface_inventory.note | Canonizar en interface_inventory, reconciliando con intent_area IDs de esta baseline.               |

---

## 7. Exclusiones Explicitas de Esta Fase

Los siguientes elementos estan **fuera del alcance de esta baseline** y no deben interpretarse como definidos aqui:

- Numero final de pantallas
- Routes definitivas
- Endpoints y operationIds
- Payloads de API
- Componentes visuales concretos
- Tokens de diseno
- Layout visual definitivo
- Aprobacion de mockups preexistentes
- Arquitectura React/KMP
- Permisos tecnicos definitivos
- Implementacion de cualquier tipo
- Backlog ejecutable
- `brownfield_observed_interface_scope` (no aplica: proyecto Greenfield)

---

## 8. Notas de Conformidad Blueprint

- **Modo:** Greenfield. El check `ui.brownfield_observed_interface_scope` no aplica.
- **Mockups preexistentes:** GENERATED != REVIEWED != APPROVED. Su existencia no constituye aprobacion Blueprint.
- **IDs legacy:** WEB-BO-###, WEB-CP-###, MOB-### son informales. Canonizacion corresponde a `interface_inventory`.
- **mobile_licensing:** `false` segun EVD-REQ-001 seccion 9. El check `requirements.mobile_licensing_decision` esta en PASS.
- **interface_scope_ready:** Este gate permanece en `READY_FOR_REVIEW` hasta aprobacion humana. No se declara PASS aqui.

---

## 9. Aprobacion y Estado

- **Estado del artefacto:** `READY_FOR_REVIEW`
- **Pendiente de:** Revision por Dalila y aprobacion humana por Luis.
- **El gate `interface_scope_ready`** permanecera en `READY_FOR_REVIEW` hasta aprobacion humana explicita. No se declara COMPLETE ni PASS en esta fase.
