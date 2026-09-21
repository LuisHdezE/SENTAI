# SENTAI — API Implementation I1 Foundation & Architecture Guard

**Artifact ID:** EVD-API-IMPL-001  
**Blueprint Phase:** API Implementation  
**Status:** IN_PROGRESS  
**Base API Contract:** EVD-API-007  
**Architecture:** ADR-001, ADR-002, ADR-004, ADR-005, ADR-006

## Scope

I1 establishes the executable backend foundation before any of the 51 contracted business operations are implemented.

This increment intentionally contains no business endpoint. Its purpose is to make architectural drift difficult from the first implementation commit.

## Runtime baseline

- Backend root: `backend/`
- PHP: `^8.3`
- Laravel Framework: `^13.17`
- Persistence configuration: MySQL / InnoDB
- Redis: not introduced
- Docker: not introduced

Laravel 13 was selected because it is the current stable major at implementation start and is compatible with the project's approved Laravel direction.

## Architecture layout

Each canonical module has four explicit layers:

- Domain
- Application
- Infrastructure
- Presentation

Modules: Identity, MasterData, Inventory, Orders, Fulfillment, Shipping, Finance, Audit.

The Laravel `app/` directory is the host/composition boundary. SENTAI business rules belong under `backend/src/Modules`.

## Executable architecture guard

I1 adds fitness tests that enforce:

1. Domain and Application do not depend on Laravel, Laravel packages, HTTP framework types, or the Laravel host namespace.
2. Domain cannot depend outward on Application, Infrastructure or Presentation.
3. Application cannot depend on Infrastructure or Presentation.
4. Presentation cannot directly depend on Infrastructure.
5. Infrastructure cannot depend on Presentation.
6. Cross-module dependencies must follow the ADR-002 topology.
7. Cross-module dependencies use only `Application\\Contracts` or `Domain\\Events` seams.
8. All eight canonical modules expose the four required layer directories.

## CI

`backend-ci.yml` runs Composer validation, backend tests, architecture fitness tests and Pint in pull requests that touch the backend.

During I1, if `composer.lock` does not yet exist, CI resolves the initial dependency set. A committed lockfile is required before the foundation is promoted as the reproducible baseline.

## Blueprint check disposition

This evidence does **not** claim `api_implemented = PASS`.

The implementation-phase checks remain PENDING until the contracted endpoints, authorization, durable audit, backend tests and full architecture implementation conformance are implemented and evidenced. I1 only establishes the foundation and the executable guard needed to keep subsequent increments conformant.
