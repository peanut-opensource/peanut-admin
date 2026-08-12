# MT01 Generator Parameterization And Fixture Preparation Contract

## Status And Fixed Input

```text
task: MT01-GENERATOR-01
state: implementation-ready contract
contract_owner: MT01 Core Generator owner
implementation_owner: MT01 Core Generator implementation owner
test_owner: MT01-GENERATOR-PARAMETERS-001
prerequisite_commit: 98d97aa11617a5178e3ed804d33df1d05e4543fe
prerequisite_tree: 36d7bbd27cde97836b36adab47a653d8d93ea6b3
schema_change: none
migration_change: none
dependency_change: none
registry_write: forbidden
qualification: preparation only
```

The prerequisite is the latest accepted Core `dev` input when this contract was
fixed. It contains the passed CAP06 private adoption record. The application
plan's older recovery pointer saying that CAP06 still waits for implementation
is historical and is not permission to repeat CAP06. Neither a branch name nor
the unrelated local draft `f7b4dd5` is an input.

This is the first independently reviewable MT01 sub-contract. It freezes the
Generator parameter and removable-fixture boundary needed before the complete
MT01 consumption Gate can be contracted. It does not run that Gate and does not
start application MT02.

## Objective And Non-Goals

The implementation must make one generated Host identity reproducible from an
explicit parameter set, let a company product omit the fictional
`example.greeting` Module completely, and record enough non-secret ownership
metadata for a later isolated fixture to prove Host extension.

This slice does not:

- publish or select a Composer/npm Registry version;
- create a product domain Module, table, migration, controller, page, or
  business fixture in Core;
- change Kernel, package, authentication, authorization, Tenant, Module,
  transaction, idempotency, audit, outbox, OpenAPI, or Admin Web Runtime;
- make the Generator update, merge, overwrite, or migrate an existing project;
- perform clean install, upgrade, real database, browser, release, or
  downstream-adoption qualification;
- nominate `PA-DCS-ADOPT-01` or authorize DCS Runtime work;
- change an application manifest or lock, a package manifest or lock, a
  workflow, or the Runtime coverage ledger.

## Canonical Parameter Contract

The existing `create-project` command remains the only entry point. Its
canonical generation identity contains all of these inputs in this order:

| Parameter | Contract |
| --- | --- |
| `--target` | New absent or empty non-symlink directory; excluded from content identity |
| `--slug` | Existing bounded lowercase project slug |
| `--display-name` | Existing bounded UTF-8 text value |
| `--php-namespace` | Product-owned multi-segment PSR-4 root; generated Host and external Module namespace authority |
| `--brand` | Existing bounded UTF-8 text value rendered only through text bindings |
| `--profile` | Exactly `standard-admin` in this schema |
| `--tenant-client key=/api/.../vN/` | One to eight unique Tenant Client keys and protected API prefixes |
| `--admin-client key` | Explicit when more than one Tenant Client exists; must name a declared Client |
| `--feature key` | Existing canonical first-party Module selection and dependency order |
| `--example-module retain\|remove` | New explicit fictional-fixture disposition; default `retain` preserves existing callers |

`peanut-project.json` remains deterministic and must record the canonicalized
values, including `example_module: "retained"` or `"removed"`. It must also
record Generator schema version, the exact 40-character input commit and tree,
the Generator digest defined below, and `secrets.embedded: false`. Absolute
source/target paths, timestamps, random ownership markers, credentials, and
environment-specific values are forbidden from the identity.

Unknown, missing, duplicate, malformed, non-canonical, conflicting, or
dependency-incomplete inputs fail before output is published. Input order may
be canonicalized only where the existing contract already declares canonical
order; no implicit Client, namespace, API prefix, Module, schema owner, or
migration owner may be invented.

## Namespace, API, Module, Schema, And Migration Ownership

- `--php-namespace` owns the generated Host namespace. Application Modules use
  a child namespace selected by the application; the Generator must not put a
  product Module under a `PeanutAdmin` package namespace.
- Each `--tenant-client` pair is the authority for its Client key and protected
  API prefix. Prefixes remain absolute, normalized, distinct, versioned paths;
  a protected transport must reject another Client prefix or origin.
- `--feature` selects only the existing first-party `peanut.*` Modules. Their
  manifests remain the Module-key authority and their existing package
  migrations remain the schema/migration authority.
- The Generator owns no database schema and creates no migration in this slice.
  A later application-owned Module owns its tables and append-only migrations
  through its manifest and `OwnedMigration`; the migration owner must exactly
  equal the Module key. Core must not infer ownership from a namespace, folder,
  table prefix, Client key, or API prefix.
- A later MT01 fixture may use only the product-neutral key `fixture.record`
  under the generated product namespace. Its API route must be nested under one
  declared Tenant Client prefix, and its schema/migration owner is exactly
  `fixture.record`. The fixture remains test-only and is not copied into Core
  Runtime or a generated product unless a later contract explicitly says so.

When `--example-module remove` is selected, generated output contains no
`example.greeting` manifest, provider, route, frontend module/page, setting
definition, menu/component reference, verification import, expected Module
key, or README claim. First-party Modules and the always-on Ops Console keep
their existing behavior. Retaining the example keeps the current internal
starter contract.

## Generator Identity And Final Registry Fields

The Generator digest algorithm is
`sha256-git-blob-manifest-v1`. At a fixed source commit, enumerate regular Git
blobs copied or executed by generation under `scripts/create-project`,
`tools/project-generator`, `starter`, `packages/php`, and `packages/web` using
the Generator's controlled path allowlist. Sort by repository-relative path
and hash the exact sequence:

```text
<git-mode> <40-character-blob-id><TAB><repository-relative-path><LF>
```

Symlinks, submodules, unsupported modes, a missing controlled entry, dirty
controlled content, or disagreement between checkout/archive identity and the
manifest fail closed. Two runs from the same fixed source and canonical
parameters must produce identical file paths, modes, and bytes outside their
different target directories.

This contract deliberately does not fill the final Registry identity. A later
Alpha.5 publication/Registry decision and the complete MT01 Gate must replace
every `PENDING_ALPHA5` value below with one immutable value in a separate
record; private branches, moving HEADs, path repositories, and guesses are not
valid substitutes.

| Final field | Current value |
| --- | --- |
| Core source repository | `PENDING_ALPHA5` |
| Core 40-character source commit | `PENDING_ALPHA5` |
| Core 40-character source tree | `PENDING_ALPHA5` |
| Generator digest and algorithm | `PENDING_ALPHA5` |
| Composer package version and immutable Registry source | `PENDING_ALPHA5` |
| npm package version and immutable Registry source/integrity | `PENDING_ALPHA5` |
| Complete canonical generation parameters | `PENDING_ALPHA5` |

The current private Alpha.5 adoption inputs are evidence for CAP06 only. They
do not become public Registry values or a company adoption lock through this
contract.

## Fail-Closed And Safety Behavior

- The source must be clean, ancestry-bound, and equal to its controlled digest
  before the target is claimed. A validated `git archive` must carry expanded
  commit/tree identity; an unexpanded or edited archive is rejected.
- The target must not overlap the source, traverse parents, resolve through a
  symlink, or contain pre-existing content. A generation failure removes only
  content carrying the current run's exact private ownership marker.
- A successful project contains no marker, Git state, source absolute path, or
  secret value. Labels remain text, never compiled template or HTML input.
- `remove` is exact and exhaustive. If any example reference cannot be removed
  safely, generation fails and publishes no partial project.
- Missing Tenant context, wrong Client/audience, cross-Tenant access, disabled
  Module, missing functional Permission, missing data declaration/resolver/
  Provider, or unauthorized typed target remains denied by the existing
  Runtime. The Generator must not add a bypass, silent fallback, implicit
  Module enablement, body-supplied Tenant, or test-only production switch.
- Generation never executes Composer/pnpm, contacts a Registry, initializes
  Git, writes credentials, or runs migrations.
- A non-empty generated project is never an update target. Upgrade and source
  migration require a later append-only release/upgrade contract; regeneration
  over an application is forbidden.

## Exact Implementation File Whitelist

After this contract is committed independently, one implementation commit may
change only:

- `tools/project-generator/src/ProjectGenerator.php`;
- `tools/project-generator/source-baseline.json` only to reseal the controlled
  Generator identity required by this exact implementation;
- `tests/project-generator/run.php`;
- `tests/project-generator/static-contract.php`;
- `docs/guide/internal-starter.md`;
- `docs/status/mt01-generator-parameterization-contract.md` only to record the
  implementation commit and static evidence;
- `docs/status/index.md` only to change this slice from implementation-ready to
  candidate.

No `starter/**`, Runtime source, manifest, dependency manifest/lock, package
version, migration, schema, OpenAPI/generated artifact, workflow, release,
Registry, or application file may change. If exhaustive example removal cannot
be implemented within this whitelist, work stops for a separate contract
amendment; the whitelist is not widened during implementation.

## Test Ownership And Acceptance

`MT01-GENERATOR-PARAMETERS-001` owns the focused executable evidence. Tests
must be written to fail before implementation and prove:

- existing default generation retains `example.greeting` and remains byte
  deterministic;
- explicit `remove` produces two byte-identical outputs with no example path,
  key, route, namespace, setting, component, import, test, or documentation
  reference;
- both outputs record exact source commit/tree, the same valid Generator
  digest, canonical parameters, schema version, and no embedded secret;
- invalid example-mode, namespace, Client key/prefix, admin Client, feature,
  target, source identity, digest, archive identity, or partial-removal
  condition fails before a project is published;
- the generated namespace, Tenant Clients/API prefixes, selected first-party
  Modules, and Module migration ownership compile without an implicit owner;
- a product-neutral `fixture.record` manifest and `OwnedMigration` can be added
  under the generated namespace in test space, and its Module key, Client API
  prefix, owned table, and migration owner remain exact;
- target collision, source overlap, symlink, dirty source, HTML-like labels,
  failed-copy cleanup, no-secret, no-network, and no-overwrite assertions remain
  intact.

Per repository verification policy, the contract commit runs only JSON parse,
documentation content-status validation, exact-write-set review, and
`git diff --check`. The implementation owner performs static review and exact
write-set checks; automated Generator tests belong to the later integration
owner `MT01-GENERATOR-INTEGRATION-001`, which runs each contracted group once.
No aggregate, database, browser, Registry, or publication command belongs to
this preparation slice.

## Stop Line

Completion creates only an unqualified Generator-parameterization candidate.
The full MT01 Gate still needs separate fixed contracts for Registry/package
identity, clean empty-database install/start, isolation and atomic-command
fixtures, removable external Module evidence, Admin Web smoke, upgrade and
recovery behavior, and final fixed-candidate review.

`PA-DCS-ADOPT-01` remains `UNKNOWN`/unnominated until public Alpha.5 Registry
identity is fixed and every complete MT01 Gate group passes against one source
commit/tree and Generator digest. DCS may continue contracts, data cleaning,
fixtures, migration mapping, and acceptance matrices only. MT02, application
Tenant adoption, DCS Runtime creation, legacy Runtime copying, package
publication, tags, Releases, deployment, and production claims are forbidden.
