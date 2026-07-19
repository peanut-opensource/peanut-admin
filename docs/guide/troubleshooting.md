# Troubleshooting

## Installer Reports Environment Invalid

Run:

```bash
php -v
php -m
composer --version
composer install
```

Use PHP 8.3 and Composer 2.10.2, and confirm `pdo_mysql` is loaded. The full
repository gate also requires pnpm 11.13.0. Do not bypass or relax the preflight
and workspace checks when a local tool version differs.

## Installer Cannot Connect To MySQL

Check the service and environment without printing secrets:

```bash
docker compose ps mysql
```

The CLI reads `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, and `DB_PASSWORD`. It expects the database to exist and does not grant database privileges.

## Install Already Completed

This is a safety stop after a platform operator exists. Use `--allow-existing` only when you intend an idempotent verification. It will not replace the current owner credential.

## Migration Checksum Mismatch

Do not edit the ledger or restore the changed migration file from memory. Compare the deployed commit with the approved release. Applied migration files are immutable; corrections require a new migration.

## Module Is Unavailable

Check all three layers:

1. `pa_module_installation.status` is `active`.
2. The current tenant has an effective `pa_tenant_module` record.
3. The member has the required functional permission and data policy.

Hiding a menu does not repair an inactive Module or missing permission.

## Health Is Degraded

A degraded report currently means the cache probe is down while database and application checks are up. Requests may continue, but cache-backed performance and revision behavior need operator attention. The CLI returns non-zero.

## Operation Is Missing From The API

Only operations present in the current OpenAPI document and classified in the
Runtime coverage ledger are implemented. A P1 candidate remains unavailable to
qualified downstream consumers until a fixed-commit aggregate review approves
it. Do not call an unused fallback controller or infer availability from a
planned name. Add the operation schema, concrete handler, authorization
metadata, classification, generated artifacts, and tests in one scoped change.

## 403, 404, 412, And 429

- `403`: functional or data authorization denied the known operation.
- `404`: existence is intentionally not disclosed or the object is unavailable in scope.
- `412`: reload the current representation and repeat with the new ETag after user review.
- `429`: honor `Retry-After`; do not create an unbounded retry loop.
