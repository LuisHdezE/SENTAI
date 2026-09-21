# SENTAI API Backend

Laravel 13 backend for SENTAI, implemented as a Clean Architecture modular monolith.

## Runtime baseline

- PHP: `^8.3`
- Laravel Framework: `^13.17`
- Authoritative persistence: MySQL / InnoDB
- Redis: disabled by Product Truth
- Docker: disabled by Product Truth

## Local bootstrap

```bash
cd backend
composer install
cp .env.example .env
php artisan key:generate
composer test
composer test:architecture
composer lint
```

No business endpoint is implemented in I1. `/up` is Laravel's operational health endpoint and is outside the `/api/v1` business contract.
