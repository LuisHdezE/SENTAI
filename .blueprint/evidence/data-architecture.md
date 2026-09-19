# SENTAI – Data Architecture

**Artifact ID:** EVD-ARCH-DATA-001
**Blueprint Phase:** Architecture & Security Data (A3)
**Status:** READY_FOR_REVIEW
**Bases en:** EVD-ARCH-001 (Domain Model), EVD-ARCH-SEC-001 (Security Model), EVD-REQ-001 (Requirements & Domain)

---

## 1. Authoritative Transactional Database

### Decision

MySQL with the InnoDB storage engine is the **single authoritative transactional persistence** for all server-side SENTAI business state.

| Property | Value |
|---|---|
| Engine | MySQL |
| Storage Engine | InnoDB (all transactional business tables) |
| ACID Guarantees | Atomicity, Consistency, Isolation, Durability via InnoDB |
| SSOT | Server/backend is the sole source of truth for synchronized mobile state |
| Redis | **Excluded** (`redis=false`; no cache layer introduced in architecture) |

**Rationale:** InnoDB provides row-level locking, MVCC (multi-version concurrency control), fully transactional DDL-independent operations, and foreign key support, all of which are required to enforce the domain invariants identified in A1. Non-transactional storage engines (e.g., MyISAM) are prohibited for business tables.

---

## 2. Persistence Boundary Map

### 2.1 Server-Side Authoritative Persistence (MySQL/InnoDB)

All business state that constitutes legal system truth resides here. No client-side state supersedes server state.

| Module | Owned Persistence Domain | Notes |
|---|---|---|
| Identity & Access | Users, Roles, Permissions, Sessions | Foundation; no business dependencies |
| Master Data | Products, Customers, Warehouses, Zones, Locations | Shared reference; read by operational modules |
| Inventory / Warehouse Operations | InventoryItem quantities/states, ASNs, Receipts, Put-away records, Adjustments | Critical for invariant enforcement |
| Commercial Orders | CustomerOrders, Order Lines | Business intent |
| Fulfillment | Allocations, Picking records, Packing records, Packages | Operational execution |
| Shipping / Dispatch | Shipping Plans, Dispatch records | Physical exit |
| Finance | Financial Obligations, Payments, Payment Applications | Saldos y deuda |
| Audit / Compliance | Audit Events (append-only) | Cross-cutting; immutable evidence |

### 2.2 Mobile Local/Offline Persistence (KMP – Provisional Only)

Mobile local persistence (KMP-managed SQLite or equivalent) is **not an alternative business authority**.

| Property | Constraint |
|---|---|
| Authority | Provisional only until server reconciliation succeeds |
| Scope | Locally-queued operations and downloaded work context |
| State | May diverge from server until sync |
| Outcome | Server state wins; conflicts are registered, not silently resolved |
| Secrets | No business-critical state stored unsecured locally |

Mobile local persistence holds:
- Queued offline operations pending sync
- Downloaded read-only work context (e.g., picker task list)
- Provisional results pending server confirmation

It does **not** hold:
- Canonical inventory quantities
- Canonical financial obligation balances
- Authorization state beyond initially granted offline token

### 2.3 Transient / Cache State

No distributed cache (Redis) is introduced. If the application layer uses in-process request-scoped caching (e.g., within a single HTTP request lifecycle), such data is:
- Non-authoritative
- Never persisted between requests
- Never used as the basis for business decisions that bypass the database read inside the same transaction

### 2.4 Audit Persistence

Audit events are server-side, append-only records stored within MySQL/InnoDB in a dedicated audit store. Audit records are never deleted by application logic. Retention management is governed by policy (see `audit-architecture.md`).

---

## 3. Logical Persistence Ownership by Module

The following describes the logical tables/entities each module owns. Physical DDL is deferred to implementation. All constraints, indexes, and foreign keys will be defined in versioned migrations.

### 3.1 Identity & Access

Owns: `users`, `roles`, `permissions`, `role_permissions`, `user_roles`, `sessions` (or equivalent token store for mobile).

Key invariants:
- A user has at least one role or no active access.
- Roles aggregate capabilities/permissions.
- Sessions are revocable server-side.

### 3.2 Master Data

Owns: `products`, `customers`, `warehouses`, `zones`, `locations`.

Key invariants:
- Product, Customer, Warehouse, Zone, Location are shared reference data.
- Only `admin.masters.maintain` capability may mutate these.
- Locations belong to a Zone; Zones belong to a Warehouse.

### 3.3 Inventory / Warehouse Operations

Owns: `inventory_items` (Product x Location x Lot? x SerialNumber? x State), `asns`, `asn_lines`, `receipts`, `receipt_lines`, `putaway_tasks`, `inventory_adjustments`, `inventory_adjustment_lines`.

Key invariants (preserved from A1, BR-001 through BR-004):
- `Reserved <= OnHand` must be enforced by the database-aware transactional layer.
- Allocation does **not** reduce `OnHand`.
- Picking does **not** reduce `OnHand`.
- Packing does **not** reduce `OnHand`.
- Only confirmed physical Dispatch reduces `OnHand`.
- `Available` is not simply `OnHand - Reserved`; blocked/quarantine/damaged/expired states exclude inventory from commercial availability.
- `InventoryItem` granularity: Product, Location, Lot (when applicable), SerialNumber (when applicable), State.

### 3.4 Commercial Orders

Owns: `customer_orders`, `order_lines`.

Key invariants:
- An order has an aggregate lifecycle state.
- Order lines reference Product and Customer from Master Data.

### 3.5 Fulfillment

Owns: `allocations`, `picking_tasks`, `packing_tasks`, `packages`, `allocation_lines`.

Key invariants:
- An Allocation references an InventoryItem; it logically increments `Reserved` on that item.
- Picking and Packing update state within Fulfillment ownership; they do not reduce `OnHand`.
- A Package groups physically packed items before Dispatch.

### 3.6 Shipping / Dispatch

Owns: `shipping_plans`, `dispatch_records`, `dispatch_lines`.

Key invariants (critical, UC-013):
- A Dispatch transitions to `Completed` only when all mandatory effects are committed atomically (see `transactional-consistency.md`).
- Dispatch completion triggers `OnHand` decrement in Inventory and Financial Obligation creation/update in Finance, within the same atomic transaction.
- `Dispatch = Completed` without consistent Inventory and Finance effects is **forbidden by architecture**.

### 3.7 Finance

Owns: `financial_obligations`, `payments`, `payment_applications`.

Key invariants (preserved from A1, BR-005 through BR-009):
- One Financial Obligation = one business truth (AR/AP are views, not separate tables).
- Register Payment is an independent operation creating a `payments` record; it may leave a non-zero unapplied balance.
- Apply Payment creates `payment_applications` records and reduces both the payment's available balance and the obligation's outstanding balance.
- Obligation closes only when applied payments cover the total (BR-007).
- Double application of the same payment to the same obligation is prevented by idempotency and unique constraint (see `transactional-consistency.md`).

### 3.8 Audit / Compliance

Owns: `audit_events` (append-only, cross-module).

Key invariants:
- No application-layer DELETE on audit records.
- No UPDATE on committed audit records.
- Audit records may be retained in a physically separate audit schema/table group to support independent retention policies.

---

## 4. Cross-Module Persistence Interaction Principle

- Modules own their tables exclusively; no module queries another module's tables directly.
- Cross-module operations occur via Application Contracts (as established in A1).
- For operations requiring atomicity across module boundaries (e.g., Dispatch), the coordinating business transaction must span all affected persistence resources within a single InnoDB transaction.
- Module contracts must not be bypassed for the sake of performance at the cost of consistency.

---

## 5. Preservation of A1/A2 Decisions

This document does not alter:
- Module boundaries defined in EVD-ARCH-001.
- Aggregate candidates defined in EVD-ARCH-001.
- Critical invariants defined in EVD-ARCH-001 (section 4).
- Security model constraints from EVD-ARCH-SEC-001.
- `mobile_licensing=false`, `saas=false`, `multi_tenant=false`, `redis=false`.

---

## Traceability

| Requirement / Decision | A3 Data Architecture Mapping |
|:---|:---|
| FR-002..FR-005 | Inventory persistence model and InventoryItem granularity |
| FR-011..FR-013 | Dispatch persistence, OnHand decrement, Financial Obligation creation |
| FR-014..FR-018 | Finance persistence (Register Payment, Apply Payment, Obligation lifecycle) |
| FR-019, NFR-003 | Audit persistence (append-only, durable) |
| BR-001..BR-004 | Inventory invariant enforcement via InnoDB transactional boundaries |
| BR-005..BR-009 | Finance invariant enforcement |
| NFR-005 | Idempotency constraint support in persistence layer |
| NFR-007 | Offline provisional state; server SSOT reconciliation |
| NFR-012 | Correlation ID support in audit and operational tables |
| EVD-ARCH-001 section 6 | Transaction boundary principles formalized in transactional-consistency.md |
| EVD-ARCH-SEC-001 section 3 | Audit obligations and forbidden audit content |
| UC-013 | Dispatch atomicity requirement |
| UNRES-001..UNRES-005 | Preserved; no resolution attempted in A3 |
