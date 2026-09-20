# SENTAI - API Endpoint Inventory Reconciliation

**Artifact ID:** EVD-API-004
**Blueprint Phase:** API Contract Design (C2)
**Status:** READY_FOR_REVIEW

---

## 1. Objetivos de Reconciliación

Este documento documenta la reconciliación entre las operaciones provisorias declaradas en C1 y los requisitos (EVD-REQ-001, EVD-ARCH-SEC-001), corrigiendo discrepancias, justificando la adición de operaciones requeridas y documentando explícitamente gaps (UNRESOLVED_CONTRACT_GAP) que impiden que el inventario contractual se considere completo en este incremento.

---

## 2. Reconciliación de Master Data (UC-002 / FR-001)

EVD-REQ-001 y UC-002 indican operaciones de "crear" y "actualizar" para maestros, pero no se evidencia necesidad de eliminación (DELETE) ni borrado lógico.

### 2.1 Warehouse, Zone, Location
Las entradas provisorias `ANY` de C1 se reemplazan por operaciones sustentadas en evidencia funcional (FR-001/UC-002):
- **Warehouse:** `GET /api/v1/masters/warehouses`, `POST /api/v1/masters/warehouses`, `PUT /api/v1/masters/warehouses/{id}`.
- **Zone:** `GET /api/v1/masters/zones`, `POST /api/v1/masters/zones`, `PUT /api/v1/masters/zones/{id}`.
- **Location:** `GET /api/v1/masters/locations`, `POST /api/v1/masters/locations`, `PUT /api/v1/masters/locations/{id}`.

### 2.2 Product y Customer
Los endpoints de Master Data correspondientes a `DELETE` para Products y Customers (API-MAST-004, API-MAST-008) quedan clasificados como **UNRESOLVED_CONTRACT_GAP**. No se autoriza su existencia ni se inventa un "soft delete" por falta de evidencia aprobada.

---

## 3. Reconciliación de Inventory Location Block (BR-004)

BR-004 requiere que una ubicación bloqueada rechace movimientos hasta su "liberación".
- **Block:** Se define `POST /api/v1/locations/{id}/block`. (Capability: `inventory.location.block`, Audit: `inventory.location.blocked`).
- **Unblock:** Se formaliza `POST /api/v1/locations/{id}/unblock` para cubrir la liberación exigida por BR-004. (Capability: `inventory.location.block`, Audit: `inventory.location.unblocked`).

---

## 4. Reconciliación de Global Audit (UNRES-006 en A2)

- **Endpoint:** `GET /api/v1/audit/events`
- **Reglas Obligatorias:**
  - Requiere autenticación interna y capability explícita `audit.global.read`.
  - El actor/rol es `AuditViewer`.
  - ACT-007 (Administrador) no obtiene acceso implícito.
  - La consulta debe quedar trazable (Audit: `authz.audit.access`).
  - No exponer campos prohibidos/sensibles en la respuesta.
  - Paginación/filtering quedan como detalle contractual posterior.

---

## 5. Reconciliación de Identity / Roles Management

La gestión se mapea directamente al catálogo de auditoría canónico de A2. No se define eliminación de usuario.

### 5.1 Identidad (Users)
- `POST /api/v1/admin/users` (Audit: `admin.user.created`)
- `PUT /api/v1/admin/users/{id}` (Audit: `admin.user.updated`)
- `PUT /api/v1/admin/users/{id}/disable` (Audit: `admin.user.disabled`)
- `GET /api/v1/admin/users` (Listing para gestión administrativa. Mapea con capability: `admin.identity.manage`).

### 5.2 Permisos / Roles
Se mapean explícitamente a los eventos canónicos de asignación y revocación:
- `POST /api/v1/admin/users/{user_id}/roles` (Asignación. Audit: `authz.role.assigned`)
- `DELETE /api/v1/admin/users/{user_id}/roles/{role_id}` (Revocación. Audit: `authz.role.revoked`)

---

## 6. Gaps Contractuales Identificados (UNRESOLVED_CONTRACT_GAP)

Los siguientes puntos carecen de evidencia explícita aprobada (FR, UC, Interface Scope), lo que bloquea el cierre del `api.endpoint_inventory`:

1. **`customer.order.read`**: UNRES-001 (tracking del cliente) sigue sin resolverse. Por tanto, no se autoriza la creación de `GET /api/v1/orders`. 
2. **`commercial.customer.maintain`**: Falta definición contractual sobre qué componen exactamente los "commercial-terms" indicados en FR-008, impidiendo el diseño intuitivo de una API para ello.
3. **Mobile Offline Tasks**: Aunque existe necesidad de que el dispositivo descargue tareas offline para operar, no está claro qué tareas incluye (picking, put-away, reception, etc.) ni cómo se autorizan operaciones heterogéneas. El recurso exacto (ej. `GET /api/v1/fulfillment/tasks`) permanece indefinido sin un modelo aprobado.
4. **Offline Conflicts Resolution API**: La respuesta del lote `/api/v1/sync` comunicará los resultados/conflictos individuales. Sin embargo, no se crea un endpoint explícito independiente (ej. `GET /api/v1/sync/conflicts`) ya que no existe una capability canónica ni requisito de UI aprobado para la lectura/consulta asíncrona de estos conflictos.
5. **Master Data Deletion**: Todo requerimiento de borrado/eliminación sobre entidades de Master Data queda en gap por ausencia funcional demostrable.

---

## Traceability
* **A2/A3 Security & Audit:** Mapeo estricto del contrato a capacidades y eventos canónicos (Global Audit, Identity, Roles).
* **FR-001/UC-002:** Ajuste a CRUD sin DELETE por falta de justificación.
* **BR-004:** Justificación de Block/Unblock Operations.
* **Mobile Sync:** Resultado de sincronización sin requerir nuevos endpoints adicionales injustificados.
