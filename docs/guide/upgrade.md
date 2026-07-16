# Upgrade

P0 supports a local, operator-controlled database upgrade. It does not download releases, replace application code, contact a license server, or perform remote fleet upgrades.

## Pre-Upgrade Checklist

1. Review the target Git commit and dependency lock changes.
2. Confirm all Module manifests compile and their dependency graph is acyclic.
3. Create and verify a database backup using the deployment's approved backup system.
4. Stop writes or place the deployment in a controlled maintenance window.
5. Run the repository checks against the target code.

Run the upgrade after deploying the target code:

```bash
./scripts/upgrade
```

The workflow applies migrations in this order:

```text
Kernel
-> data-permission package
-> Modules in manifest dependency order
```

Applied Module migration files are immutable. Each file is recorded in `pa_module_migration` with a SHA-256 checksum. A changed or missing applied file stops the upgrade before any Module installation state changes.

## Failure Behavior

- A manifest, dependency, or checksum failure stops before Module mutation.
- A migration failure records `MODULE_MIGRATION_FAILED` and leaves the Module installation failed closed.
- The workflow never pretends that an irreversible DDL change was automatically rolled back.
- Recovery uses a verified database backup and the matching previous application release.

Re-running after a successful upgrade returns `applied_module_migrations: 0` until new migration files are present.

## Schema Compatibility

Kernel and data-permission migrations use their own Phinx history tables. Module migration order and checksum ownership use `pa_module_migration`; they are not repeated for each tenant. A TenantModule enable hook may write tenant-scoped defaults but must not execute DDL.
