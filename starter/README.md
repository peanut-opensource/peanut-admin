# Peanut Admin Internal Starter

This is the fixed P0 internal starter used to verify that a new host can consume
versioned Peanut Admin packages. It is intentionally small and is not a public
project generator.

The generated project contains:

- a minimal ThinkPHP backend host;
- a Vue and Vite Admin Web host;
- local package snapshots at version `0.1.0`;
- a fictional `example.greeting` Module hook;
- build, type, unit, backend, and HTTP smoke checks.

Install and verify:

```bash
composer install --working-dir backend
pnpm install
php backend/tests/smoke.php
pnpm typecheck
pnpm test
pnpm build
```

This starter does not define template variables, CRUD generation, package
publishing, source overwrite upgrades, or compatibility promises. Those are
separate post-P0 capabilities.
