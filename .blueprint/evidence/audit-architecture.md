# SENTAI – Audit Architecture

**Artifact ID:** EVD-ARCH-AUDIT-001
**Blueprint Phase:** Architecture & Security Data (A3)
**Status:** READY_FOR_REVIEW
**Bases en:** EVD-ARCH-SEC-001 (Security Model), EVD-ARCH-TXN-001 (Transactional Consistency), EVD-REQ-001
**A2 continuity:** EVD-ARCH-SEC-001 section 4 defined audit obligations; A3 formalizes the catalog, persistence, retention, and traceability model.
**Corrections Applied:** A3-CORRECTION-7 (UNRES-007 introduced), A3-CORRECTION-8 (retention/purge provider-neutral)

---

## 1. Audit Persistence Architecture

### 1.1 Principle

Audit evidence must be durable, immutable after commit, and decoupled from application data lifecycle. Audit records are proof of what happened; they must survive independently of the business objects they describe.

### 1.2 Storage

- Audit events are stored in MySQL/InnoDB in a dedicated audit table group (e.g., logically separated within the same database, or a dedicated schema partition).
- Audit records are **INSERT-only** from the application layer. No `UPDATE` or `DELETE` is permitted via application code.
- Audit records are never cascaded-deleted due to deletion of related business entities.
- Physical audit table(s) may be separated from operational tables for retention management and access control purposes.

### 1.3 Audit / Business Transaction Relationship

The relationship between audit evidence and the business transaction it describes is **explicit and mandatory**:

| Scenario | Audit Requirement |
|---|---|
| Inventory adjustment commits | Mandatory audit event in the same InnoDB transaction |
| Dispatch confirmation commits | Mandatory audit event in the same InnoDB transaction |
| Register Payment commits | Mandatory audit event in the same InnoDB transaction |
| Apply Payment commits | Mandatory audit event in the same InnoDB transaction |
| Allocation commits | Mandatory audit event in the same InnoDB transaction |
| Authentication event occurs | Durable audit event; may be asynchronous for non-critical paths, but security-relevant events (failed login, revocation) are synchronous |
| Role/permission change commits | Mandatory audit event in the same InnoDB transaction |
| Privileged admin action commits | Mandatory audit event in the same InnoDB transaction |
| Offline sync applied | Audit event in the same transaction as the sync operation |
| Offline sync rejected | Audit event recording the rejection |

**Implication:** A business transaction that cannot write its mandatory audit event must rollback. Committed business state without mandatory durable audit evidence is an architecture violation.

### 1.4 Tamper Resistance

- Application-layer controls prevent updates and deletes.
- Database-level GRANT configuration must restrict DELETE/UPDATE privileges on audit tables to privileged/DBA-only access (an administrative control, not an application feature).
- Access to global audit data requires the `audit.global.read` capability (resolved in UNRES-006, EVD-ARCH-SEC-001).
- Physical tamper-resistance mechanisms (e.g., write-once storage, cryptographic chaining) are not required at this architecture stage but may be added as a future hardening measure if regulatory requirements dictate.

---

## 2. Audit Event Catalog

All events listed below must produce a durable audit record. "Mandatory TX" = must be in the same InnoDB transaction as the business operation. "Post-commit" = written after the business transaction commits (acceptable for non-transactionally-coupled events).

### 2.1 Authentication & Session Events (from A2)

| Event Type | Actor Context | Mandatory TX | Key Fields |
|---|---|---|---|
| `auth.login.success` | User account | Post-commit | actor_id, timestamp, surface/channel, correlation_id |
| `auth.login.failure` | Attempted identity | Post-commit | attempted_identity, failure_reason, timestamp, correlation_id |
| `auth.logout` | User account | Post-commit | actor_id, timestamp, session_ref, correlation_id |
| `auth.session.revoked` | Admin or system | Post-commit | actor_id, revoked_session_ref, revoked_by, timestamp, correlation_id |
| `auth.token.revoked` | Admin or system | Post-commit | actor_id, token_ref, revoked_by, timestamp, correlation_id |
| `auth.password.reset.initiated` | User or admin | Post-commit | actor_id, initiated_by, timestamp, correlation_id |
| `auth.account.locked` | System | Post-commit | actor_id, reason, timestamp, correlation_id |

### 2.2 Authorization & Role Events (from A2)

| Event Type | Actor Context | Mandatory TX | Key Fields |
|---|---|---|---|
| `authz.role.assigned` | Admin (admin.roles.manage) | Yes | actor_id, target_user_id, role_id, assigned_by, timestamp, correlation_id |
| `authz.role.revoked` | Admin (admin.roles.manage) | Yes | actor_id, target_user_id, role_id, revoked_by, timestamp, correlation_id |
| `authz.permission.assigned` | Admin (admin.roles.manage) | Yes | actor_id, target_role_id, permission_id, assigned_by, timestamp, correlation_id |
| `authz.permission.revoked` | Admin (admin.roles.manage) | Yes | actor_id, target_role_id, permission_id, revoked_by, timestamp, correlation_id |
| `authz.denial.significant` | Any | Post-commit | actor_id, attempted_operation, resource_ref, reason, timestamp, correlation_id |
| `authz.audit.access` | AuditViewer | Post-commit | actor_id, query_scope, timestamp, correlation_id |

### 2.3 Identity / User Administration Events (from A2)

| Event Type | Actor Context | Mandatory TX | Key Fields |
|---|---|---|---|
| `admin.user.created` | Admin (admin.identity.manage) | Yes | actor_id, created_user_id, created_by, timestamp, correlation_id |
| `admin.user.updated` | Admin (admin.identity.manage) | Yes | actor_id, updated_user_id, fields_changed, updated_by, timestamp, correlation_id |
| `admin.user.disabled` | Admin (admin.identity.manage) | Yes | actor_id, disabled_user_id, disabled_by, timestamp, correlation_id |
| `admin.masters.changed` | Admin (admin.masters.maintain) | Yes | actor_id, resource_type, resource_id, fields_changed_ref, changed_by, timestamp, correlation_id |

### 2.4 Inventory Events

| Event Type | Actor Context | Mandatory TX | Key Fields |
|---|---|---|---|
| `inventory.adjustment.applied` | Supervisor (inventory.adjust) | Yes | actor_id, inventory_item_id, product_id, location_id, lot_ref, serial_ref, state_before, state_after, qty_before, qty_after, reason_ref, correlation_id, timestamp |
| `inventory.item.state.changed` | Supervisor or system | Yes | actor_id, inventory_item_id, state_before, state_after, reason_ref, correlation_id, timestamp |
| `inventory.location.blocked` | Supervisor (inventory.location.block) | Yes | actor_id, location_id, blocked_by, reason_ref, timestamp, correlation_id |
| `inventory.location.unblocked` | Supervisor (inventory.location.block) | Yes | actor_id, location_id, unblocked_by, reason_ref, timestamp, correlation_id |
| `inventory.asn.received` | Warehouse operator (warehouse.receive) | Yes | actor_id, asn_id, receipt_id, items_ref, timestamp, correlation_id |
| `inventory.putaway.completed` | Warehouse operator (warehouse.putaway) | Yes | actor_id, receipt_id, putaway_task_id, location_id, timestamp, correlation_id |

### 2.5 Reservation / Allocation Events

| Event Type | Actor Context | Mandatory TX | Key Fields |
|---|---|---|---|
| `fulfillment.allocation.created` | System / Fulfillment module | Yes | actor_id, order_id, allocation_id, inventory_item_id, qty_reserved, timestamp, correlation_id |
| `fulfillment.allocation.released` | System / Fulfillment module | Yes | actor_id, order_id, allocation_id, inventory_item_id, qty_released, reason_ref, timestamp, correlation_id |

### 2.6 Dispatch Events

| Event Type | Actor Context | Mandatory TX | Key Fields |
|---|---|---|---|
| `dispatch.confirmed` | Dispatch planner (dispatch.confirm) | Yes | actor_id, dispatch_id, order_refs, items_dispatched_ref, qty_dispatched_ref, financial_obligation_created_ref, timestamp, correlation_id |
| `dispatch.cancelled` | Dispatch planner | Yes | actor_id, dispatch_id, reason_ref, timestamp, correlation_id |

### 2.7 Finance Events

| Event Type | Actor Context | Mandatory TX | Key Fields |
|---|---|---|---|
| `finance.payment.registered` | Finance (finance.payment.register) | Yes | actor_id, payment_id, amount, customer_id, timestamp, correlation_id |
| `finance.payment.applied` | Finance (finance.payment.apply) | Yes | actor_id, payment_id, payment_application_ids_ref, obligation_ids_ref, amount_applied, timestamp, correlation_id |
| `finance.obligation.created` | System (Dispatch confirmation) | Yes | actor_id, obligation_id, order_id, dispatch_id, amount, timestamp, correlation_id |
| `finance.obligation.closed` | System (Apply Payment) | Yes | actor_id, obligation_id, closed_by_application_refs, timestamp, correlation_id |

### 2.8 Offline Sync Events

| Event Type | Actor Context | Mandatory TX | Key Fields |
|---|---|---|---|
| `sync.operation.applied` | Mobile operator | Yes (within sync TX) | actor_id, device_ref, operation_type, idempotency_key, result_ref, timestamp, correlation_id |
| `sync.operation.rejected` | System | Post-commit | actor_id, device_ref, operation_type, idempotency_key, rejection_reason, timestamp, correlation_id |
| `sync.conflict.generated` | System | Post-commit | actor_id, device_ref, operation_type, conflict_ref, server_state_ref, timestamp, correlation_id |
| `sync.authorization.failed` | System | Post-commit | actor_id, device_ref, operation_type, revocation_ref, timestamp, correlation_id |

### 2.9 Security / Idempotency Events (from A2)

| Event Type | Actor Context | Mandatory TX | Key Fields |
|---|---|---|---|
| `security.config.changed` | Admin | Yes | actor_id, config_area, changed_by, timestamp, correlation_id |
| `security.replay.rejected` | System | Post-commit | actor_id, idempotency_key, operation_type, reason, timestamp, correlation_id |

---

## 3. Audit Record Mandatory Fields

Every audit record must include at minimum:

| Field | Description |
|---|---|
| `event_type` | Canonical event type from catalog |
| `actor_id` | Authenticated user ID (null only when genuinely unauthenticated, e.g., failed login attempt) |
| `actor_role_snapshot` | Role(s) of actor at time of event (snapshot, not live join) |
| `timestamp` | UTC timestamp with millisecond precision |
| `aggregate_type` | Domain entity type affected (e.g., `InventoryItem`, `Dispatch`, `Payment`) |
| `aggregate_id` | Identifier of the affected entity |
| `operation` | Canonical operation performed |
| `outcome` | Result: `success`, `failure`, `conflict`, `rejection` |
| `correlation_id` | Correlation identifier linking request, actor, operation, and related audit events |
| `source_surface` | Originating surface: `backoffice`, `customer_portal`, `mobile`, `system` |

### Forbidden Audit Content (NFR-001, EVD-ARCH-SEC-001 section 4)

The following must **never** appear in any audit record:

- Passwords or plaintext credentials
- Session tokens, access tokens, refresh tokens
- Cryptographic private keys or secrets
- Unnecessary PII beyond what is required for audit traceability

---

## 4. Correlation / Traceability Model

### 4.1 Purpose

The correlation model supports NFR-012. It allows linking:
- Inbound HTTP request
- Authenticated actor
- Use case execution
- InnoDB transaction
- Business operation result
- One or more audit events
- Offline sync attempt and its outcome
- Conflict or rejection

### 4.2 Correlation ID Responsibility

| Layer | Responsibility |
|---|---|
| **API Gateway / Entry Point** | Generate or accept `X-Correlation-ID` (or equivalent) on every inbound request. If client provides one, validate and use it. If not, generate server-side. |
| **Application Layer** | Propagate correlation ID through the use case execution scope. Pass to all sub-operations, repository calls, and audit event writes. |
| **Audit Events** | Every audit record includes the correlation ID of the originating request/operation. |
| **Mobile Sync** | Mobile operations carry a correlation ID generated at operation creation time. The server propagates it through sync processing and audit. |

### 4.3 Correlation ID Format and Generation

The specific format (UUID, ULID, etc.) is a deployment decision. The architecture requires:
- Globally unique within the system's operational lifetime
- Fixed at request entry; not regenerated mid-request
- Included in all log lines and audit records for the duration of the request

### 4.4 Traceability Chain

```
Inbound Request
  └── correlation_id assigned / validated at entry
       └── actor_id resolved (authentication)
            └── capability checked (authorization)
                 └── Business Transaction begins (InnoDB)
                      ├── Business rows mutated
                      └── Audit Event(s) written (correlation_id, actor_id, timestamp)
                           └── Transaction commits
                                └── [Post-commit: notification, external call if any]
```

For offline sync:
```
Mobile Operation (idempotency_key, correlation_id)
  └── Sync API receives operation
       └── Auth revalidated
            └── Idempotency key checked
                 └── Business Transaction (same chain as above)
                      └── Audit: sync.operation.applied / sync.conflict.generated
```

### 4.5 No Middleware Prescription

This architecture defines the *responsibility* of correlation but does not prescribe a specific middleware class, header, or interceptor implementation. Those decisions belong to the implementation phase (Laravel middleware, request lifecycle hooks, etc.).

---

## 5. Audit Retention Policy

### 5.1 Unresolved Item: UNRES-007 (A3-CORRECTION-7)

> **UNRES-007 — Exact audit retention periods require legal, regulatory, fiscal, and product evidence before implementation.**
>
> Exact retention durations (in days, months, or years) are **not** defined in this architecture document. Legal, regulatory, and fiscal requirements have not been evidenced for this project. Retention durations must be determined through formal product/legal input before implementation. Architecture establishes the category structure and the requirement to define durations; it does not fabricate them.

This is a new unresolved item introduced in A3.

### 5.2 Retention Categories

| Category | Events | Retention Guidance |
|---|---|---|
| **Security-Critical** | Authentication events, session revocations, privilege changes, security denials | Long retention. Exact duration: **UNRESOLVED — UNRES-007** |
| **Financial** | Payment registration, payment application, obligation creation/closure | Long retention. Exact duration: **UNRESOLVED — UNRES-007** |
| **Inventory-Operational** | Inventory adjustments, dispatch confirmation, allocation changes | Medium retention. Exact duration: **UNRESOLVED — UNRES-007** |
| **Sync/Conflict** | Offline sync events, conflict records | Operational retention. Exact duration: **UNRESOLVED — UNRES-007** |
| **Administrative** | User creation/modification, master data changes, role/permission changes | Long retention. Exact duration: **UNRESOLVED — UNRES-007** |

### 5.3 Retention Architecture Constraints (Corrected — A3-CORRECTION-8)

- Audit records are not deleted by application business logic under any operational scenario.
- Retention enforcement is a **governed operational procedure**, not an application feature.
- When retention action is required by policy, permitted mechanisms include: archival, partitioning, anonymization where legally appropriate, or purge — but only under an approved retention procedure that satisfies applicable legal, financial, and security requirements.
- The exact physical mechanism for retention enforcement (e.g., whether records are archived to a separate store, partitioned within the same database, anonymized in place, or purged) is deferred to the Operations/Data implementation phase and must be approved before execution.
- Application business logic must not casually delete audit evidence.
- Any retention operation must preserve applicable legal, financial, and security obligations for the relevant category (UNRES-007).
- Backup/recovery procedures must include the audit store.

---

## 6. Preservation of A1/A2 Decisions

This document does not alter:
- EVD-ARCH-SEC-001 section 4 (audit obligations and forbidden content).
- UNRES-006 resolution (AuditViewer role / `audit.global.read` capability).
- Module boundaries and aggregate candidates from EVD-ARCH-001.
- `redis=false`, `mobile_licensing=false`, `saas=false`, `multi_tenant=false`.
- UNRES-001 through UNRES-005 remain unresolved.

New unresolved item introduced in A3:
- **UNRES-007** — Exact audit retention periods require legal/regulatory/fiscal/product evidence before implementation (Section 5.1).

---

## Traceability

| Requirement / Decision | Audit Architecture Mapping |
|:---|:---|
| FR-019 | Audit event catalog (Section 2); all required event categories |
| NFR-003 | Durable, append-only audit persistence (Section 1) |
| NFR-012 | Correlation / Traceability model (Section 4) |
| NFR-005 | Idempotency key in sync audit events |
| NFR-007 | Sync rejection and conflict audit events |
| EVD-ARCH-SEC-001 section 4 | Catalog extends A2 security audit obligations |
| UNRES-006 | AuditViewer capability enforced; access auditable via `authz.audit.access` |
| UNRES-007 | Exact retention periods unresolved; category structure defined (Section 5.1) |
| UC-013 | `dispatch.confirmed` mandatory audit within Dispatch transaction |
| UC-014..UC-016 | Finance audit events within respective transactions |
| BR-001..BR-004 | Inventory audit events within adjustment/allocation transactions |
| A3-CORRECTION-7 | UNRES-007 introduced; retention durations not fabricated |
| A3-CORRECTION-8 | Retention mechanism provider-neutral; not restricted to physical archive only |
