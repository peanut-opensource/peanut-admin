# MT01 Generator Parameterization Integration Contract

## Fixed Stage

```text
task: MT01-GENERATOR-INTEGRATION-001
state: waits for parameterization implementation PR to merge green
contract_input: 852e5dca4e333a023a5ec3b7ee52abe46384a115
implementation_candidate: PENDING_IMPLEMENTATION_MERGE
registry_identity: PENDING_ALPHA5
owner: MT01 Generator integration owner
```

This stage owns the one consolidated executable acceptance round for the
Generator parameterization slice. It does not own package publication, Registry
consumer probes, empty-database installation, Runtime isolation/atomicity or an
Admin Web browser smoke. Those remain separate MT01 stages and are not inferred
from repository PR CI.

## Exact Integration Write Set

After the implementation PR merges with every declared check successful, the
integration owner creates one commit from that exact `dev` result and changes
only:

- `tools/project-generator/source-baseline.json` to reseal the controlled
  Generator content at the implementation commit;
- `docs/status/mt01-generator-parameterization-contract.md` to record the
  implementation and integration identities/results;
- `docs/status/index.md` to mark only this slice integrated.

No Generator source/test, starter, Runtime, manifest, lock, workflow, package,
migration, schema, application or Registry file may change in this stage. If
the candidate needs a source or fixture repair, the integration stage stops and
returns a finding to a new implementation commit before any failed group is
re-run.

## One Consolidated Round

Immediately before the integration commit, after static review, exact write-set
inspection and `git diff --check`, the integration owner runs exactly once:

```bash
php tests/project-generator/run.php
PEANUT_PHP83=/absolute/php-8.3 php tests/project-generator/static-contract.php
```

The first group owns request validation, retained/removed example behavior,
deterministic bytes, archive/source identity, cleanup and metadata. The second
owns PHP 8.3 syntax/static generated-boundary evidence. A failure is collected
once and repaired as one static batch by the implementation owner; only the
failed group may run one additional time. A second failure blocks this slice.
Passed groups, CAP01-CAP06, aggregate, database, browser and Registry checks are
never repeated.

## Acceptance And Stop Line

Both groups must pass against one exact implementation commit/tree and one
resealed Generator digest. The integration record must retain
`PENDING_ALPHA5` for Composer/npm Registry values and must not nominate
`PA-DCS-ADOPT-01`.

Completion proves only Generator parameterization and removable fictional
Module fixtures. The complete MT01 Gate still requires separately owned
deterministic packaged generation, empty-database install/start, fail-closed
isolation, atomic Host command failure injection, external Module removal and
Admin Web smoke, followed by immutable Registry identity injection and one
fixed-candidate review. MT02 remains forbidden.
