# Module Development

A Module is a reusable capability owner. It owns its schema, migrations, repositories, rules, protected resources, permissions, target types, menus, frontend contribution, and public contracts.

Use the fictional modules under `backend/app/Modules/Example` as executable references. Do not copy product-specific code into the foundation.

## 1. Choose A Stable Key

Module keys use lower-case dot-separated segments such as `example.work-item`. The host combines the key with its declared `ModuleHostLayout`; the key itself does not choose a repository path or PHP namespace. Validate the reference host mapping with:

```bash
./scripts/module-key example.work-item
```

An external host provides its own roots without changing the key:

```bash
PA_MODULE_NAMESPACE_ROOT='Acme\Admin\Modules' \
PA_MODULE_BACKEND_ROOT='backend/app/Modules' \
PA_MODULE_FRONTEND_ROOT='frontend/src/modules' \
./scripts/module-key example.work-item
```

The same `ModuleHostLayout` instance must be supplied to the registry compiler and boundary checker. The boundary checker also receives every managed database prefix, including `pa_` and the host's business prefix, so an undeclared or cross-Module table reference fails closed.

The compiler also receives the complete registered Client key list. Every menu contribution must target one or more registered Clients; a typo or undeclared Client fails compilation instead of creating an unreachable or accidentally shared menu.

## 2. Create `module.json`

The manifest must pass the versioned JSON Schema. A minimal capability declares a provider, version, Kernel constraint, owned tables, public contracts, and tenant behavior. Dependencies refer to Module keys and SemVer constraints.

```json
{
  "schema_version": 1,
  "key": "example.work-item",
  "name": "Example Work Item",
  "description": "Fictional work item capability",
  "version": "1.0.0",
  "kernel_constraint": "^1.0",
  "license": "Apache-2.0",
  "backend": {
    "provider": "PeanutAdmin\\App\\Modules\\Example\\WorkItem\\ModuleProvider"
  },
  "frontend": {},
  "database": { "owned_tables": [] },
  "contracts": { "exports": [], "events": [] },
  "tenant": { "enableable": true, "requires": [] }
}
```

Run:

```bash
./scripts/check-module-manifests
```

## 3. Own Migrations And Tables

Every Module migration implements `OwnedMigration`, declares the Module key, lists owned tables, and states whether it is reversible. Applied files are immutable. New schema changes use new timestamped migration files.

Owned table names are lower-case SQL identifiers up to 64 characters. They are not required to use `pa_`; an application should use a stable application prefix. A host must pass all Kernel and data-permission table names as reserved tables when compiling manifests, so a business Module cannot claim a framework-owned table.

Module dependencies determine deployment migration order. Schema migration runs once per deployment, never once per tenant.

## 4. Declare Authorization

Register permissions, protected resources, operations, target cardinality, and target types in controlled catalog files. A `shared_master` resource also declares a scope provider. Do not invent operation permissions in controllers.

Each operation target relation is a structured object, not a bare type string. It declares `target_resource_key`, `target_role`, `input_mode` (`explicit`, `derived`, or `either`), and an optional `policy_selection_permission`. This preserves source/destination semantics through HTTP, jobs, the Kernel adapter, and the data-permission engine.

The owner implements query, target, create, resolver, and catalog-provider contracts as needed. Its `ModuleProvider` implements `DataPermissionModuleProvider` to register those implementations with the host runtime. Other Modules call exported contracts and never query the owner's private tables or the Kernel's authorization tables.

## 5. Contribute Admin Web Routes

Use `defineAdminModule()` with build-time imports. Each route stays below `/app/`, names its Module and functional permissions, and disposes tenant state on switch. The API menu can select only a route name already present in this build-time registry.

## 6. Verify The Contract

```bash
PEANUT_INTEGRATION=1 php vendor/bin/phpunit \
  examples/module-contract/ExampleModuleContractTest.php
```

The tutorial fixture intentionally covers one Tenant with several Project and Queue targets. It demonstrates cross-Module calls through contracts, a unified shared reference, one-target writes, multi-target reads, policy publication, and fail-closed category checks.
