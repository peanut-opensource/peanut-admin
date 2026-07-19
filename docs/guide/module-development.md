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

## 7. Compose An Atomic Command

An external Module owns its domain callable and, when needed, its outbox
schema. Peanut Admin provides the transaction, idempotency, and audit
primitives; it does not own the Module's domain tables or outbox table. Pass
one caller-owned PDO through the whole command so every state change shares
one commit boundary:

```php
$transactions = new PdoTransactionManager($pdo);
$idempotency = new PdoIdempotencyRepository($pdo);
$audit = new PdoAuditRepository($pdo);

$result = $transactions->run(function () use ($request, $moduleCommand, $outbox, $idempotency, $audit) {
    $lease = $idempotency->beginTenant(
        $request->tenantId,
        $request->memberId,
        $request->operationKey,
        $request->idempotencyKey,
        $request->requestHash,
        $request->idempotencyExpiresAt,
    );

    if ($lease->replayable()) {
        return [$lease->responseStatus, $lease->responseBody];
    }
    if (!$lease->acquiredForExecution()) {
        throw new RuntimeException('IDEMPOTENCY_REQUEST_PROCESSING');
    }

    $domainResult = $moduleCommand->execute($request);
    $audit->appendTenantMember(
        $request->context,
        $request->eventType,
        $request->operationKey,
        metadata: $domainResult->redactedAuditMetadata(),
        outcome: AuditOutcome::Success,
    );
    $outbox->append($domainResult->events());
    $idempotency->completeTenant(
        $lease->id,
        $domainResult->responseStatus(),
        $domainResult->safeResponseBody(),
    );

    return $domainResult->response();
});
```

The repository objects above all receive the same PDO. The application-owned
command and outbox adapter must retain that PDO as well; creating another
connection inside either callable breaks the atomicity guarantee.

The host must store only a safe, redacted terminal response. It must not store
credentials, secrets, SQL, stack traces, raw authorization input, or hidden
target existence. An expected denial may record a redacted `denied` audit and
a safe failed idempotency response; an unexpected exception rolls back the
domain, in-transaction audit, outbox, and completion together. The host owns
the policy for deciding which failures are safe to replay.

An expired `processing` record is not automatically taken over. The current
schema has no fencing token and cannot prove that an older executor stopped, so
the host returns `IDEMPOTENCY_REQUEST_PROCESSING` until a separately governed
cleanup or future fenced-recovery capability resolves the record.

Nested transaction calls use unique savepoints. A caught nested failure rolls
back only nested effects and leaves the outer command available to continue;
an uncaught failure rolls back the outer command. Cross-connection or
cross-database writes are outside this atomicity contract.

For deterministic failure testing, use
`PeanutAdmin\Testing\Operation\OperationAtomicityContractHarness`. The
Module supplies its callable, state probes, and exact expected success state;
the harness injects failures after idempotency acquisition, domain write,
audit, outbox, and completion, requires all four probes to remain unchanged,
then verifies one successful execution and the fixed checkpoint order.
