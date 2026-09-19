# SENTAI – Transactional Consistency, Concurrency & Idempotency Architecture

**Artifact ID:** EVD-ARCH-TXN-001
**Blueprint Phase:** Architecture & Security Data (A3)
**Status:** READY_FOR_REVIEW
**Bases en:** EVD-ARCH-001, EVD-ARCH-DATA-001, EVD-REQ-001
**Blueprint Debt Reference:** LuisHdezE/SoftwareDevelopmentBlueprint issue #40

---

## 1. Foundational Principle

> A business operation requiring several dependent state mutations is atomic from the domain perspective: all mandatory effects commit, or none do.

For SENTAI, MySQL/InnoDB transactional guarantees are the mechanism that enforces this principle for server-side business state. `autocommit` mode is **not sufficient** for multi-step business operations. Explicit transaction boundaries must be used for all operations involving more than a single isolated mutation.

---

## 2. MySQL/InnoDB Concurrency Strategy

### 2.1 Row-Level Locking

InnoDB provides row-level locking. This allows high concurrency for operations touching different rows, but requires careful ordering to prevent deadlocks when multiple rows are involved.

### 2.2 Isolation Level

**Default isolation level: `REPEATABLE READ`** (InnoDB default).

This level prevents dirty reads and non-repeatable reads within a transaction. However, it is subject to phantom reads in range queries. For operations where the total count of matching rows is part of the decision (e.g., counting available inventory), validation must occur inside the locked transactional scope.

For specific operations requiring stronger guarantees (documented in the matrix below), **`SELECT ... FOR UPDATE`** (or framework equivalent) is used to escalate to pessimistic exclusive row locking within the current transaction.

### 2.3 Pessimistic Locking Where Required

Where concurrent transactions can invalidate a prior read, the validation must occur **inside** the protected transactional/concurrency boundary. The following anti-pattern is prohibited:

**Prohibited pattern:**
1. Read state outside transaction (or before lock)
2. Decide based on that read
3. Mutate inside transaction

Another transaction could alter state between steps 1 and 3. For any invariant-sensitive validation, the read and validation must occur within the same locked transactional scope.

**Required pattern for invariant-sensitive operations:**
1. Begin transaction
2. Lock relevant rows (`SELECT ... FOR UPDATE` or framework equivalent)
3. Validate invariants against locked data
4. Mutate
5. Commit (or rollback on invariant violation or error)

### 2.4 Lock Ordering Principle

To prevent deadlocks, all operations that must lock multiple rows or tables must acquire locks in a **consistent, deterministic order**. The canonical lock acquisition order is:

1. `inventory_items` (by primary key ascending)
2. `allocations`
3. `dispatch_records`
4. `financial_obligations`
5. `payments`
6. `payment_applications`
7. `audit_events` (append-only; no row lock needed for INSERT)

This order must be respected by all concurrent operations that touch multiple of these tables. Implementation must not deviate without documented justification.

### 2.5 Transaction Duration Rule

Database transactions must remain **short**. The following must **not** be performed inside an open InnoDB transaction unless architecturally unavoidable and explicitly justified:

- Remote HTTP calls to external services
- External API requests (payment gateways, shipping carriers, etc.)
- User interaction waits
- Long-running CPU-intensive computation
- Unrelated file system operations
- Arbitrary sleeps
- Uncontrolled network I/O

If an external side effect (e.g., sending an email notification, calling an external shipping API) must occur as part of a business operation, the database transaction must commit first. The external call is then a best-effort post-commit action. The consistency boundary at this seam is explicit: the business operation is committed; the external side effect is not transactional with it.

**Note:** Outbox pattern, saga orchestration, and message brokers are **not** current Product Truth and must not be introduced speculatively in this architecture.

### 2.6 Deadlock and Transient Failure Policy

Deadlocks and equivalent transient concurrency failures (e.g., lock wait timeout) may occur even in a correctly designed system. The following policy applies:

1. **Complete rollback:** A failed transaction must be rolled back completely. Partial state must never persist.
2. **Whole-transaction retry:** Retries operate on the entire business transaction. Resuming from the middle of a failed transaction is forbidden.
3. **Bounded retry:** Retries must be bounded. Concrete maximum retry counts are a deployment/configuration decision; the architecture requires the bound to be finite and configurable.
4. **Idempotency prerequisite for retry:** A transaction may only be safely retried if the operation is idempotent (see Section 5). Non-idempotent operations must not be blindly retried.
5. **Non-transient domain conflicts:** A business conflict (e.g., insufficient inventory, already-applied payment) is not a transient failure. It must surface as a domain error, not be swallowed by a retry loop.
6. **Retry-safe operations:** Retry is safe when an idempotency key ensures the operation produces the same committed result on repeated execution.

---

## 3. Transaction Boundary Matrix

### Legend

- **TX Required:** Whether an explicit InnoDB transaction with manual commit boundary is required (vs. single-statement autocommit).
- **Locking Strategy:** Describes the concurrency protection mechanism.
- **Isolation Requirement:** The minimum isolation level required for correct execution.
- **Retry Semantics:** Whether safe to retry under the deadlock/transient policy.
- **Idempotency Relationship:** How the operation relates to NFR-005 idempotency requirements.

---

### 3.1 Inventory Reservation / Allocation

| Field | Detail |
|---|---|
| **Use Case / Operation** | Allocate inventory for a Customer Order (Fulfillment initiation) |
| **Atomic Effects** | (1) Validate InventoryItem state and quantities; (2) Validate `Reserved + requestedQty <= OnHand`; (3) Create Allocation record; (4) Increment `reserved_qty` on InventoryItem; (5) Write mandatory audit event |
| **Transaction Required** | **YES** – multiple row mutations; invariant `Reserved <= OnHand` must be enforced atomically |
| **Persistence Resources** | `inventory_items`, `allocations`, `allocation_lines`, `audit_events` |
| **Domain Invariants** | `Reserved <= OnHand` (BR-001); InventoryItem must be in eligible state (not blocked/quarantine/damaged/expired) (BR-003, BR-004) |
| **Concurrency Risk** | Multiple concurrent allocations against the same InventoryItem (overselling risk) |
| **Locking / Concurrency Strategy** | `SELECT ... FOR UPDATE` on the target `inventory_items` row(s) at transaction start. Lock acquired before any quantity validation. |
| **Isolation Requirement** | REPEATABLE READ with explicit pessimistic row lock |
| **Retry Semantics** | Safe to retry if idempotency key provided; the second attempt will detect the existing allocation and return the same result without duplicating |
| **Idempotency Relationship** | Allocation operation must carry an idempotency key; duplicate allocation for the same order/item is a no-op returning the existing allocation, not a domain error |
| **Audit Relationship** | Mandatory: allocation event written within same transaction; if transaction rolls back, audit event also rolls back |
| **Verification Requirement** | Unit-level: invariant check under concurrent load; integration-level: concurrent allocation test must not produce `Reserved > OnHand` |

---

### 3.2 Inventory Adjustment

| Field | Detail |
|---|---|
| **Use Case / Operation** | Manual inventory quantity/state change (UC-006, FR-004) |
| **Atomic Effects** | (1) Lock InventoryItem row; (2) Validate current state allows adjustment; (3) Apply quantity delta; (4) Update state if applicable; (5) Create adjustment record; (6) Write mandatory durable audit event with before/after values |
| **Transaction Required** | **YES** – quantity mutation and durable audit are coupled; a committed adjustment without audit evidence is an invariant violation |
| **Persistence Resources** | `inventory_items`, `inventory_adjustments`, `inventory_adjustment_lines`, `audit_events` |
| **Domain Invariants** | Post-adjustment `OnHand` must remain consistent with existing `reserved_qty`; `OnHand` must not go negative; adjustment does not bypass blocked/quarantine rules |
| **Concurrency Risk** | Concurrent reservation or dispatch could invalidate the quantity being read before adjustment |
| **Locking / Concurrency Strategy** | `SELECT ... FOR UPDATE` on `inventory_items`. No validation may occur outside the lock. |
| **Isolation Requirement** | REPEATABLE READ with explicit pessimistic row lock |
| **Retry Semantics** | Safe to retry with idempotency key; retry detects existing adjustment record |
| **Idempotency Relationship** | Adjustment carries idempotency key; duplicate submission returns existing result without re-applying delta |
| **Audit Relationship** | Audit event is **mandatory within the same transaction**. Adjustment without audit evidence must not commit. |
| **Verification Requirement** | Audit event must exist for every committed adjustment record; invariant check post-adjustment |

---

### 3.3 Confirm Dispatch (UC-013) — Critical

This is the highest-criticality transactional boundary in SENTAI.

| Field | Detail |
|---|---|
| **Use Case / Operation** | Confirm physical dispatch of packages (UC-013) |
| **Atomic Effects** | (1) Validate Dispatch record current state (must be `Ready`/`Pending`, not already `Completed` or `Cancelled`); (2) Lock Dispatch record; (3) Validate all included InventoryItems states; (4) Lock InventoryItem rows (canonical lock order); (5) Decrement `on_hand_qty` by dispatched quantity for each item; (6) Validate resulting `OnHand >= Reserved` after decrement; (7) Transition Dispatch to `Completed`; (8) Create or update Financial Obligation where required (BR-005); (9) Write mandatory audit event; |
| **Transaction Required** | **YES – mandatory**. No partial commit allowed. `Dispatch = Completed` must never be observable without all mandatory effects (inventory decrement, finance effect, audit) committed in the same transaction. |
| **Persistence Resources** | `dispatch_records`, `dispatch_lines`, `inventory_items`, `financial_obligations`, `audit_events` |
| **Domain Invariants** | `OnHand` decremented exactly once per dispatch line; `Reserved <= OnHand` after decrement; Financial Obligation created/updated per BR-005; Dispatch state machine: `Completed` only reachable through this transaction |
| **Concurrency Risk** | Concurrent allocation or another dispatch consuming the same inventory; concurrent dispatch of the same record (idempotency) |
| **Locking / Concurrency Strategy** | (1) `SELECT ... FOR UPDATE` on `dispatch_records` first; (2) `SELECT ... FOR UPDATE` on `inventory_items` in ascending PK order (lock ordering rule). Finance rows locked if existing obligation being updated. |
| **Isolation Requirement** | REPEATABLE READ with explicit pessimistic row locks; all validation occurs after locks acquired |
| **Retry Semantics** | Safe to retry with idempotency key; if Dispatch is already `Completed`, retry returns existing committed state without re-applying effects |
| **Idempotency Relationship** | A Dispatch confirmation with the same idempotency key that finds the Dispatch already `Completed` is a no-op; it must not decrement OnHand a second time |
| **Audit Relationship** | Audit event is **mandatory within the same transaction**. Dispatch `Completed` without audit evidence must not be allowed to commit. |
| **Verification Requirement** | Integration test: concurrent dispatch of same record must produce exactly one `Completed` state; OnHand must decrease exactly once; Financial Obligation must exist; audit event must exist |

**Explicit prohibition:**
`Dispatch = Completed` while Inventory decrement or mandatory Finance effect failed is **architecturally forbidden**. No code path may transition a Dispatch to `Completed` outside this atomic transaction.

---

### 3.4 Register Payment

| Field | Detail |
|---|---|
| **Use Case / Operation** | Register an incoming payment (UC-014, FR-014) |
| **Atomic Effects** | (1) Validate actor capability (`finance.payment.register`); (2) Create `payments` record with full amount and zero applied amount; (3) Write audit event |
| **Transaction Required** | **YES** – payment creation and audit are coupled |
| **Persistence Resources** | `payments`, `audit_events` |
| **Domain Invariants** | Payment is independent of any obligation at registration time; unapplied balance = total amount at creation; BR-006 (independence of Register from Apply) |
| **Concurrency Risk** | Low; payment registration is a single-aggregate operation |
| **Locking / Concurrency Strategy** | No row lock required on existing rows; INSERT is atomic |
| **Isolation Requirement** | READ COMMITTED sufficient; no shared row contention |
| **Retry Semantics** | Safe to retry with idempotency key; duplicate registration returns existing payment without creating a second record |
| **Idempotency Relationship** | Payment carries idempotency key; duplicate submission detects existing payment and returns it |
| **Audit Relationship** | Audit event within same transaction |
| **Verification Requirement** | Duplicate registration test; payment balance = total amount after registration |

---

### 3.5 Apply Payment

| Field | Detail |
|---|---|
| **Use Case / Operation** | Apply a registered payment to one or more Financial Obligations (UC-015/UC-016, FR-015/FR-016) |
| **Atomic Effects** | (1) Lock `payments` row; (2) Validate available unapplied balance >= application amount; (3) Lock `financial_obligations` rows in ascending PK order; (4) Validate obligation is open and not already fully applied; (5) Create `payment_applications` record(s); (6) Decrement payment's available balance; (7) Decrement obligation's outstanding balance; (8) Close obligation if fully satisfied (BR-007); (9) Write audit event |
| **Transaction Required** | **YES** – multiple rows; race conditions on available balance must be prevented |
| **Persistence Resources** | `payments`, `financial_obligations`, `payment_applications`, `audit_events` |
| **Domain Invariants** | Available payment balance >= application amount; obligation cannot be over-applied; no double application of same payment to same obligation; obligation closes only when fully satisfied (BR-007) |
| **Concurrency Risk** | Concurrent applications against the same payment (double-spend); concurrent closure of the same obligation |
| **Locking / Concurrency Strategy** | `SELECT ... FOR UPDATE` on `payments` row first; then `SELECT ... FOR UPDATE` on `financial_obligations` in ascending PK order (lock ordering rule). Unique constraint on `payment_applications (payment_id, obligation_id)` as defense-in-depth against double application. |
| **Isolation Requirement** | REPEATABLE READ with explicit pessimistic row locks |
| **Retry Semantics** | Safe to retry with idempotency key; duplicate application detected via existing `payment_applications` record |
| **Idempotency Relationship** | Application carries idempotency key; if a `payment_applications` record with matching idempotency key exists, return it without re-applying |
| **Audit Relationship** | Audit event within same transaction |
| **Verification Requirement** | Concurrent application test must not produce double-spend; obligation balance must be >= 0 after application |

---

### 3.6 Offline Sync Mutation

| Field | Detail |
|---|---|
| **Use Case / Operation** | Apply an offline-queued mobile operation to server state (FR-020, FR-021, NFR-007, BR-010) |
| **Atomic Effects** | (1) Revalidate actor identity and current authorization against server state; (2) Validate idempotency key (detect replay/already-applied); (3) Read and lock relevant server-side rows; (4) Validate all business invariants against current server state; (5) Apply mutation; (6) Write audit event; (7) Return success or structured conflict |
| **Transaction Required** | **YES** – authorization revalidation, invariant validation, mutation, and audit must be atomic |
| **Persistence Resources** | Depends on operation type (inventory, dispatch, etc.) plus `audit_events` |
| **Domain Invariants** | Server SSOT; invariants apply against server state at sync time, not at offline time; stale offline state does not override server state |
| **Concurrency Risk** | Concurrent syncs from same or different devices; same operation queued multiple times due to offline retry |
| **Locking / Concurrency Strategy** | Same strategy as the underlying business operation (allocation, dispatch, etc.). Authorization check inside the transactional boundary. |
| **Isolation Requirement** | Same as underlying operation |
| **Retry Semantics** | Idempotency key provided by mobile client; server detects already-applied operations and returns the committed result without re-applying |
| **Idempotency Relationship** | NFR-005; every offline operation must carry a stable idempotency key generated at operation-creation time on the mobile client |
| **Audit Relationship** | Sync attempts (successful, conflicted, and rejected) are auditable events |
| **Verification Requirement** | Replay test: same offline sync payload submitted twice must produce one committed state; conflict test: sync against stale server state must produce conflict record, not silent commit |

---

## 4. Idempotency Architecture

### 4.1 Principle

Transaction retry and client/request retry must not duplicate business effects. This preserves NFR-005 and all offline/idempotency requirements established in A1.

### 4.2 Idempotency Identifier Lifecycle

1. **Generation:** The idempotency key is generated by the caller (API client, mobile operation queue) at the moment the operation is first created. Keys must be stable across retries of the same logical operation.
2. **Scope:** The key is scoped to a specific operation type and business context (e.g., allocation for order X, dispatch of dispatch record Y). Cross-operation reuse of the same key is a client error.
3. **Storage:** The server persists the idempotency key alongside the result of the committed operation (in the same transaction).
4. **Detection:** On receiving a request, the server checks for an existing committed result with the same key before beginning the business operation.
5. **Response:** If a prior committed result exists, return it without re-executing the operation.
6. **Expiry:** Idempotency keys are not permanent records. Retention of idempotency records is a configuration/deployment decision, documented under audit/retention policy. They must persist long enough to cover realistic retry windows.

### 4.3 Transaction Retry vs. Client Retry

| Scenario | Handling |
|---|---|
| **DB deadlock / lock timeout** | Rollback completely; retry entire business transaction (bounded); idempotency key prevents duplicate effect |
| **API client retry (timeout, 5xx)** | Server checks idempotency key; if operation already committed, return existing result |
| **Offline operation replay** | Server detects idempotency key in committed state; returns result without re-applying |
| **Domain conflict** | Return structured domain error (e.g., insufficient inventory); must not be retried blindly |
| **Duplicate financial application** | Idempotency key + unique constraint on `payment_applications`; second attempt returns existing record |
| **Duplicate dispatch confirmation** | Idempotency key + Dispatch state check; `Completed` dispatch returns existing result |

### 4.4 Forbidden Retry Patterns

- Do **not** retry a business transaction that failed due to a domain conflict (e.g., `Reserved > OnHand` would result, obligation already closed). These are deterministic failures, not transient failures.
- Do **not** resume a failed transaction from the middle. Always rollback and restart from the beginning.
- Do **not** allow unbounded retries. The retry bound must be finite.

---

## 5. Schema Migration Strategy

### 5.1 Version-Controlled Migrations

All schema changes must be expressed as discrete, version-controlled migration files. The migration tool will be determined at implementation (likely Laravel Migrations or equivalent). No schema change is applied to production outside the migration system.

### 5.2 Forward Migration Philosophy

Migrations are designed to apply forward. The canonical path is forward evolution. Every migration must:
- Be idempotent where feasible (safe to run on already-applied state)
- Apply exactly once per environment
- Be committed to version control before deployment

### 5.3 Rollback Limitations

MySQL DDL operations (e.g., `ALTER TABLE`, `CREATE TABLE`) are not transactional and cannot be rolled back by InnoDB within a transaction. Therefore:
- Schema rollbacks are treated as new forward migrations, not literal reversions.
- A "rollback" migration (if needed) must be a separate, explicit migration that undoes the structural change via a new DDL statement.
- Rollback plans must be prepared before any risky migration is applied to production.

### 5.4 Production Data Preservation

- Migrations must **not** irreversibly destroy production data unless explicitly authorized by a governance decision.
- Destructive operations (DROP COLUMN, DROP TABLE, truncation) are prohibited without explicit governance approval and a data backup verification step.
- Column removal must follow a deprecation pattern: (1) stop writing to column, (2) deploy, (3) verify no reads remain, (4) remove column in a subsequent migration.

### 5.5 Compatibility Considerations

- Migrations that modify shared reference tables (products, customers) must consider read-heavy access patterns.
- Index creation on large tables should use `ALGORITHM=INPLACE` or equivalent non-locking strategy where supported and verified.
- Foreign key additions must be validated against existing data before deployment.

### 5.6 Backup Dependency for Risky Changes

Production deployments involving structural changes to high-traffic or large tables require:
- A verified backup taken immediately before migration application.
- A tested rollback plan (as a forward-rollback migration).
- A maintenance window or zero-downtime strategy, depending on migration scope.

### 5.7 Constraints, Indexes, and Foreign Keys

- Foreign keys are defined in migrations for all relationships that must be enforced at the database level.
- Unique constraints are defined in migrations for idempotency keys and business-unique identifiers.
- Indexes are defined in migrations, not added ad hoc in production.
- Not-null constraints reflect domain mandatory fields.

---

## 6. Preservation of A1/A2 Decisions

This document does not alter:
- Module boundaries, aggregate candidates, or critical invariants from EVD-ARCH-001.
- Security model constraints from EVD-ARCH-SEC-001.
- All `UNRES-001..UNRES-005` remain unresolved.
- `redis=false`, `mobile_licensing=false`, `saas=false`, `multi_tenant=false`.
- No outbox, saga, or message broker introduced.

---

## Traceability

| Requirement / Decision | Transactional Architecture Mapping |
|:---|:---|
| BR-001 | `Reserved <= OnHand` enforced via pessimistic lock in Allocation and Dispatch transactions |
| BR-002 | OnHand decrement only on confirmed Dispatch; enforced by Dispatch atomicity (3.3) |
| BR-003, BR-004 | Inventory eligibility validated inside locked transaction |
| BR-005 | Financial Obligation creation within Dispatch transaction (3.3) |
| BR-006 | Register Payment independence from Apply Payment (separate transactions 3.4, 3.5) |
| BR-007 | Obligation closure condition validated inside Apply Payment transaction |
| BR-008 | Idempotent retry semantics for sync operations |
| BR-010 | Server SSOT enforced in Offline Sync transaction (3.6) |
| NFR-005 | Idempotency identifier lifecycle (Section 4) |
| NFR-007 | Conflict generation on stale sync; not silent commit |
| NFR-012 | Correlation ID flows through all transaction boundaries |
| UC-013 | Dispatch atomicity (Section 3.3) |
| UC-014..UC-016 | Payment transaction boundaries (3.4, 3.5) |
| EVD-ARCH-DATA-001 | MySQL/InnoDB as authoritative persistence |
| Blueprint issue #40 | Concrete InnoDB transactional architecture delivered in A3 |
