# Canonical modules

The canonical module set from ADR-002 is:

1. Identity
2. MasterData
3. Inventory
4. Orders
5. Fulfillment
6. Shipping
7. Finance
8. Audit

Cross-module dependencies are constrained by the architecture fitness tests. Infrastructure and Presentation are never valid cross-module seams.
