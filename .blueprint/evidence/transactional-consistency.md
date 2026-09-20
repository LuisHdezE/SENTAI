# SENTAI – Transactional Consistency, Concurrency & Idempotency Architecture

**Artifact ID:** EVD-ARCH-TXN-001
**Blueprint Phase:** Architecture & Security Data (A3)
**Status:** READY_FOR_REVIEW
**Bases en:** EVD-ARCH-001, EVD-ARCH-DATA-001, EVD-REQ-001
**Blueprint Debt Reference:** LuisHdezE/SoftwareDevelopmentBlueprint issue #40
**Corrections Applied:** A3-CORRECTION-1 (lock ordering), A3-CORRECTION-2 (dispatch reserved lifecycle), A3-CORRECTION-3 (idempotency concurrency), A3-CORRECTION-4 (payment application uniqueness), A3-CORRECTION-5 (MySQL RR precision), A3-CORRECTION-6 (DDL accuracy)

---

## 1. Foundational Principle

> A business operation requiring several dependent state mutations is atomic from the domain perspective: all mandatory effects commit, or none do.

For SENTAI, MySQL/InnoDB transactional guarantees are the mechanism that enforces this principle for server-side business state. `autocommit` mode is **not sufficient** for multi-step business operations. Explicit transaction boundaries must be used for all operations involving more than a single isolated mutation.

---

## 2. MySQL/InnoDB Concurrency Strategy

### 2.1 Row-Level Locking

InnoDB provides row-level locking. This allows high concurrency for operations touching different rows, but requires consistent deterministic lock ordering to reduce deadlock probability when multiple rows are involved. Because deadlocks may still occur and InnoDB may abort one transaction, bounded whole-transaction retry remains mandatory for transient deadlock failures.

### 2.2 Isolation Level

**Default isolation level: `REPEATABLE READ`** (InnoDB default).

InnoDB's REPEATABLE READ uses MVCC (multi-version concurrency control) for consistent non-locking reads: a transaction sees a snapshot of the database as of the moment its first read was executed, preventing dirty reads and non-repeatable reads for those reads.

**Important precision:**

- Consistent (non-locking) reads under REPEATABLE READ use snapshots and do not acquire row locks; they are not protected against concurrent writes to the rows they observe.
- Locking reads (`SELECT ... FOR UPDATE`, `SELECT ... LOCK IN SHARE MODE`) and range operations may use record locks, gap locks, or next-key locks depending on query shape, predicate, and index coverage.
- Gap locking and next-key locking behavior depends on whether the query uses a unique index or a range predicate.
- **Correctness for invariant-sensitive operations must not depend solely on the isolation level.** Snapshot reads alone are insufficient to prevent concurrent violations of domain invariants (e.g., `Reserved <= OnHand`, available payment balance).
- Explicit row/range locking and transactional validation against locked data are required wherever concurrent mutation can violate domain invariants (see Section 2.3).

Where a specific operation requires a different isolation level (e.g., READ COMMITTED for operations with no shared row contention), that may be justified and documented per operation. Isolation level changes must be supported by documented rationale before implementation.

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

### 2.4 Lock Ordering Principle (Corrected — A3-CORRECTION-1)

**Principle:** Consistent, deterministic lock acquisition order is the primary architectural mechanism to reduce deadlock probability. It does **not** eliminate deadlocks entirely; therefore bounded whole-transaction retry (Section 2.6) remains required.

#### 2.4.1 Ordering Rules

1. Each transactional use case defines a deterministic lock acquisition order for the resources it touches.
2. All code paths that can concurrently contend on the **same set of resource types** must acquire those resources in the **same order**.
3. Multiple rows within the same resource type (table) are locked in **ascending primary key order**.
4. If two operations share overlapping resource types, the shared ordering must be consistent across both.

#### 2.4.2 Canonical Lock Order for Overlapping Critical Operations

The following canonical ordering applies to operations that can contend with each other. No global table-ordering table is maintained because not all operations touch all tables; what matters is consistency within overlapping sets.

**Inventory allocation / reservation operations:**

Lock `inventory_items` before `allocations`. Any operation touching both must acquire `inventory_items` lock first.

```
inventory_items (PK ASC) → allocations (PK ASC) → audit_events (INSERT; no row lock needed)
```

**Confirm Dispatch (UC-013):**

Lock `dispatch_records` first (to assert operational control over the dispatch), then `inventory_items` in ascending PK order, then `financial_obligations` (if an existing obligation is being updated) in ascending PK order.

```
dispatch_records (the target dispatch, FOR UPDATE) → inventory_items (PK ASC, FOR UPDATE) → financial_obligations (PK ASC, FOR UPDATE, if applicable) → audit_events (INSERT)
```

Any other operation that must lock both `dispatch_records` and `inventory_items` must acquire them in this same order: Dispatch first, then Inventory.

**Apply Payment (UC-016):**

Lock `payments` first (to assert control over the payment being applied), then `financial_obligations` in ascending PK order.

```
payments (the target payment, FOR UPDATE) → financial_obligations (PK ASC, FOR UPDATE) → payment_applications (INSERT) → audit_events (INSERT)
```

Any other operation that must lock both `payments` and `financial_obligations` must acquire them in this same order: Payment first, then Obligations.

#### 2.4.3 Summary: No Conflicting Global Table Order

The previous single global ordering (`inventory_items → allocations → dispatch_records → financial_obligations → payments → payment_applications`) was internally inconsistent with actual operation boundaries (Confirm Dispatch locks Dispatch before Inventory; Apply Payment locks Payment before Obligations). That global ordering is **replaced** by the operation-scoped canonical orderings above.

The principle is: **within any overlapping resource set, the acquisition order is fixed and documented per operation group.** Deviations require documented architectural justification.

#### 2.4.4 Deadlock Acknowledgment

Deterministic lock ordering reduces—but does not eliminate—deadlock probability. InnoDB deadlock detection will roll back one of the involved transactions. Bounded retry (Section 2.6) must handle this case.

### 2.5 Transaction Duration Rule

Database transactions must remain **short**. The following must **not** be performed inside an open InnoDB transaction unless architecturally unavoidable and explicitly justified:

- Remote HTTP calls to external services
- External API requests (payment gateways, shipping carriers, etc.)
- User interaction waits
- Long-running CPU-intensive computation
- Unrelated file system operations
- Arbitrary sleeps
- Uncontrolled network I/O

**External Side Effect Policy (Corrected — A3-CORRECTION-9):**

External and remote side effects must be classified by their business criticality before implementation:

- **Non-critical external side effects** (e.g., sending a notification email): may be executed post-commit as best-effort. The business operation is committed; the external side effect is not transactional with it. Failure of a non-critical side effect must not require business transaction rollback.
- **Mandatory external side effects** (e.g., a call whose success or failure is required for business correctness): their consistency strategy must be explicitly designed before implementation. They must **not** be silently classified as best-effort. The architecture documents this seam; the exact mechanism is deferred.
- All code paths involving external side effects must document which category applies.

**Note:** Outbox pattern, saga orchestration, and message brokers are **not** current Product Truth and must not be introduced speculatively in this architecture.

### 2.6 Deadlock and Transient Failure Policy

Deadlocks and equivalent transient concurrency failures (e.g., lock wait timeout) may occur even in a correctly designed system. The following policy applies:

1. **Complete rollback:** A failed transaction must be rolled back completely. Partial state must never persist.
2. **Whole-transaction retry:** Retries operate on the entire business transaction. Resuming from the middle of a failed transaction is forbidden.
3. **Bounded retry:** Retries must be bounded. Concrete maximum retry counts are a deployment/configuration decision; the architecture requires the bound to be finite and configurable.
4. **Idempotency prerequisite for retry:** A transaction may only be safely retried if the operation is idempotent (see Section 5). Non-idempotent operations must not be blindly retried.
5. **Non-transient domain conflicts:** A business conflict (e.g., insufficient inventory, already-applied payment) is not a transient failure. It must surface as a domain error, not be swallowed by a retry loop.
6. **Retry-safe operations:** Retry is safe when an idempotency record ensures the operation produces the same committed result on repeated execution (see Section 5).

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
| **Locking / Concurrency Strategy** | `SELECT ... FOR UPDATE` on the target `inventory_items` row(s) first (PK ASC), then lock `allocations` rows if applicable. Lock order: `inventory_items → allocations` (see Section 2.4.2). |
| **Isolation Requirement** | REPEATABLE READ with explicit pessimistic row lock; correctness does not depend on snapshot read alone |
| **Retry Semantics** | Safe to retry if idempotency record present; the second attempt will detect the existing allocation and return the same result without duplicating |
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
| **Isolation Requirement** | REPEATABLE READ with explicit pessimistic row lock; correctness does not depend on snapshot read alone |
| **Retry Semantics** | Safe to retry with idempotency record; retry detects existing adjustment record |
| **Idempotency Relationship** | Adjustment carries idempotency key; duplicate submission returns existing result without re-applying delta |
| **Audit Relationship** | Audit event is **mandatory within the same transaction**. Adjustment without audit evidence must not commit. |
| **Verification Requirement** | Audit event must exist for every committed adjustment record; invariant check post-adjustment |

---

### 3.3 Confirm Dispatch (UC-013) — Critical

This is the highest-criticality transactional boundary in SENTAI.

| Field | Detail |
|---|---|
| **Use Case / Operation** | Confirm physical dispatch of packages (UC-013) |
| **Atomic Effects** | (1) Validate Dispatch record current state (must be in a valid pre-completion state, not already Completed or in a state that forbids confirmation); (2) Lock Dispatch record (`FOR UPDATE`); (3) Validate all included InventoryItem states; (4) Lock `inventory_items` rows in ascending PK order (`FOR UPDATE`); (5) Decrement `on_hand_qty` by dispatched quantity for each item; (6) **Release/consume the corresponding reserved quantities** consistent with dispatched amounts — see Dispatch Reserved Lifecycle note below; (7) Validate post-decrement invariants: `OnHand >= 0`, `Reserved <= OnHand`; (8) Transition Dispatch to `Completed`; (9) Create or update Financial Obligation where required (FR-013 / UC-013), locking existing obligations in PK ASC order if applicable; (10) Write mandatory audit event |
| **Transaction Required** | **YES – mandatory**. No partial commit allowed. `Dispatch = Completed` must never be observable without all mandatory effects (inventory decrement, reservation release, finance effect, audit) committed in the same transaction. |
| **Persistence Resources** | `dispatch_records`, `dispatch_lines`, `inventory_items`, `allocations`, `financial_obligations`, `audit_events` |
| **Domain Invariants** | `OnHand` decremented exactly once per dispatch line; `Reserved <= OnHand` after decrement and reservation release; `Reserved >= 0`; Financial Obligation created/updated per FR-013 / UC-013; Dispatch state machine: `Completed` only reachable through this transaction |
| **Concurrency Risk** | Concurrent allocation or another dispatch consuming the same inventory; concurrent dispatch of the same record (idempotency) |
| **Locking / Concurrency Strategy** | Canonical order (Section 2.4.2): (1) `SELECT ... FOR UPDATE` on `dispatch_records` first; (2) `SELECT ... FOR UPDATE` on `inventory_items` in ascending PK order; (3) lock `financial_obligations` in ascending PK order if an existing obligation is being updated. |
| **Isolation Requirement** | REPEATABLE READ with explicit pessimistic row locks; all validation occurs after locks acquired; correctness does not depend on snapshot read alone |
| **Retry Semantics** | Safe to retry with idempotency record; if Dispatch is already `Completed`, retry returns existing committed state without re-applying effects |
| **Idempotency Relationship** | A Dispatch confirmation with the same idempotency key that finds the Dispatch already `Completed` is a no-op; it must not decrement OnHand a second time or re-release reservations |
| **Audit Relationship** | Audit event is **mandatory within the same transaction**. Dispatch `Completed` without audit evidence must not be allowed to commit. |
| **Verification Requirement** | Integration test: concurrent dispatch of same record must produce exactly one `Completed` state; OnHand must decrease exactly once; Reserved must be reduced consistently; Financial Obligation must exist; audit event must exist |

**Explicit prohibition:**
`Dispatch = Completed` while Inventory decrement, reservation release, or mandatory Finance effect failed is **architecturally forbidden**. No code path may transition a Dispatch to `Completed` outside this atomic transaction.

#### Dispatch Reserved Lifecycle Note (A3-CORRECTION-2)

When a Dispatch is confirmed, inventory that was previously reserved for the fulfilled order must have its reservation reconciled consistently with the physical exit:

- `OnHand` decreases by the dispatched quantity per item line.
- The `reserved_qty` corresponding to the allocation(s) fulfilled by this dispatch must be released/consumed within the same transaction — the reservation is no longer needed once the physical goods have departed.
- After the transaction commits: `Reserved <= OnHand` and `Reserved >= 0` must both hold.
- If multiple allocation records participate in the dispatch (e.g., the order has multiple allocation lines), all relevant reservations must be reconciled within the same transaction.
- The dispatch must not leave "orphaned" reserved quantities that exceed the post-dispatch OnHand.
- The exact mechanism (decrementing `reserved_qty` on `inventory_items`, marking allocation records as fulfilled, or equivalent) is determined at implementation; the architecture requires that both `OnHand` and `Reserved` are adjusted atomically and consistently.

No new product lifecycle state is introduced; this is a consistency requirement on the existing reservation/inventory model.

---

### 3.4 Register Payment

| Field | Detail |
|---|---|
| **Use Case / Operation** | Register an incoming payment (UC-015, FR-014, BR-006) |
| **Atomic Effects** | (1) Validate actor capability (`finance.payment.register`); (2) Create `payments` record with full amount and zero applied amount; (3) Write audit event |
| **Transaction Required** | **YES** – payment creation and audit are coupled |
| **Persistence Resources** | `payments`, `audit_events` |
| **Domain Invariants** | Payment is independent of any obligation at registration time; unapplied balance = total amount at creation; BR-006 (independence of Register from Apply) |
| **Concurrency Risk** | Low; payment registration is a single-aggregate operation |
| **Locking / Concurrency Strategy** | No row lock required on existing rows; INSERT is atomic |
| **Isolation Requirement** | READ COMMITTED sufficient; no shared row contention |
| **Retry Semantics** | Safe to retry with idempotency record; duplicate registration returns existing payment without creating a second record |
| **Idempotency Relationship** | Payment carries idempotency key; duplicate submission detects existing payment and returns it |
| **Audit Relationship** | Audit event within same transaction |
| **Verification Requirement** | Duplicate registration test; payment balance = total amount after registration |

---

### 3.5 Apply Payment (Corrected — A3-CORRECTION-4)

| Field | Detail |
|---|---|
| **Use Case / Operation** | Apply a registered payment to one or more Financial Obligations (UC-016, FR-015, BR-007) |
| **Atomic Effects** | (1) Lock `payments` row (`FOR UPDATE`); (2) Validate available unapplied balance >= application amount; (3) Lock `financial_obligations` rows in ascending PK order (`FOR UPDATE`); (4) Validate each obligation is open and has outstanding balance; (5) Create `payment_applications` record(s); (6) Decrement payment's available balance; (7) Decrement each obligation's outstanding balance by the applied amount; (8) Close obligation if fully satisfied (BR-007); (9) Write audit event |
| **Transaction Required** | **YES** – multiple rows; race conditions on available balance must be prevented |
| **Persistence Resources** | `payments`, `financial_obligations`, `payment_applications`, `audit_events` |
| **Domain Invariants** | Available payment balance >= application amount; obligation cannot be over-applied; obligation closes only when fully satisfied (BR-007); the same logical Apply Payment command cannot commit its effects twice (enforced via idempotency, not via a unique constraint on `(payment_id, obligation_id)`) |
| **Concurrency Risk** | Concurrent applications against the same payment (double-spend); concurrent closure of the same obligation |
| **Locking / Concurrency Strategy** | Canonical order (Section 2.4.2): `SELECT ... FOR UPDATE` on `payments` row first; then `SELECT ... FOR UPDATE` on `financial_obligations` in ascending PK order. Duplicate-effect prevention is enforced via idempotency identity (see Section 5), not via a `UNIQUE(payment_id, obligation_id)` constraint — UC-016 permits multiple legitimate partial applications between the same payment and obligation over time. |
| **Isolation Requirement** | REPEATABLE READ with explicit pessimistic row locks; correctness does not depend on snapshot read alone |
| **Retry Semantics** | Safe to retry with idempotency record; duplicate application detected via existing idempotency record matching the logical Apply Payment command |
| **Idempotency Relationship** | The logical Apply Payment command carries an idempotency key; if a prior committed result exists for that key, return it without re-applying. A unique constraint may be applied to the idempotency identity of the logical application operation if justified, but **not** to `(payment_id, obligation_id)` globally. |
| **Audit Relationship** | Audit event within same transaction |
| **Verification Requirement** | Concurrent application test must not produce double-spend; obligation balance must be >= 0 after application; same payment may be legitimately applied to the same obligation multiple times in separate valid Apply Payment commands |

---

### 3.6 Offline Sync Mutation

| Field | Detail |
|---|---|
| **Use Case / Operation** | Apply an offline-queued mobile operation to server state (FR-020, FR-021, NFR-007, BR-010) |
| **Atomic Effects** | (1) Revalidate actor identity and current authorization against server state; (2) Validate idempotency key atomically (see Section 5 — concurrent duplicate detection); (3) Read and lock relevant server-side rows; (4) Validate all business invariants against current server state; (5) Apply mutation; (6) Write audit event; (7) Return success or structured conflict |
| **Transaction Required** | **YES** – authorization revalidation, invariant validation, mutation, and audit must be atomic |
| **Persistence Resources** | Depends on operation type (inventory, dispatch, etc.) plus `audit_events` |
| **Domain Invariants** | Server SSOT; invariants apply against server state at sync time, not at offline time; stale offline state does not override server state |
| **Concurrency Risk** | Concurrent syncs from same or different devices; same operation queued multiple times due to offline retry |
| **Locking / Concurrency Strategy** | Same strategy as the underlying business operation (allocation, dispatch, etc.). Idempotency record insertion must be atomic (see Section 5). Authorization check inside the transactional boundary. |
| **Isolation Requirement** | Same as underlying operation |
| **Retry Semantics** | Idempotency key provided by mobile client; server detects already-applied operations and returns the committed result without re-applying |
| **Idempotency Relationship** | NFR-005; every offline operation must carry a stable idempotency key generated at operation-creation time on the mobile client |
| **Audit Relationship** | Sync attempts (successful, conflicted, and rejected) are auditable events |
| **Verification Requirement** | Replay test: same offline sync payload submitted twice must produce one committed state; conflict test: sync against stale server state must produce conflict record, not silent commit |

---

## 4. Schema Migration Strategy

### 4.1 Version-Controlled Migrations

All schema changes must be expressed as discrete, version-controlled migration files. The migration tool will be determined at implementation (likely Laravel Migrations or equivalent). No schema change is applied to production outside the migration system.

### 4.2 Forward Migration Philosophy

Migrations are designed to apply forward. The canonical path is forward evolution. Every migration must:
- Be idempotent where feasible (safe to run on already-applied state)
- Apply exactly once per environment
- Be committed to version control before deployment

### 4.3 Rollback Limitations (Corrected — A3-CORRECTION-6)

MySQL DDL statement behavior is not uniform across all operations:

- Many MySQL DDL statements (e.g., `ALTER TABLE`, `DROP TABLE`, `CREATE TABLE`) cause **implicit commits**. When a DDL statement executes, any open transaction is implicitly committed before the DDL runs; the DDL itself is then committed separately.
- Normal application `ROLLBACK` cannot be relied upon to undo schema changes caused by DDL statements. A `ROLLBACK` issued after a DDL statement will roll back only any DML that followed the DDL — not the DDL itself.
- Some InnoDB/MySQL DDL operations (particularly under MySQL 8.0+) support atomic DDL semantics at the MySQL server level (e.g., crash recovery of DDL operations), but this is a distinct concern from ordinary user-transaction ACID rollback semantics. Atomic DDL at the server level does **not** mean DDL can be rolled back by application `ROLLBACK`.
- Migration rollback must not assume ACID application transaction semantics for DDL statements.
- For risky migrations, the recovery strategy must be: forward recovery (explicit reverse migration), explicit backup/restore, or an operations-approved rollback procedure — not a `ROLLBACK` command.

Therefore:

- Schema rollbacks are treated as new forward migrations, not literal reversions.
- A "rollback" migration (if needed) must be a separate, explicit migration that undoes the structural change via a new DDL statement.
- Rollback plans must be prepared before any risky migration is applied to production.

### 4.4 Production Data Preservation

- Migrations must **not** irreversibly destroy production data unless explicitly authorized by a governance decision.
- Destructive operations (DROP COLUMN, DROP TABLE, truncation) are prohibited without explicit governance approval and a data backup verification step.
- Column removal must follow a deprecation pattern: (1) stop writing to column, (2) deploy, (3) verify no reads remain, (4) remove column in a subsequent migration.

### 4.5 Compatibility Considerations

- Migrations that modify shared reference tables (products, customers) must consider read-heavy access patterns.
- Index creation on large tables should use `ALGORITHM=INPLACE` or equivalent non-locking strategy where supported and verified.
- Foreign key additions must be validated against existing data before deployment.

### 4.6 Backup Dependency for Risky Changes

Production deployments involving structural changes to high-traffic or large tables require:
- A verified backup taken immediately before migration application.
- A tested rollback plan (as a forward-rollback migration).
- A maintenance window or zero-downtime strategy, depending on migration scope.

### 4.7 Constraints, Indexes, and Foreign Keys

- Foreign keys are defined in migrations for all relationships that must be enforced at the database level.
- Unique constraints are defined in migrations for idempotency identity fields and business-unique identifiers.
- Indexes are defined in migrations, not added ad hoc in production.
- Not-null constraints reflect domain mandatory fields.

---

## 5. Idempotency Architecture (Corrected — A3-CORRECTION-3)

### 5.1 Principle

Transaction retry and client/request retry must not duplicate business effects. This preserves NFR-005 and all offline/idempotency requirements established in A1.

Idempotency must be treated as an **atomic persistence concern**, not merely a pre-check. Two concurrent requests carrying the same idempotency key must not both be able to observe "no existing record" and both proceed to commit business effects.

### 5.2 Idempotency Identity and Atomic Enforcement

The architecture requires:

1. **Server-side persisted idempotency record or equivalent durable constraint.** The server must persist an idempotency record in durable storage (MySQL/InnoDB) that is associated with the committed business result.
2. **Scoped identity.** The idempotency identity is scoped to the logical operation type and caller/business context. A conceptual uniqueness constraint of the form `UNIQUE(operation_scope, idempotency_key)` — or a domain-equivalent durable unique marker — must prevent two independent commits for the same logical identity. The exact table name, column names, and Laravel implementation are deferred to implementation.
3. **Atomic duplicate key detection.** Idempotency checking must be enforced via a durable unique constraint at the database level, not only a pre-check read. A pre-check read alone (read → if absent → proceed) is not concurrency-safe: two concurrent requests can both observe absence and both proceed. The unique constraint ensures that only one of them can commit the idempotency record; the other will receive a duplicate key error and must return the previously committed result.
4. **Idempotency/result association persisted within the same transaction.** The idempotency record and the business result it represents must be committed atomically in the same InnoDB transaction. If the business transaction rolls back, the idempotency record also rolls back.
5. **Concurrent attempts cannot both commit.** Two concurrent requests with the same logical idempotency identity cannot both commit their business effects. One will commit; the other will detect the committed state and return it.

### 5.3 Idempotency Identifier Lifecycle

1. **Generation:** The idempotency key is generated by the caller (API client, mobile operation queue) at the moment the operation is first created. Keys must be stable across retries of the same logical operation.
2. **Scope:** The key is scoped to a specific operation type and business context (e.g., allocation for order X, dispatch of dispatch record Y). Cross-operation reuse of the same key is a client error.
3. **Storage:** The server persists the idempotency record alongside the result of the committed operation, within the same atomic transaction.
4. **Atomic detection:** On receiving a request, the idempotency record insertion (or equivalent unique constraint evaluation) is the atomic gate — not merely a pre-check read.
5. **Response:** If a prior committed result exists (detected via the unique constraint or a locked read of the existing record), return it without re-executing the business operation.
6. **Expiry:** Idempotency records are not permanent. Idempotency record retention is an operational/API consistency policy; it must cover the maximum supported retry/replay window; exact duration is deferred to API Contract / Operations configuration; audit retention policy applies only to audit evidence. They must persist long enough to cover realistic retry windows.

### 5.4 Idempotency Outcomes

| Scenario | Required Behavior |
|---|---|
| Same key + committed result exists, same logical operation | Return the previously committed result without re-executing |
| Same key + different request identity/payload | Idempotency conflict; return a structured conflict response |
| Concurrent attempt while first is in progress | Only one can commit the idempotency record; the other receives a conflict and must treat the in-progress operation as the authority |
| No prior result | Proceed normally; commit idempotency record atomically with business result |

**Note:** Exact HTTP response codes and headers for in-progress concurrent attempts belong to API Contract design, not architecture. The architecture requires that two concurrent commits cannot both succeed for the same idempotency identity.

### 5.5 Transaction Retry vs. Client Retry

| Scenario | Handling |
|---|---|
| **DB deadlock / lock timeout** | Rollback completely; retry entire business transaction (bounded); idempotency record prevents duplicate effect on retry |
| **API client retry (timeout, 5xx)** | Server checks idempotency identity; if operation already committed, return existing result |
| **Offline operation replay** | Server detects idempotency key in committed state; returns result without re-applying |
| **Domain conflict** | Return structured domain error (e.g., insufficient inventory); must not be retried blindly |
| **Duplicate financial application** | Idempotency record for the Apply Payment command; second attempt returns existing committed result without re-applying |
| **Duplicate dispatch confirmation** | Idempotency record + Dispatch state check; `Completed` dispatch returns existing result |

### 5.6 Forbidden Retry Patterns

- Do **not** retry a business transaction that failed due to a domain conflict (e.g., `Reserved > OnHand` would result, obligation already closed). These are deterministic failures, not transient failures.
- Do **not** resume a failed transaction from the middle. Always rollback and restart from the beginning.
- Do **not** allow unbounded retries. The retry bound must be finite.

---

## 6. Preservation of A1/A2 Decisions

This document does not alter:
- Module boundaries, aggregate candidates, or critical invariants from EVD-ARCH-001.
- Security model constraints from EVD-ARCH-SEC-001.
- UNRES-001 through UNRES-005 remain unresolved.
- UNRES-006 remains RESOLVED from A2 and is preserved unchanged.
- UNRES-007 was introduced in A3 and remains unresolved.
- `redis=false`, `mobile_licensing=false`, `saas=false`, `multi_tenant=false`.
- No outbox, saga, or message broker introduced.

---

## Traceability

| Requirement / Decision | Transactional Architecture Mapping |
|:---|:---|
| BR-001 | `Reserved <= OnHand` enforced via pessimistic lock in Allocation and Dispatch transactions |
| BR-002 | OnHand decrement only on confirmed Dispatch; enforced by Dispatch atomicity (3.3) |
| BR-003, BR-004 | Inventory eligibility validated inside locked transaction |
| BR-005 | Single obligation truth (Accounts Receivable internally vs Accounts Payable externally) |
| BR-006 | Register Payment independence from Apply Payment (separate transactions 3.4, 3.5) |
| BR-007 | Obligation closure condition validated inside Apply Payment transaction |
| BR-008 | Idempotent retry semantics for sync operations |
| BR-010 | Server SSOT enforced in Offline Sync transaction (3.6) |
| NFR-005 | Idempotency identity lifecycle and atomic enforcement (Section 5) |
| NFR-007 | Conflict generation on stale sync; not silent commit |
| NFR-012 | Correlation ID flows through all transaction boundaries |
| UC-013 | Dispatch atomicity including reservation release (Section 3.3) |
| UC-015..UC-016 | Payment transaction boundaries (3.4, 3.5); partial repeated application permitted (UC-014 is Consultar Cartera) |
| EVD-ARCH-DATA-001 | MySQL/InnoDB as authoritative persistence |
| Blueprint issue #40 | Concrete InnoDB transactional architecture delivered in A3 |
| A3-CORRECTION-1 | Lock ordering reconciled; no conflicting global table order |
| A3-CORRECTION-2 | Dispatch Reserved lifecycle explicit |
| A3-CORRECTION-3 | Idempotency atomic persistence; concurrent duplicate prevention |
| A3-CORRECTION-4 | UNIQUE(payment_id, obligation_id) removed; idempotency-based protection |
| A3-CORRECTION-5 | MySQL REPEATABLE READ precision: MVCC snapshot vs. locking reads |
| A3-CORRECTION-6 | MySQL DDL implicit commit behavior; rollback limitations |
