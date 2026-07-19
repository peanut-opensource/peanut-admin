# P1-B04 Minimal Reference-Code Module Contract

## Status

```text
state: implementation-ready
task: P1-B04
runtime_prerequisite_commit: 3ab9a7ddf7488a9cc941b4c4f8fa9ba25470a9ad
runtime_prerequisite_tree: b306be362e8e69c12b2d398e6543a582800ab6ec
qualified_downstream_lock: 0ab02a9b735ba9f4c23509cb366b9bf04039ebf8
module_key: peanut.reference-code
schema_owner: peanut.reference-code
runtime_test_owner: RUNTIME-REFERENCE-CODE-001
dependency_change: no third-party dependency
expected_pure_branch_operations: 75 P0 + 9 P1 = 84
expected_after_b03_operations: 75 P0 + 15 P1 = 90
qualification: candidate only
```

This contract is the complete execution input for P1-B04. The four-line
summary in the aggregate readiness plan is not sufficient for implementation
and must not be interpreted at runtime-edit time.

The implementation branch starts from the exact R02 commit above. The single
integrator serializes B03 first and then integrates B04 migration, manifest,
OpenAPI, generated artifacts, locks, and final evidence onto the resulting
tree. B04 never changes the qualified downstream lock.

## Objective And Non-Goals

B04 provides reusable code-set infrastructure for small administrative
reference values that do not deserve an application-owned aggregate. External
hosts consume first-party PHP and Web packages and provide a thin Host Module
adapter; they do not copy reference-host source.

B04 does not provide:

- product catalogs, domain identifiers, units, prices, tax rules, lifecycle
  policy, inventory states, or any application-specific category;
- hierarchy, parent/child codes, arbitrary workflow, approval, translation,
  import/export, bulk mutation, drag ordering, or a universal CRUD engine;
- deployment-global entries, cross-Tenant sharing, typed-target scope, or a
  fallback when an owner Module is unavailable;
- package publication, a release, production sizing, or consumption approval.

The historical `example.reference` Module remains a fictional shared-master
scope fixture. Its tables, contracts, Provider, routes, and manifest are not
renamed, reused, or modified by B04.

## Package, Host, And Set Ownership

The reusable PHP package is `peanut-admin/reference-code` with namespace
`PeanutAdmin\ReferenceCode\`. The reusable Web package is
`@peanut-admin/reference-code`. Neither is published by this task.

The Host Module key is exactly `peanut.reference-code`. Its provider remains in
the configured Host namespace and delegates all catalog, persistence, and
application behavior to the PHP package. The reference host adapter is
`PeanutAdmin\App\Modules\Peanut\ReferenceCode\ModuleProvider`.

The Module manifest schema adds one optional backend resource:

```json
{
  "backend": {
    "reference_code_sets": "Resources/reference-code-sets.json"
  }
}
```

Each trusted resource is an array of objects with exactly:

```text
key: <declaring-module-key>.<local-key>, maximum 160 characters
name: non-empty display name, maximum 160 characters
description: non-empty text, maximum 500 characters
metadata_schema: absent or a JSON Schema draft 2020-12 object
```

`key` must start with the declaring Module key plus `.`. The catalog rejects a
missing file, invalid JSON, duplicate key, duplicate owner, owner mismatch,
unsupported schema, unknown Module, or a schema that permits nested objects,
arrays, executable content, or more than 32 properties. Code sets cannot be
created, renamed, or deleted by a Tenant API.

A set is available only when its owner Module installation is active and that
Module is enabled and effective for the current Tenant. The
`peanut.reference-code` Module itself must also be active and enabled. Missing,
retired, failed, disabled, or ineffective Modules fail closed; the API never
serves stale definitions from a previous manifest.

## Schema And Migration Contract

B04 has one additive migration:

```text
20260719040101_create_reference_code_entries.php
```

It implements `OwnedMigration`, reports module key
`peanut.reference-code`, and owns only `pa_reference_code_entry`. It alters no
P0, B03, or example table. The migration is reversible only for an empty clean
install database; operational rollback keeps the table and runs old code.

`pa_reference_code_entry` contains exactly:

```text
id BIGINT UNSIGNED primary key
tenant_id BIGINT UNSIGNED not null, foreign key pa_tenant(id) RESTRICT
set_key VARCHAR(160) ASCII BINARY not null
code VARCHAR(64) ASCII BINARY not null
label VARCHAR(160) not null
metadata_json JSON not null
status VARCHAR(16) ASCII BINARY not null: active|disabled|archived
effective_from DATETIME(3) null
effective_until DATETIME(3) null
sort_order INT not null default 0
revision BIGINT UNSIGNED not null default 1
archived_at DATETIME(3) null
created_by_member_id BIGINT UNSIGNED not null, foreign key pa_tenant_member(id) RESTRICT
updated_by_member_id BIGINT UNSIGNED not null, foreign key pa_tenant_member(id) RESTRICT
created_at DATETIME(3) not null
updated_at DATETIME(3) not null
unique (tenant_id, set_key, code)
index (tenant_id, set_key, status, sort_order, code)
index (tenant_id, set_key, effective_from, effective_until)
```

`code` is an immutable lower-case ASCII slug matching
`^[a-z][a-z0-9]*(?:-[a-z0-9]+)*$`. Metadata is a maximum 8 KiB canonical JSON
object with at most 32 top-level scalar or null values and must also satisfy
the owner set's optional trusted schema. Empty metadata is `{}`.

Effective intervals use `[effective_from, effective_until)`. A null start is
unbounded in the past and a null end is unbounded in the future. When both are
present, end must be later than start. `archived` is terminal and sets
`archived_at`; no API returns an archived entry as an option. Rows are never
physically deleted. Revision starts at 1 and increments by one for every
accepted patch.

The migration and manifest catalog are idempotent. A second upgrade on the same
tree applies zero migrations and produces the same catalog digest. Old code
ignores the new Module ledger row and table.

## Query And Mutation Semantics

All repositories receive Tenant identity only from trusted `TenantContext` and
include `tenant_id` in every select, unique check, update, and count. A route or
body Tenant value is rejected. A code or set belonging to another Tenant is
indistinguishable from absence.

Entry lists have fixed ordering `sort_order ASC, code ASC` and stable cursor or
page behavior. The options query accepts an explicit UTC `as_of`; when absent,
the controller captures one UTC instant for the request. Options include only
`active` entries whose interval contains that instant. Disabled and archived
entries remain visible in the administrative list when the requested status
filter permits them.

Create binds immutable `tenant_id`, `set_key`, and `code`. Patch may change
only `label`, `metadata`, `status`, `effective_from`, `effective_until`, and
`sort_order`. An archived row rejects every later patch. Two concurrent
patches with one ETag yield one success and one `412`; there is no last-write
wins path.

Writes use the R02 operation host and R01 same-PDO transaction for mutation and
redacted audit. B04 does not declare `Idempotency-Key`: a repeated create is the
same unique `(tenant_id, set_key, code)` conflict and returns `409`; a repeated
patch uses the old ETag and returns `412`. No partial value or audit row remains
after an injected failure.

## API Contract

B04 adds exactly five Tenant-audience P1 Runtime operations:

| Method and path | Operation ID | Permission | Behavior |
| --- | --- | --- | --- |
| `GET /api/v1/reference-code-sets` | `listReferenceCodeSets` | `peanut.reference-code.read` | list trusted sets from active Tenant Modules |
| `GET /api/v1/reference-code-sets/{set_key}/entries` | `listReferenceCodeEntries` | `peanut.reference-code.read` | administrative list with status/search/page filters |
| `GET /api/v1/reference-code-sets/{set_key}/options` | `listReferenceCodeOptions` | `peanut.reference-code.use` | effective options as of one UTC instant |
| `POST /api/v1/reference-code-sets/{set_key}/entries` | `createReferenceCodeEntry` | `peanut.reference-code.manage` | create one immutable code identity |
| `PATCH /api/v1/reference-code-sets/{set_key}/entries/{code}` | `updateReferenceCodeEntry` | `peanut.reference-code.manage` | conditionally update mutable fields |

Every operation declares `x-module-key: peanut.reference-code`, Tenant
audience, and R02 data authorization mode `none`. Functional permission and
Module availability remain mandatory. No operation trusts a body-supplied
Module owner or Tenant.

GET entry and option responses include a collection ETag. POST requires
`If-None-Match: *`. PATCH requires a strong `If-Match` and returns the new
strong ETag. Lists accept only documented filters; unknown fields, invalid UTC,
oversized search, unsupported sort, or malformed metadata are `422`.

Stable errors are:

```text
401 unauthenticated
403 permission denied
404 unknown set, unavailable owner Module, unknown code, or hidden Tenant row
409 duplicate code
412 stale ETag
422 invalid code, metadata, state transition, interval, filter, or payload
428 missing precondition
503 Module deployment unavailable
500 generic redacted infrastructure failure
```

Problem Details and audit never expose SQL, stack traces, private paths,
cross-Tenant existence, hidden entry data, Provider classes, or unvalidated
metadata. Audit fields are limited to set key, code, changed field names,
previous/new status, revision, and redacted actor/request evidence.

## Tenant Web Contract

`@peanut-admin/reference-code` exports the `/app/reference-codes` Tenant Module
contribution. The page loads only when `peanut.reference-code` is enabled and
the user has read permission.

The page provides a set selector, administrative `all` and effective `options`
views, status and bounded search filters, deterministic table ordering, create
and edit dialogs, visible ETag conflict recovery, and no hierarchy or drag
tree. Set ownership and unavailability are explicit. Code becomes read-only
after creation; archived entries have no edit action.

Tenant switch or Module disposal clears set catalog, rows, options, filters,
forms, ETags, errors, and pending response visibility. Permission denial blocks
the page chunk before fetch. Desktop `1440x900` and mobile `390x844` flows have
no horizontal overflow, overlapping controls, inaccessible labels, or hidden
conflict actions.

## Exact Implementation File Whitelist

After this contract commit, the B04 implementation commit may add or modify
only these files. Any additional file requires a separate contract amendment
before editing.

```text
composer.json
composer.lock
backend/composer.json
deptrac.yaml
phpunit.xml
pnpm-lock.yaml
README.md
packages/php/kernel/resources/schemas/module-manifest.schema.json
packages/php/kernel/tests/Unit/Module/ModuleRegistryCompilerTest.php
packages/php/reference-code/LICENSE
packages/php/reference-code/composer.json
packages/php/reference-code/src/Package.php
packages/php/reference-code/src/Application/ReferenceCodeAdminService.php
packages/php/reference-code/src/Application/ReferenceCodeException.php
packages/php/reference-code/src/Application/ReferenceCodeQueryService.php
packages/php/reference-code/src/Catalog/ReferenceCodeSet.php
packages/php/reference-code/src/Catalog/ReferenceCodeSetLoader.php
packages/php/reference-code/src/Catalog/ReferenceCodeSetRegistry.php
packages/php/reference-code/src/Database/Schema.php
packages/php/reference-code/src/Model/ReferenceCodeEntry.php
packages/php/reference-code/src/Persistence/PdoReferenceCodeRepository.php
packages/php/reference-code/tests/Unit/Catalog/ReferenceCodeSetLoaderTest.php
packages/php/reference-code/tests/Unit/Model/ReferenceCodeEntryTest.php
packages/php/reference-code/tests/Integration/Application/ReferenceCodeAdminServiceTest.php
packages/php/reference-code/tests/Integration/Application/ReferenceCodeQueryServiceTest.php
packages/php/reference-code/tests/Integration/Support/ReferenceCodeDatabaseTestCase.php
packages/php/reference-code/tests/Integration/Schema/ReferenceCodeMigrationRunner.php
packages/php/reference-code/tests/Integration/Schema/ReferenceCodeMigrationTest.php
packages/php/reference-code/tests/Security/ReferenceCodeIsolationTest.php
backend/app/Modules/Peanut/ReferenceCode/module.json
backend/app/Modules/Peanut/ReferenceCode/ModuleProvider.php
backend/app/Modules/Peanut/ReferenceCode/Database/Migrations/20260719040101_create_reference_code_entries.php
backend/app/Modules/Peanut/ReferenceCode/Resources/menus.json
backend/app/Modules/Peanut/ReferenceCode/Resources/permissions.json
backend/app/Modules/Peanut/ReferenceCode/Resources/protected-resources.json
backend/app/Modules/Peanut/ReferenceCode/Resources/reference-code-sets.json
backend/app/Modules/Example/Target/module.json
backend/app/Modules/Example/Target/Resources/reference-code-sets.json
backend/app/controller/api/v1/ReferenceCodeController.php
backend/app/reference/ReferenceCodeRuntimeFactory.php
backend/app/command/InstallProductProfileApplier.php
backend/app/command/UpgradeWorkflow.php
backend/config/modules.php
backend/tests/Architecture/ModuleManifestValidationTest.php
backend/tests/Contract/OpenApiArtifactTest.php
backend/tests/Http/ReferenceCodeApiTest.php
backend/tests/Integration/ReferenceCodeModuleIntegrationTest.php
backend/tests/Security/ReferenceCodeSecurityTest.php
backend/tests/Upgrade/ReferenceCodeUpgradeTest.php
backend/tests/Install/InstallWorkflowTest.php
backend/tests/Upgrade/UpgradeWorkflowTest.php
backend/tests/Install/ProductProfileTest.php
backend/tests/Install/InstallWorkflowIntegrationTest.php
backend/tests/Upgrade/UpgradeWorkflowIntegrationTest.php
profiles/reference-admin.json
packages/web/reference-code/LICENSE
packages/web/reference-code/package.json
packages/web/reference-code/tsconfig.json
packages/web/reference-code/src/contracts.ts
packages/web/reference-code/src/index.ts
packages/web/reference-code/src/runtime.ts
packages/web/reference-code/src/ReferenceCodePage.vue
packages/web/reference-code/tests/contracts.spec.ts
packages/web/reference-code/tests/page.spec.ts
frontend/package.json
frontend/src/app/routes.ts
frontend/src/modules/peanut-reference-code/index.ts
frontend/src/modules/peanut-reference-code/routes.ts
frontend/tests/e2e/reference-code.e2e.ts
frontend/tests/e2e/full-stack.e2e.ts
frontend/tests/fixtures/api.ts
frontend/tests/fixtures/full-stack-setup.php
frontend/tests/fixtures/full-stack.ts
frontend/tests/fixtures/full-stack-vite.config.ts
docs/api/openapi.yaml
docs/api/schemas/reference-code.yaml
backend/route/openapi-generated.php
packages/web/admin-core/src/generated/api.d.ts
docs/status/runtime-operation-coverage.json
docs/api/index.md
docs/decisions/dependencies/index.md
docs/decisions/dependencies/p1-b04-lock-evidence.json
docs/reference/packages/reference-code.md
docs/guide/module-development.md
docs/content-status.json
docs/status/index.md
docs/status/p1-b04-minimal-reference-code-contract.md
scripts/check-openapi
scripts/check
scripts/check-workspace
scripts/create-internal-starter
scripts/verify-doc-examples
scripts/verify-internal-starter
scripts/test-unit
scripts/test-integration
scripts/test-security
starter/backend/composer.json
starter/backend/composer.lock
starter/backend/config/modules.php
starter/backend/src/Modules/Example/Greeting/module.json
starter/backend/src/Modules/Example/Greeting/Resources/reference-code-sets.json
starter/backend/src/Modules/Peanut/ReferenceCode/module.json
starter/backend/src/Modules/Peanut/ReferenceCode/ModuleProvider.php
starter/backend/src/Modules/Peanut/ReferenceCode/Database/Migrations/20260719040101_create_reference_code_entries.php
starter/backend/src/Modules/Peanut/ReferenceCode/Resources/menus.json
starter/backend/src/Modules/Peanut/ReferenceCode/Resources/permissions.json
starter/backend/src/Modules/Peanut/ReferenceCode/Resources/protected-resources.json
starter/backend/src/Modules/Peanut/ReferenceCode/Resources/reference-code-sets.json
starter/backend/tests/reference-code.php
starter/.env.example
starter/README.md
starter/package.json
starter/pnpm-lock.yaml
starter/frontend/package.json
starter/frontend/src/app/modules.ts
starter/frontend/src/modules/peanut-reference-code.ts
starter/frontend/tests/reference-code.spec.ts
tests/recovery/RecoveryAcceptanceTest.php
tests/security/g07-evidence.json
```

The implementation must not edit `example.reference`, a B03 source file on the
pure branch, R01/R02 primitives, W03 files, the qualified lock, a product
repository, a parent-repository Patch, release metadata, or an unlisted
generated artifact.

## Test Ownership And Acceptance

`RUNTIME-REFERENCE-CODE-001` owns all five operations. Tests are written
failing before source implementation and must prove:

- trusted manifest set ownership, duplicate/owner/schema rejection, owner
  Module disablement, catalog digest stability, and no API-defined set;
- one-table ownership, constraints, migration order, clean install,
  idempotent upgrade, and no P0/B03/example table change;
- two Tenant isolation, trusted-context-only Tenant scope, non-enumerating set
  and code failures, and owner Module availability;
- immutable code, metadata limits/schema, exact interval boundaries, fixed
  ordering, effective options, disabled visibility, archived terminal state,
  and no physical delete;
- create precondition, duplicate conflict, patch precondition, stale ETag, two
  connection concurrency, revision increments, same-PDO audit, and injected
  rollback;
- separate read/use/manage permissions and redacted errors/audit;
- page permission and Module guards, creation/edit/archive behavior, explicit
  `412` reload, Tenant-switch cleanup, desktop/mobile layout, and real backend
  flow;
- old commit `0ab02a9b735ba9f4c23509cb366b9bf04039ebf8` can use the upgraded database
  for health, login, P0 APIs, workspace, and the qualified external-host path;
  rollback restores a pre-upgrade backup and does not run destructive down
  migration.

The isolated namespace is fixed:

```text
compose_project: peanut-admin-p1-b04
mysql_port: 33394
cache_port: 36394
backend_port: 38094
frontend_port: 35194
browser_backend_port: 38194
browser_frontend_port: 35294
database: peanut_admin_p1_b04_reference_code_test
```

Focused package, Host, HTTP, Web, upgrade, and security tests run first. Final
acceptance on the pure B04 branch requires generated artifacts to be clean,
`75 P0 + 9 P1 = 84` operations, four security JUnit groups with zero skips,
desktop/mobile E2E, clean install, upgrade, old-code compatibility, recovery,
unchanged P0 performance, starter consumption, architecture, dependency,
license, secret, documentation, `./scripts/check`, and `git diff --check`.

After B03 is integrated, the single integrator regenerates the combined tree;
the expected count is `75 P0 + 15 P1 = 90`. The pure-branch count is never
copied into the integrated artifacts.

## Integration And Stop Line

B04 contract and implementation are separate commits based on the fixed R02
tree. B03 and B04 contracts may be designed in parallel. Migration inventory,
manifest schema, root package locks, OpenAPI, generated route/type artifacts,
Runtime coverage, starter changes, and integration are serialized: B03 first,
B04 second. Conflicts are resolved only by the single integrator and are
verified from the resulting tree.

Completion makes B04 only an unqualified P1 candidate. It does not move
`0ab02a9b735ba9f4c23509cb366b9bf04039ebf8`, authorize a downstream consumer,
publish a package, create a tag or release, claim production readiness, start
Q01, or add application-specific business logic to Peanut.
