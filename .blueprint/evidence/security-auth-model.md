# SENTAI - Security & Auth Model

**Blueprint Phase:** Architecture & Security Data (A2)
**Status:** COMPLETE
**Artifact ID:** EVD-ARCH-SEC-001
**Type:** architecture_security_model

---

## 1. Security Model Constraints

1. **Authoritative Boundary:** The API/server remains the authoritative security boundary.
2. **AuthN != AuthZ:** Authentication never substitutes authorization.
3. **Explicit Authorization:** Authorization must use explicit capabilities/permissions plus contextual/resource policies where necessary.
4. **Customer Ownership Isolation:** Customer access must enforce ownership server-side. A customer can never access another customer's orders, financial obligations, payments, or account information by manipulating identifiers.
5. **Least Privilege:** Internal roles must not become universal superusers.
6. **No Implicit Admin Bypass:** ACT-007 Administrator must not be treated as an implicit bypass of commercial, warehouse, or finance controls.
7. **Server-Side Enforcement:** Sensitive operations require server-side permission checks even if the UI hides the action.
8. **Offline Mobile Authorization:** Offline mobile authorization is provisional only for already-downloaded/local work. Every synchronized mutation must be reauthenticated/reauthorized against current server state.
9. **Revocation Handing:** Revocation or authorization changes discovered at reconnect must prevent invalid queued work from silently becoming authoritative.
10. **Secret Protection:** Credentials, tokens, secrets, and sensitive authentication material must never appear in logs or durable audit events.
11. **Architecture Compatibility:** Security decisions must remain compatible with the existing Clean Architecture + Modular Monolith A1 baseline.

---

## 2. Authentication Strategy

### Web (Backoffice & Customer Portal)
* **Session Management:** Secure backend-managed browser sessions.
* **Cookies:** `HttpOnly` and `Secure` cookies.
* **CSRF:** CSRF protection enforced for state-changing browser requests.
* **SameSite:** Appropriate `SameSite` policy applied.
* **Storage:** No authentication secrets/tokens stored in browser `localStorage`.

### Mobile (Operator Mobile)
* **Tokens:** Short-lived access credential/token.
* **Session Renewal:** Renewable/rotating refresh session.
* **Storage:** Secure platform storage appropriate to Android/iOS.
* **Revocation:** Server-revocable sessions.
* **Sync Validation:** Authorization revalidation occurs during synchronization.
* **Licensing:** Device licensing is explicitly excluded (`mobile_licensing=false`).

---

## 3. Authorization Architecture & Actor Mapping

SENTAI uses capability-oriented authorization. Actors are mapped strictly to capabilities derived from EVD-REQ-001.

### Capability Definitions

* `catalog.read`: customer/catalog read
* `customer.order.create`: customer own-order creation
* `customer.order.read`: customer own-order read
* `customer.finance.read`: customer own financial/account-statement read
* `warehouse.receive`: warehouse receive
* `warehouse.putaway`: warehouse put-away
* `warehouse.picking`: picking
* `warehouse.packing`: packing
* `inventory.read`: inventory read
* `inventory.adjust`: inventory adjustment
* `inventory.location.block`: location blocking
* `dispatch.plan`: dispatch planning
* `dispatch.confirm`: dispatch confirmation
* `commercial.order.approve`: commercial order approval
* `commercial.customer.maintain`: customer commercial maintenance
* `finance.payment.register`: payment registration
* `finance.payment.apply`: payment application
* `finance.receivables.read`: receivables visibility
* `admin.identity.manage`: identity/user management
* `admin.roles.manage`: role/permission administration
* `admin.masters.maintain`: authorized master-data maintenance
* `audit.global.read`: global audit read

### Actor to Capability Mapping

* **ACT-001 (Cliente):** `catalog.read`, `customer.order.create`, `customer.order.read`, `customer.finance.read` (Contextual policy restricts to OWN data).
* **ACT-002 (Operario de almacén):** `warehouse.receive`, `warehouse.putaway`, `warehouse.picking`, `warehouse.packing`.
* **ACT-003 (Supervisor / Jefe de almacén):** `inventory.read`, `inventory.adjust`, `inventory.location.block`.
* **ACT-004 (Planificador de despacho):** `dispatch.plan`, `dispatch.confirm`.
* **ACT-005 (Comercial):** `commercial.order.approve`, `commercial.customer.maintain`.
* **ACT-006 (Finanzas):** `finance.payment.register`, `finance.payment.apply`, `finance.receivables.read`.
* **ACT-007 (Administrador):** `admin.identity.manage`, `admin.roles.manage`, `admin.masters.maintain`.

### Resolution of UNRES-006

**Issue:** EVD-REQ-001 does not define which internal actors can query the global audit log.
**Resolution:** 
We define an explicit authorization capability: `audit.global.read`.
We define `AuditViewer` as a SECURITY ROLE, not a new business/domain actor.
Only authenticated internal accounts explicitly assigned that capability (via the `AuditViewer` role) may query global Audit/Compliance information.
ACT-007 (Administrator) may manage role/permission assignments but does NOT automatically receive `audit.global.read`. Domain-specific actors may later receive narrower audit visibility where required, but that must not imply global audit visibility. This formally resolves UNRES-006.

---

## 4. Preservation of Baseline Constraints
* All A1 domain/module decisions preserved.
* All inventory invariants preserved.
* Financial Obligation AR/AP single-truth rule preserved.
* Register Payment / Apply Payment separation preserved.
* Dispatch cross-module consistency requirement preserved.
* Server SSOT for offline sync preserved.
* `mobile_licensing=false`
* `saas=false`
* `multi_tenant=false`
* No Redis, No Docker.
