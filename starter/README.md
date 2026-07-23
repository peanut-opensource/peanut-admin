# Peanut Admin Internal Starter

This fixed internal starter verifies that a clean host can consume versioned
Peanut Admin packages. It is intentionally small and is not a public project
generator.

The generated project contains:

- a minimal ThinkPHP backend host with an external Module namespace;
- a Vue and Vite Admin Web host;
- local package snapshots at version `0.1.0`;
- complete local package snapshots including migrations and schemas;
- a schema-validated fictional `example.greeting` Module;
- a host-owned `peanut.settings` Module backed by `peanut-admin/settings`;
- a fictional typed setting definition with repeatable synchronization and
  default resolution evidence;
- an `@peanut-admin/settings` Tenant contribution composed through its package
  root;
- a host-owned `peanut.reference-codes` Module and
  `@peanut-admin/reference-codes` Tenant contribution with no committed
  application code sets or values;
- two registered fictional Tenant Clients with independent sessions and cookies;
- a generic protected frontend transport for application-owned OpenAPI clients;
- build, type, unit, MySQL authentication, backend, and HTTP smoke checks.

Install and verify:

```bash
composer install --working-dir backend
pnpm install
php backend/tests/smoke.php
php backend/tests/auth-clients.php
php backend/tests/settings.php
php backend/tests/reference-codes.php
pnpm typecheck
pnpm test
pnpm build
```

The authentication, Settings, and Reference Codes checks require MySQL 8 and the same connection
variables used by the repository checks. Secret configuration is intentionally
blank in `.env.example`; a consuming host must supply key material outside the
generated source before enabling secret definitions. This starter does not
define template variables, CRUD generation, package publishing, source overwrite
upgrades, or compatibility promises.
