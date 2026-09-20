# ADR-004: MySQL/InnoDB as Authoritative Transactional Persistence

**Status:** ACCEPTED
**Artifact ID:** EVD-ARCH-ADR-004
**Type:** architecture_decision_record
**Date:** 2026-09-19
**Authors:** Prepared by Antigravity; governed through PR review and explicit merge approval.
**Related Evidence:** EVD-ARCH-DATA-001, EVD-ARCH-TXN-001

---

## Context

SENTAI requires a server-side authoritative persistence layer capable of:

1. Enforcing complex domain invariants (e.g., `Reserved <= OnHand`, Payment double-application prevention, Dispatch atomicity).
2. Supporting concurrent access from multiple operators and mobile sync operations simultaneously.
3. Providing full ACID guarantees for multi-step business operations.
4. Integrating naturally with the selected backend framework (Laravel/PHP).
5. Supporting referential integrity and explicit schema management via migrations.
6. Remaining within the approved infrastructure constraints: no Redis, no Docker requirement at architecture stage, no distributed message broker.

A1 (EVD-ARCH-001) established that the server is the SSOT for all synchronized mobile state. A2 (EVD-ARCH-SEC-001) confirmed server-side session and authorization state. Both require a reliable, transactional persistence foundation.

---

## Decision

**MySQL with the InnoDB storage engine is the authoritative transactional persistence for all server-side SENTAI business state.**

- All transactional business tables must use the InnoDB engine.
- Non-transactional engines (e.g., MyISAM) are prohibited for business tables.
- Mobile local/offline persistence (KMP-managed SQLite or equivalent) is provisional only; it is not an alternative business authority.
- No distributed cache (Redis) is introduced at this architecture stage.

---

## Rationale

| Factor | Rationale |
|---|---|
| **ACID Guarantees** | InnoDB provides full atomicity, consistency, isolation, and durability; required for multi-step business transactions (Dispatch, Apply Payment, Allocation) |
| **Row-Level Locking** | InnoDB row-level locking supports concurrent operations without full table locks; critical for inventory reservation concurrency |
| **MVCC** | Multi-version concurrency control reduces reader/writer contention |
| **Foreign Key Support** | Enforces relational integrity at database level as defense-in-depth |
| **Laravel Ecosystem Alignment** | MySQL is well-supported by Laravel's Eloquent ORM and Migration system; the combination is a common and documented deployment pattern for the chosen framework |
| **Operational Simplicity** | A single authoritative relational database is appropriate for a Modular Monolith; distributed data stores are not warranted at this scale |
| **Existing Constraints** | `redis=false`, `saas=false`, `multi_tenant=false`; no additional infrastructure components needed |

---

## Consequences

### Positive
- Clear, single authoritative data store for all business state.
- Full transactional semantics available for all critical business operations.
- Migrations managed by versioned migration files; schema evolution is controlled.
- Framework-native integration reduces impedance mismatch.

### Constraints and Accepted Trade-offs
- Many MySQL DDL statements (e.g., `ALTER TABLE`, `DROP TABLE`, `CREATE TABLE`) cause **implicit commits**: any open transaction is committed before the DDL executes, and normal application `ROLLBACK` cannot undo the DDL change. Migration rollback must not assume ACID application transaction semantics for DDL. Risky migrations require forward recovery, explicit reverse migrations, or backup/restore strategies (documented in EVD-ARCH-TXN-001).
- InnoDB row locking requires consistent deterministic lock ordering to reduce deadlock probability; bounded whole-transaction retry remains required because deadlocks can still occur (documented in EVD-ARCH-TXN-001).
- Horizontal read scaling via replicas may be considered in a future operational phase but is not part of the current architecture scope.
- Full-text search and time-series capabilities are limited; if required in future, specialized tools may be considered at that time.

---

## Alternatives Considered

| Alternative | Reason Rejected |
|---|---|
| PostgreSQL | Not rejected on technical grounds; MySQL chosen for Laravel ecosystem alignment and existing infrastructure context |
| MySQL with MyISAM | No ACID guarantees; incompatible with domain invariant requirements |
| Separate MySQL instance per module | Premature distribution; cross-module atomic transactions would be impossible; Modular Monolith shares a single database |
| NoSQL (MongoDB, etc.) | NoSQL/document databases were not selected because SENTAI's approved relational domain model, cross-module consistency needs, FK/constraint expectations, Laravel/MySQL infrastructure context, and multi-row transactional requirements are better served by the selected MySQL/InnoDB architecture. |
| Redis as primary store | `redis=false`; Redis excluded from architecture |

---

## Related Decisions

- EVD-ARCH-001 (A1): Modular Monolith; shared database; module-owned table groups.
- EVD-ARCH-SEC-001 (A2): Server SSOT; mobile provisional only.
- EVD-ARCH-TXN-001 (A3): Concurrency strategy, isolation levels, lock ordering, DDL implicit commit behavior.
- Blueprint issue #40: Transactional consistency debt tracked in canonical Blueprint.

**Corrections Applied:** A3-CORRECTION-12 (Laravel ecosystem claim softened; DDL wording corrected to reflect implicit commit, not absolute non-transactional statement).
