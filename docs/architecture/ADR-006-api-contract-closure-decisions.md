# ADR-006 — API Contract Closure Decisions

**Status:** READY_FOR_REVIEW  
**Date:** 2026-09-21  
**Scope:** API Contract Design closure  
**Extends:** EVD-ARCH-SEC-001, EVD-ARCH-AUDIT-001, EVD-ARCH-TXN-001  
**Preserves:** EVD-REQ-001, EVD-UI-SCOPE-001, ADR-001..005

## Context

The C1-C3 API Contract evidence left a small set of implementation-significant gaps: Allocation authorization/triggering, the server contract used to download mobile offline work, six missing canonical audit events, and explicit transaction coupling for the affected mutations.

This ADR resolves only the decisions required to make the initial `/api/v1/` contract implementation-safe. It does not broaden product scope beyond approved Requirements and Interface Scope.

## Decision 1 — Allocation authorization

A new canonical capability is added:

`fulfillment.allocate`

It authorizes the explicit inventory-allocation command defined by FR-006 / UC-009 and is assigned to **ACT-003 Supervisor / Jefe de almacén**.

For the initial v1 API:

- `API-FUL-001` is a Backoffice operation executed by ACT-003.
- Authentication is the existing authenticated backend-managed Web session.
- Required capability is `fulfillment.allocate`.
- The operation remains server-authoritative and must enforce BR-001, BR-002, BR-003 and the A3 locking/idempotency rules.
- The HTTP command triggers server-side allocation logic; the caller does not gain permission to bypass eligibility or select invalid stock.

### System trigger disposition

UC-009 names `Sistema / Supervisor` as possible actors, but the automatic/manual interaction was previously UNRES-003. For initial v1, **automatic System-triggered allocation is deferred**. The v1 implementation MUST NOT fabricate a service account, service token, hidden HTTP credential, wildcard capability, implicit Administrator bypass, or an unaudited internal authorization path.

A later automatic trigger may invoke the same application use case internally only after a separate approved architecture/API decision defines its trigger semantics and authorization context. This disposition resolves UNRES-003 for the initial v1 contract without deleting the future System possibility from Product Truth.

## Decision 2 — Mobile offline work feed

FR-020 / UC-019 requires operational work to be downloaded before loss of connectivity. The initial API therefore includes:

`API-MOB-001 — GET /api/v1/mobile/work-items`

Purpose: provide ACT-002 with a server-authoritative, downloadable projection of operational work that may be cached locally for degraded/offline execution.

Authorization rules:

- Mobile access credential required.
- No new broad capability is created.
- Request access requires ACT-002 to hold at least one of the existing mobile warehouse capabilities.
- Each returned work item is filtered server-side according to the capability required by its work type.
- Canonical capability mapping remains:
  - reception -> `warehouse.receive`
  - put-away -> `warehouse.putaway`
  - picking -> `warehouse.picking`
  - packing -> `warehouse.packing`
- A work item for an operation the actor is not currently authorized to perform MUST NOT be returned.
- Downloaded authorization is provisional. `/api/v1/sync` reauthenticates and reauthorizes every submitted operation against current server state.

The feed is read-only and does not itself apply business mutations. It does not create `sync.execute`, `conflicts.read`, wildcard capabilities, or a second source of truth.

## Decision 3 — Canonical audit catalog extension

The following events are added to the canonical durable audit catalog:

| Event | Operation | Actor | Coupling |
|---|---|---|---|
| `inventory.move.completed` | Inventory Move | ACT-003 | same business transaction |
| `order.created` | Customer Order Create | ACT-001 | same business transaction |
| `order.confirmed` | Commercial Order Confirm | ACT-005 | same business transaction |
| `fulfillment.picking.completed` | Picking Confirm | ACT-002 | same business transaction |
| `fulfillment.packing.completed` | Packing Confirm | ACT-002 | same business transaction |
| `shipping.plan.created` | Shipping Plan Create | ACT-004 | same business transaction |

Minimum event context follows EVD-ARCH-AUDIT-001 mandatory fields and correlation rules. Operation-specific references must identify the affected aggregate/resource and relevant before/after or line/package references without recording secrets or unnecessary PII.

A mutation covered by one of these events MUST roll back if its mandatory audit event cannot be committed.

## Decision 4 — Transaction coupling for previously TBD operations

The following API mutations require explicit InnoDB transactions:

| Contract ID | Atomic intent |
|---|---|
| `API-ORD-002` | create Order + lines + `order.created` audit |
| `API-ORD-003` | validate/transition Order + `order.confirmed` audit |
| `API-FUL-002` | apply Picking confirmation/state effects + `fulfillment.picking.completed` audit; OnHand is not decremented |
| `API-FUL-003` | create/update package state + `fulfillment.packing.completed` audit; OnHand is not decremented |
| `API-SHP-001` | create Shipping Plan + assignments + `shipping.plan.created` audit |
| `API-INV-005` | move inventory representation between locations + `inventory.move.completed` audit |

The existing A3 rules remain authoritative: short transactions, invariant-sensitive validation under lock where required, deterministic lock ordering for overlapping resources, bounded whole-transaction retry for transient deadlocks, and persisted idempotency for retry-safe business commands.

## Decision 5 — Explicit deferred contract items

The following are **not** part of the initial v1 endpoint implementation and do not authorize invention during coding:

1. `customer.order.read` / customer order tracking endpoint (UNRES-001): deferred until Product Truth defines tracking/read semantics.
2. `commercial.customer.maintain`: capability remains canonical, but mutation endpoints are deferred until maintained commercial fields and invariants are defined.
3. Separate persistent conflict query such as `GET /sync/conflicts`: deferred. `/sync` returns operation conflict outcomes inline; no `conflicts.read` capability exists.
4. Automatic System-triggered allocation: deferred as stated in Decision 1.
5. ASN creation/approval workflow (UNRES-002): Receipt continues to require a valid ASN; this contract does not invent who creates or approves it.
6. Exact audit retention durations (UNRES-007): remains an Operations/legal policy decision.

## Consequences

- Canonical capability count becomes **23** for the initial v1 architecture.
- `API-FUL-001` no longer has a permission gap.
- The six C3 audit catalog gaps are resolved by explicit Architecture Truth rather than by reusing semantically incorrect events.
- Mobile offline execution has a defined server download boundary without weakening server SSOT.
- Deferred Product Truth gaps remain visible but no longer masquerade as missing endpoints that implementation should guess.
- No existing inventory, finance, dispatch, ownership, authentication, or offline synchronization invariant is weakened.

## Rejected alternatives

- Reusing `inventory.adjust` for Allocation: rejected because it grants a different business capability.
- Creating `allocation.manage`, `sync.execute` or `conflicts.read`: rejected because these were not approved capabilities.
- Giving ACT-007 a global bypass: rejected by EVD-ARCH-SEC-001.
- Creating a system service token solely to close UNRES-003: rejected as speculative security architecture.
- Mapping inventory move to `inventory.adjustment.applied`: rejected because the event semantics are different.
- Treating downloaded mobile work as authoritative: rejected by BR-010 and the offline server-SSOT architecture.
