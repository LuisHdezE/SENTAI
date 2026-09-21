# SENTAI backend core

This tree implements ADR-001 Clean Architecture + Modular Monolith.

- `Modules/*/Domain`: pure business model and invariants, framework-independent.
- `Modules/*/Application`: use-case orchestration and ports, framework-independent.
- `Modules/*/Infrastructure`: MySQL/Eloquent and other concrete adapters.
- `Modules/*/Presentation`: HTTP/API adapters, request/response mapping and authorization integration.
- `Shared`: cross-cutting primitives that are genuinely shared and do not collapse module ownership.

The Laravel `app/` directory is the host/composition boundary, not the home of SENTAI business rules.
