# Qismat Backend

Laravel 12 / PHP 8.2+ API application for Qismat.

This directory currently contains the Qismat application overlay (domain models, API routes, migrations/schema and environment contract). The generated Laravel framework skeleton will be added in the next backend bootstrap commit; vendor dependencies and secrets are never committed.

Target production: cPanel + SSH + MySQL/MariaDB.

## API baseline

- `/api/v1/health`
- `/api/v1/auth/*`
- `/api/v1/profile`
- `/api/v1/matches`
- `/api/v1/interests`
- `/api/v1/conversations`

Authentication will use Laravel Sanctum tokens for mobile/API clients.
