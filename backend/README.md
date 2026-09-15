# Qismat Backend

Laravel 12 / PHP 8.2+ API application for Qismat.

This directory contains the bootable Qismat Laravel application, Sanctum token authentication, versioned API routes, domain models, migrations, seed data and API tests. Vendor dependencies and secrets are never committed.

Target production: cPanel + SSH + MySQL/MariaDB.

## API baseline

- `/api/v1/health`
- `/api/v1/auth/*`
- `/api/v1/profile`
- `/api/v1/matches`
- `/api/v1/interests`
- `/api/v1/conversations`

Authentication will use Laravel Sanctum tokens for mobile/API clients.

## Local setup

```bash
cp .env.example .env
composer install
php artisan key:generate
php artisan migrate --seed
php artisan serve
```

The default environment targets MySQL/MariaDB. The automated test suite uses an in-memory SQLite database:

```bash
composer test
```
