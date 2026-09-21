# SENTAI — API Implementation I1 Foundation & Architecture Guard

**Artifact ID:** EVD-API-IMPL-001  
**Blueprint Phase:** API Implementation  
**Status:** READY_FOR_REVIEW  
**Base API Contract:** EVD-API-007  
**Architecture:** ADR-001, ADR-002, ADR-004, ADR-005, ADR-006

## Scope

I1 establishes the executable backend foundation before any of the 51 contracted business operations are implemented.

This increment intentionally contains no business endpoint. Its purpose is to make architectural drift difficult from the first implementation commit.

## Runtime baseline

- Backend root: `backend/`
- PHP contract: `^8.3`
- CI runtime: PHP `8.4.25`
- Laravel Framework contract: `^13.17`
- Reproducible lock baseline: Laravel Framework `v13.32.0`
- Persistence configuration: MySQL / InnoDB
- Redis: not introduced
- Docker: not introduced

Laravel 13 was selected because it is the current stable major at implementation start and is compatible with the project's approved Laravel direction. The exact dependency graph is committed in `backend/composer.lock`; CI performs `composer install` from that lockfile and does not resolve a floating dependency set.

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

## CI closure

Backend CI is read-only (`contents: read`) and validates pull requests that touch the backend.

Exact backend validation head: `08c45b368b625754365bcfb98fe917682b08adbf`  
GitHub Actions run: `35620801958`

Validated on Ubuntu 24.04 / PHP 8.4.25:

- Composer manifest + committed lock validation: **PASS**
- Ephemeral CI Laravel environment from `.env.example`: **PASS**
- Locked dependency install: **PASS**
- Full backend test suite: **35 passed / 36 assertions / 0 warnings**
- Architecture fitness suite: **34/34 passed / 34 assertions**
- Pint formatting check: **PASS**
- PHPUnit is configured with `failOnWarning=true` so warnings cannot silently pass future backend validation.

The operational health smoke uses Laravel's JSON health response and verifies exactly `{"status":"up"}`. No business API endpoint is introduced by I1.

## Reproducibility and security notes

- `backend/composer.lock` is committed.
- CI uses `composer install`, never a normal floating `composer update`.
- `.env` is not committed. CI creates an ephemeral copy from `.env.example` only for the job lifecycle.
- No credentials, production secrets, Redis dependency, Docker artifact, or speculative infrastructure was introduced.

## Blueprint check disposition

This evidence does **not** claim `api_implemented = PASS`.

The implementation-phase project checks remain PENDING until the 51 contracted operations, authentication/authorization, durable audit behavior, complete backend tests and full architecture implementation conformance are implemented and evidenced.

I1 proves only the implementation foundation and the executable architecture guard required to keep subsequent API increments conformant. It is therefore ready for human review while `api_implementation` remains `IN_PROGRESS` and `api_implemented` remains `PENDING`.
