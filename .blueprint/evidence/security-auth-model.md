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
* **Lifecycle & Expiration:**
  * Session identifiers must be regenerated after successful authentication and relevant privilege changes.
  * Logout invalidates the server-side session.
  * Administrative/security revocation must be able to invalidate active sessions.
  * Sessions must have idle expiration and absolute expiration.
  * Concrete expiration durations remain configuration/deployment decisions and are NOT defined in A2.
  * Credential/password reset or equivalent security-sensitive account recovery must invalidate affected active sessions where appropriate.
  * Authorization is evaluated against current server-side privileges and must not depend solely on stale client state.
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

## 4. Security Audit Obligations (FR-019, NFR-003)

A2 defines *what* categories of events require durable evidence. The exact audit event catalog, persistence mechanism, retention policy, physical schema, and tamper-resistance storage mechanism are explicitly deferred to A3.

### Required Audit Events
At a minimum, the system must durably record:
* Successful/failed authentication events where security-relevant.
* Session/token revocation.
* Password/account recovery security events where applicable.
* Role assignment changes.
* Permission/capability assignment changes.
* Creation/update/disablement of privileged users.
* Significant authorization denials.
* Privileged administrative actions.
* Access to global Audit/Compliance capability.
* Security-sensitive configuration changes.
* Offline synchronization rejection caused by revoked/stale authorization.
* Suspected replay/idempotency rejection where security-relevant.

### Audit Record Obligations
Each audit record must capture:
* Actor/account identity when available.
* Timestamp.
* Action/event category.
* Target/resource context when applicable.
* Outcome.
* Correlation identifier when applicable.

### Forbidden Audit Content (NFR-001)
The system must **never record**:
* Passwords or plaintext credentials.
* Refresh/access tokens or session secrets.
* Cryptographic private material.
* Unnecessary sensitive payloads (PII).

---

## 5. Preservation of Baseline Constraints
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

---

## Traceability

| Requirement | A2 Security & Auth Mapping |
| :--- | :--- |
| **ACT-001..ACT-007** | capability-oriented authorization and actor-to-capability mapping |
| **FR-019** | Security Audit Obligations |
| **FR-020** | provisional offline authorization for downloaded/local operational work |
| **FR-021** | mandatory reauthentication/reauthorization and reconciliation during synchronization |
| **FR-022** | authenticated and authorized access across all registered actors |
| **NFR-001** | authentication, authorization, secure session/token handling, ownership enforcement |
| **NFR-003** | durable security audit evidence |
| **NFR-005** | replay protection/idempotent synchronized processing principle |
| **NFR-007** | rejected/stale offline operations become traceable conflicts instead of silently committing |
| **NFR-012** | correlation identifiers and security/audit observability support |
| **BR-008** | security alignment with idempotent retry semantics |
| **BR-010** | server SSOT and authoritative reconciliation of offline work |
| **UC-001** | authentication establishes access constrained by authorization |
| **UNRES-006** | formally resolved via `audit.global.read` + `AuditViewer`, without implicit Administrator access |
