# Peanut Admin Internal Starter

This is the fixed P0 internal starter used to verify that a new host can consume
versioned Peanut Admin packages. It is intentionally small and is not a public
project generator.

The generated project contains:

- a minimal ThinkPHP backend host with an external Module namespace;
- a Vue and Vite Admin Web host;
- local package snapshots at version `0.1.0`;
- complete local package snapshots including migrations and schemas;
- a schema-validated fictional `example.greeting` Module;
- two registered fictional Tenant Clients with independent sessions and cookies;
- a generic protected frontend transport for application-owned OpenAPI clients;
- build, type, unit, MySQL authentication, backend, and HTTP smoke checks.

Install and verify:

```bash
composer install --working-dir backend
pnpm install
php backend/tests/smoke.php
php backend/tests/auth-clients.php
pnpm typecheck
pnpm test
pnpm build
```

The authentication check requires MySQL 8 and the same connection variables used
by the repository checks. This starter does not define template variables, CRUD
generation, package publishing, source overwrite upgrades, or compatibility
promises. Those are separate post-P0 capabilities.
