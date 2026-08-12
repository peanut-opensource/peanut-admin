# MT01 Generated Host Fixture Contract

## Fixed Stage

```text
task: MT01-GENERATED-HOST-01
state: fixed-candidate remediation
prerequisite: 05d4c64106c60d14e4510a1c95e77334bbffdc40
registry_identity: PENDING_ALPHA5
owner: MT01 Generated Host fixture owner
integration_owner: MT01-GENERATED-HOST-INTEGRATION-001
database: peanut_admin_mt01_generated_host_<run_id>
```

This slice proves that a project generated with the fictional example Module
removed can host one product-neutral external Module without copying or
weakening the qualified Runtime. It reuses the existing R02 public Host
composition and real-MySQL behavior; it does not create a second authorization,
Tenant, idempotency, audit, outbox or transaction engine.

## Exact Implementation Write Set And Owners

After the Generator parameterization integration result is committed, one
fixture owner changes only these eight paths:

- `scripts/test-mt01-generated-host` — isolated MySQL entry point and unique
  resource owner;
- `tests/mt01/generated-host/run.php` — one consolidated generated-Host fixture;
- `tests/mt01/generated-host/fixture/module.json` — external Module manifest;
- `tests/mt01/generated-host/fixture/CreateFixtureRecord.php` —
  `fixture.record` migration owner;
- `tests/mt01/generated-host/fixture/FixtureRecordHost.php` — external Host
  composition through public Core APIs;
- this contract;
- `docs/content-status.json`;
- `docs/status/index.md`.

Schema and migration ownership belong only to Module key `fixture.record`.
The test database must use the fixed prefix above plus a generated run ID;
cleanup may drop only that exact database. The implementation may not change
Generator source/tests, Runtime packages, starter source, package manifests or
locks, release workflows, production application code, CAP evidence or MT02.

## One Integration Group

`MT01-GENERATED-HOST-INTEGRATION-001` runs once against MySQL 8.4 after static
review, exact write-set inspection, PHP 8.3 lint and `git diff --check`:

```bash
./scripts/test-mt01-generated-host
```

The group must prove all of the following on one generated tree:

1. Generation uses `--example-module remove`; no fictional example route,
   namespace, table, Module manifest or migration owner remains.
2. The external `fixture.record` Module mounts from the fixture namespace,
   registers its API prefix, manifest and `OwnedMigration`, and installs into a
   blank isolated MySQL database without using another checkout, `vendor` tree
   or database.
3. Trusted Tenant/Client context reaches the public R02 Host. Wrong Client,
   cross-Tenant target, disabled Module, missing functional permission and
   missing/denied typed-target Data Provider all return the existing stable
   fail-closed problems before the domain handler runs.
4. One positive command commits the domain row, Tenant audit event, outbox row
   and idempotency completion through the same PDO boundary. Exact replay does
   not re-run domain/outbox; changed payload with the same key conflicts.
5. Domain, audit/outbox and idempotency-completion failure injection each leave
   no partial domain, audit, outbox or completion state. Existing R02
   invariants and response codes are asserted, not reimplemented.
6. Unmount removes the external route/config/manifest and reverses only
   `fixture.record`-owned schema. A Host smoke still passes afterward, and no
   example or fixture owner residue remains.

## Stop Line

A first failure receives one read-only diagnosis and one static repair batch;
only this failed group may run once more. A second failure blocks the slice.
The result does not claim a production installer, Admin Web login/Tenant
selector, package publication, final immutable Generator identity, complete
MT01, `PA-DCS-ADOPT-01` nomination or MT02 authorization. Those remain separate
contracts and Gates.

## Fixed-Candidate Remediation

The first candidate was blocked before completing the integration assertions:
the initial invocation had no MySQL listener, the configured MySQL 8.4 retry
exposed direct construction of Phinx migrations, and the repaired clean-tree
retry exposed denial requests whose request IDs differed from their trusted
contexts. The latter correctly returned `REQUEST_CONTEXT_MISMATCH` before data
authorization. These are fixture defects, not authorization or isolation
failures.

One fixed candidate based on the prerequisite above may change only the five
fixture implementation paths already listed by this contract. It must use the
public Phinx manager path and bind each denial request to its trusted context's
request ID without changing the expected stable denial codes or weakening any
pre-write assertion. After static review and a clean commit, the integration
owner runs `MT01-GENERATED-HOST-INTEGRATION-001` once. Any failure blocks this
fixed candidate; no further retry is authorized by this remediation.
