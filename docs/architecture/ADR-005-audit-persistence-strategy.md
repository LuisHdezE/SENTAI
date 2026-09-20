# ADR-005: Audit Persistence Strategy

**Status:** ACCEPTED
**Artifact ID:** EVD-ARCH-ADR-005
**Type:** architecture_decision_record
**Date:** 2026-09-19
**Authors:** Prepared by Antigravity; governed through PR review and explicit merge approval.
**Related Evidence:** EVD-ARCH-AUDIT-001, EVD-ARCH-TXN-001, EVD-ARCH-SEC-001

---

## Context

A2 (EVD-ARCH-SEC-001, section 4) established that certain categories of security and business events require durable audit evidence. A3 must define the persistence mechanism, the relationship between audit records and business transactions, and the tamper-resistance model.

Key requirements:
- FR-019: durable audit of business-relevant events.
- NFR-003: audit records must be durable and resistant to unauthorized alteration.
- NFR-012: correlation identifiers linking operations to audit events.
- UNRES-006 (resolved): `audit.global.read` capability required to access global audit data.

The threat model (EVD-ARCH-THREAT-001) identified audit-log tampering as a threat requiring mitigation at the persistence layer.

---

## Decision

**Audit events are persisted as append-only records within MySQL/InnoDB in a dedicated audit table group, co-located with operational data in the same database instance.**

Mandatory audit events (as cataloged in EVD-ARCH-AUDIT-001) must be written **within the same InnoDB transaction** as the business operation they document. This ensures that a committed business state is never observable without its mandatory audit evidence.

Non-mandatory audit events (e.g., authentication events that do not participate in a business transaction) may be written post-commit but must use durable, synchronous writes (not fire-and-forget).

---

## Rationale

| Factor | Rationale |
|---|---|
| **Transaction Coupling** | Critical business events (Dispatch, Adjustment, Apply Payment) require that audit evidence commits atomically with the mutation; if the business TX rolls back, the audit event also rolls back |
| **Simplicity** | Co-locating audit in the same MySQL instance avoids distributed consistency problems; no separate audit service or message broker required |
| **Durability** | InnoDB's WAL (redo log) provides durability on commit; audit records survive crashes after commit |
| **Append-Only Model** | INSERT-only policy enforced at application layer; no UPDATE or DELETE via application code; database GRANT restrictions for DBA operations |
| **No Redis / No Broker** | `redis=false`; no message broker in Product Truth; distributed audit pipeline is not warranted |
| **Access Control** | `audit.global.read` capability (UNRES-006 resolution) enforced at application authorization layer |

---

## Consequences

### Positive
- Mandatory audit events cannot be silently lost: business TX rollback also rolls back audit record.
- Single database simplifies operational management.
- Append-only enforcement prevents application-layer tampering.
- Correlation IDs provide end-to-end traceability.

### Constraints and Accepted Trade-offs
- Audit table growth must be managed; INSERT-only tables require a retention/archival strategy (documented in EVD-ARCH-AUDIT-001).
- Database-admin-level access bypasses application INSERT-only policy; this is a residual risk documented in the threat model. Mitigation via database GRANT restrictions on audit tables is required; additional physical hardening mechanisms (e.g., write-once storage, cryptographic chaining) may be added if later required by regulatory input.
- Physical tamper-resistance mechanisms beyond GRANT-level access control are not introduced at this stage; they are deferred to a future hardening phase pending regulatory evidence.
- High-volume audit event rates could create write contention; this is a known trade-off of the co-located, INSERT-only model. Mitigation strategies (e.g., table partitioning, indexing strategy) are deferred to implementation.

---

## Alternatives Considered

| Alternative | Reason Rejected |
|---|---|
| Separate audit database / service | Introduces distributed consistency problem; mandatory audit coupling with TX becomes impossible without 2PC |
| Write to log files | Not durable by default; requires log aggregation infrastructure not in Product Truth |
| Async message queue (post-TX) | Mandatory audit events cannot afford async gap; business TX commits without guaranteed audit delivery |
| Cryptographic chaining (blockchain-like) | Not required by current Product Truth; adds complexity; deferred until regulatory input |

---

## Related Decisions

- EVD-ARCH-SEC-001 (A2): Audit obligations and forbidden content.
- EVD-ARCH-DATA-001 (A3): MySQL/InnoDB as authoritative persistence.
- EVD-ARCH-TXN-001 (A3): Mandatory audit within business transaction.
- EVD-ARCH-AUDIT-001 (A3): Full audit catalog and retention policy.

**Corrections Applied:** A3-CORRECTION-13 (tablespace prescription removed; batching assumption removed; DBA-only hardening broadened to permit other physical hardening mechanisms).
