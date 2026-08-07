# P1-PKG02 Alpha Publication Contract

## Status

```text
state: approved-planning-contract
package_candidate_commit: deb85a7e3e65b4d323a6eff4c694724a1fd23338
composer_package: peanut-admin/core@0.1.0-alpha.1
npm_package: @peanut-admin/admin@0.1.0-alpha.1
qualification_owner: P1-PKG02-QUALIFICATION
publication_owner: P1-PKG02-PUBLICATION
```

P1-PKG02 qualifies and, only after every external ownership gate is recorded,
publishes the two public package boundaries produced by P1-PKG01. Internal
domain directories remain implementation ownership boundaries and must not
become independently published packages.

This contract does not add the future PC/UniApp client package. It does not
change Runtime behavior, package source, PHP namespaces, Web exports, schemas,
migrations, OpenAPI, authorization, Tenant isolation, audit, status
transitions, user-visible behavior, or the existing downstream-consumption
lock.

## Fixed Candidate Qualification

The package source candidate is the clean commit
`deb85a7e3e65b4d323a6eff4c694724a1fd23338`. The planning commit that adds this
contract may be checked out above that candidate, but no package or Runtime
source may change before qualification.

`P1-PKG02-QUALIFICATION` runs the repository aggregate gate exactly once:

```bash
./scripts/check
```

After it passes, the owner performs one package-content inspection without
publishing:

1. create an exact temporary split of `packages/php/` from the candidate;
2. verify that the split root contains `composer.json`, `LICENSE`, the ten
   runtime namespaces, and no Host, frontend, secret, generated credential, or
   unrelated monorepo content;
3. run `composer validate --strict` against the split manifest;
4. run `pnpm --dir packages/web pack --dry-run --json` and verify that every
   declared public subpath, package metadata, license, and required source file
   is present, while Host, test fixtures, secrets, and unrelated monorepo
   content are absent;
5. record SHA-256 digests for both immutable package projections.

If the aggregate gate or either content inspection fails, the owner performs
one read-only diagnosis and stops. A fix requires an independent remediation
contract and a new candidate commit; a failed package is never published.

The qualification evidence may change only:

- new `docs/reviews/p1-pkg02-alpha-publication-qualification.md`;
- `docs/status/index.md` for the exact qualification result;
- `docs/content-status.json` to register the evidence document.

Qualification does not authorize publication.

## Registry And Repository Gates

Before `P1-PKG02-PUBLICATION` may write external state, one approval record
must name and verify all of the following:

- the npm organization or owner authorized to publish the public
  `@peanut-admin/admin` scope;
- an npm automation token available to the publishing workflow without being
  committed or printed;
- the Packagist owner authorized to publish `peanut-admin/core`;
- the public Composer split repository whose root is the generated
  `packages/php/` projection, including its GitHub owner and immutable tag
  convention;
- GitHub repository and workflow permissions for the split push, tag, Release,
  npm provenance, and Packagist update;
- exact artifact contents, licenses, SHA-256 digests, and version-to-candidate
  mapping from the qualification evidence;
- rollback by publishing a newer immutable version and deprecating the broken
  version, never by mutating or deleting a published version.

The development monorepo remains the sole source of truth. The Composer split
repository is generated release output and accepts no direct development
commits. Publishing the monorepo root as `peanut-admin/core`, using a mutable
version, overwriting an existing version, or adding aliases, `replace`,
`provide`, metapackages, compatibility packages, or module-by-module packages
is forbidden.

## Publication Result

When every gate is satisfied, publication creates exactly:

- one immutable Composer version `peanut-admin/core` `0.1.0-alpha.1` from the
  qualified PHP split;
- one immutable public npm version `@peanut-admin/admin@0.1.0-alpha.1` from the
  qualified Web package;
- one GitHub prerelease that records the candidate commit, projection commits,
  registry URLs, artifact digests, licenses, and qualification evidence.

An isolated clean Composer consumer and an isolated clean npm consumer each
install the registry version once and resolve every documented namespace or
subpath. Passing those probes proves alpha package consumability only. It does
not claim production readiness, stable API compatibility, application
migration completion, or SaaS completion.

## Stop Conditions

Work stops before publication when any of these remains unknown or false:

- the fixed candidate aggregate gate passed;
- package contents and digests match the candidate;
- npm and Packagist ownership is verified;
- the Composer split repository exists with protected generated ownership;
- non-interactive publishing credentials are available;
- an immutable version can be created without replacing existing history;
- provenance and registry consumer probes can be recorded without exposing a
  credential.

## P1-PKG02-R01 Qualification Environment Retry

The first aggregate invocation stopped in the `scripts/check` preflight before
any check or test ran because `MYSQL_PORT` and `DB_PORT` were absent. This is an
environment-contract failure, not package or Runtime evidence. R01 authorizes
one new aggregate invocation against the unchanged package candidate with the
following complete isolated environment:

```bash
export COMPOSE_PROJECT_NAME=peanut-admin-pkg02-q01
export MYSQL_PORT=33412
export CACHE_PORT=36412
export BACKEND_PORT=38112
export FRONTEND_PORT=35212
export PEANUT_BROWSER_BACKEND_PORT=38212
export PEANUT_BROWSER_FRONTEND_PORT=35312
export MYSQL_DATABASE=peanut_admin_pkg02_qualification
export DB_HOST=127.0.0.1
export DB_PORT=33412
```

All six host ports were confirmed free before this contract. The repository
Compose file remains the sole service definition and starts MySQL 8.4.10 and
Valkey when their owning checks require them. `MYSQL_PORT` and `DB_PORT` must
remain equal; no existing application, shared database, fallback port, remote
database, or compatibility environment file may be used.

R01 may run only `./scripts/check` once with the exact environment above. It
may not change package source, tests, assertions, configuration, database
contracts, authorization, Tenant isolation, or qualification thresholds. A
failure receives one read-only diagnosis and stops PKG02 qualification.

## P1-PKG02-R02 Fixed PHP And Composer Toolchain Retry

R01 passed the documentation status checks and then stopped at the first PHP
autoload because the shell selected PHP 8.1.33 while the locked dependencies
require PHP 8.3 or newer. No PHP, Web, database, browser, recovery,
performance, or Starter test ran. The repository-required tools already exist
locally as PHP 8.3.24 and Composer 2.10.2.

R02 authorizes one new aggregate invocation against the unchanged package
candidate and unchanged R01 service environment. Before the invocation, the
owner may create only the temporary executable link
`/private/tmp/peanut-pkg02-toolchain/composer` pointing to
`/private/tmp/peanut-composer-2.10.2`. The command environment must additionally
contain:

```bash
export PATH=/private/tmp/peanut-pkg02-toolchain:/opt/homebrew/opt/php@8.3/bin:$PATH
export PEANUT_COMPOSER=/private/tmp/peanut-composer-2.10.2
```

Immediately before the aggregate invocation, `php -r 'echo PHP_VERSION;'` must
report `8.3.24`, `composer --version` must report `2.10.2`, and
`$PEANUT_COMPOSER --version` must report `2.10.2`. These are preflight facts,
not additional qualification runs.

R02 may then run only `./scripts/check` once with the complete R01 and R02
environment. It may not edit dependencies, locks, package source, tests,
assertions, configuration, qualification thresholds, or committed files. A
failure receives one read-only diagnosis and stops PKG02 qualification.

## P1-PKG02-R03 Documentation Runtime Count Remediation

R02 fixed the PHP and Composer toolchain, passed documentation status, Module
manifest verification, and the first two documentation PHPUnit groups, then
stopped in `scripts/verify-doc-examples`. Static diagnosis found one stale
Stage B assertion that still expects 75 P0 plus 4 P1 operations and 79 generated
routes. The authoritative Runtime ledger and generated route table both contain
75 P0 plus 64 P1 operations, for 139 routes.

Only `scripts/verify-doc-examples` may change. Its executable documentation
assertion and matching error message must replace the stale P1 and total-route
counts with `64` and `139`. It must retain the exact 75-operation P0 assertion,
per-P0 generated-route lookup, concrete-handler rejection, JSON parsing,
installation example, database isolation, Starter verification, and every
other check unchanged. Changing the Runtime ledger, OpenAPI, generated routes,
handlers, test selection, threshold logic, or accepting a range is forbidden.

After static review and `git diff --check`, R03 runs `./scripts/check-docs`
once with the complete R01/R02 environment. If it passes, qualification
continues once from `./scripts/check-dependency-decisions` through the remaining
commands in `scripts/check`, in the same order and environment, without
rerunning `check-doc-content-status` or `check-docs`. A failure receives one
read-only diagnosis and stops PKG02 qualification.

## P1-PKG02-R04 Starter Kernel Compatibility Version Remediation

R03 reached the internal Starter verification and stopped because the Starter
passed `KernelPackage::VERSION` (`0.1.0`) to the Module compiler while the
current Kernel compatibility protocol and Stage C Module manifests use
`1.0.0` / `^1.0`. Package release versions and the Module compatibility
protocol are separate version axes. A package version must never be used as a
substitute for the Host's declared compatibility version.

R04 may change only:

- `docs/status/p1-pkg02-alpha-publication-contract.md`;
- `scripts/verify-doc-examples` (the retained R03 count correction only);
- `starter/backend/config/modules.php`;
- `starter/backend/src/Module/ModuleRegistryFactory.php`;
- `starter/backend/tests/settings.php`;
- `starter/backend/tests/smoke.php`;
- `starter/backend/src/Modules/Example/Greeting/module.json`;
- `starter/backend/src/Modules/Peanut/Settings/module.json`;
- `starter/backend/src/Modules/Peanut/ReferenceCodes/module.json`;
- `starter/backend/src/Modules/Peanut/FileMedia/module.json`;
- `starter/backend/src/Modules/Peanut/TaskJob/module.json`;
- `starter/backend/src/Modules/Peanut/NotificationSms/module.json`;
- `tests/starter/assert-generated-starter.php`;
- `tools/project-generator/src/ProjectGenerator.php`;
- `tests/project-generator/static-contract.php`;
- `tests/project-generator/run.php`.

The Starter Module config and every generated project config must declare
`kernel_version` as `1.0.0`. The Starter compiler and the one direct compiler
fixture must consume that value. All Starter Module manifests must declare the
already documented `^1.0` compatibility protocol. Package smoke assertions
must continue to prove the independently versioned `0.1.0` package contents.
The generator and generated-Starter assertions must reject a missing or drifted
compatibility version.

R04 must not change the published package version, Module versions, Runtime
behavior, schemas, routes, operations, permissions, package manifests, release
candidate identity, test selection, or compatibility matcher. It must not
weaken a Module constraint to make compilation pass.

After static review and `git diff --check`, R04 runs `./scripts/check-docs`
once with the complete R01/R02 environment. If it passes, qualification
continues once from `./scripts/check-dependency-decisions` through the remaining
commands in `scripts/check`, in the same order and environment, without
rerunning `check-doc-content-status` or `check-docs`. A failure receives one
read-only diagnosis and stops PKG02 qualification.

## P1-PKG02-R05 Starter Admin Client Menu Remediation

R04 passed the Kernel compatibility boundary and then stopped when the Starter
compiler rejected `peanut.task-job.page` because it targeted the unregistered
`admin-web` Client. Read-only diagnosis confirmed that the Starter's declared
admin Client is `operations-web`: it is registered by backend auth, exposed by
the frontend Client registry, used by the earlier Starter Modules, and fixed by
the internal-Starter guide. Four later Module menus copied the reference Host's
`admin-web` key without registering or exposing that Client.

R05 retains the exact R03 and R04 changes above and may additionally change
only:

- `starter/backend/src/Modules/Peanut/TaskJob/Resources/menus.json`;
- `starter/backend/src/Modules/Peanut/NotificationSms/Resources/menus.json`;
- `starter/backend/src/Modules/Peanut/ImportExport/Resources/menus.json`;
- `starter/backend/src/Modules/Peanut/IntegrationSecurity/Resources/menus.json`;
- `tests/starter/assert-generated-starter.php`.

Each listed Tenant menu must target only `operations-web`. The generated
Starter assertion must prove that every configured Tenant Module menu targets
that registered admin Client. Adding `admin-web` to auth, frontend, or Module
configuration, retaining both keys, changing the platform Client, or weakening
unknown-Client rejection is forbidden. Project generation continues to replace
the template key with the application's explicitly selected admin Client.

After static review and `git diff --check`, R05 runs `./scripts/check-docs`
once with the complete R01/R02 environment. If it passes, qualification
continues once from `./scripts/check-dependency-decisions` through the remaining
commands in `scripts/check`, in the same order and environment, without
rerunning `check-doc-content-status` or `check-docs`. A failure receives one
read-only diagnosis and stops PKG02 qualification.

## P1-PKG02-R06 Consolidated Settings Install Root Remediation

R05 passed Module compilation and then stopped because the Starter Settings
fixture still required `settings/composer.json`. P1-PKG01 intentionally removed
all internal domain manifests: `peanut-admin/core/composer.json` is now the only
Composer manifest, and its PSR-4 map owns `settings/src/`. Read-only diagnosis
found no second Starter fixture that requires an internal domain manifest.

R06 retains the exact R03 through R05 changes and may additionally change only
`starter/backend/tests/settings.php`. The fixture must continue to prove that
Composer installed `peanut-admin/core` below the Starter vendor directory. It
must verify the public core manifest at the installed package root and the
Settings `src/Package.php` below that root. It must not require, recreate, or
accept an internal Settings manifest, path repository, copied source tree,
fallback root, or compatibility package.

After static review and `git diff --check`, R06 runs `./scripts/check-docs`
once with the complete R01/R02 environment. If it passes, qualification
continues once from `./scripts/check-dependency-decisions` through the remaining
commands in `scripts/check`, in the same order and environment, without
rerunning `check-doc-content-status` or `check-docs`. A failure receives one
read-only diagnosis and stops PKG02 qualification.

## P1-PKG02-R07 Structured Module Dependency Assertion Remediation

R06 passed the consolidated Settings install check and then stopped because the
Starter Import/Export fixture still expected the pre-schema string dependency
list. The committed manifest uses the current versioned dependency objects for
File/Media and Task/Job. Static review found no second Starter fixture with the
same stale assertion.

R07 retains the exact R03 through R06 changes and may additionally change only
`starter/backend/tests/import-export.php`. The assertion must require the exact
ordered dependency objects `peanut.file-media@^0.1` and
`peanut.task-job@^0.1`. It must not change the manifest, dependency versions,
Module compiler, schema, dependency resolution, or Tenant requirement list,
and it must not accept both the old and current shapes.

After static review and `git diff --check`, R07 runs `./scripts/check-docs`
once with the complete R01/R02 environment. If it passes, qualification
continues once from `./scripts/check-dependency-decisions` through the remaining
commands in `scripts/check`, in the same order and environment, without
rerunning `check-doc-content-status` or `check-docs`. A failure receives one
read-only diagnosis and stops PKG02 qualification.

## P1-PKG02-R08 Starter Web Async Response Remediation

R07 passed every PHP Starter integration and then stopped in the Starter Web
typecheck. Four Host adapters passed the `Promise<Response>` returned by their
required fetch function directly to local helpers typed for a resolved
`Response`. The public transport contracts already require the resulting
Promises and must not be widened or weakened.

R08 retains the exact R03 through R07 changes and may additionally change only:

- `starter/frontend/src/modules/peanut-file-media.ts`;
- `starter/frontend/src/modules/peanut-task-job.ts`;
- `starter/frontend/src/modules/peanut-import-export.ts`;
- `starter/frontend/src/modules/peanut-integration-security.ts`.

Each local result helper must accept `Promise<Response>`, await it exactly once,
and derive body, headers, and status from the resolved Response. R08 must not
change request URLs, methods, headers, credentials, abort signals, response
parsing, runtime permissions, public package types, or allow both synchronous
and asynchronous fetch contracts.

After static review and `git diff --check`, R08 runs `./scripts/check-docs`
once with the complete R01/R02 environment. If it passes, qualification
continues once from `./scripts/check-dependency-decisions` through the remaining
commands in `scripts/check`, in the same order and environment, without
rerunning `check-doc-content-status` or `check-docs`. A failure receives one
read-only diagnosis and stops PKG02 qualification.

## P1-PKG02-R09 Vue Relative Import Architecture Remediation

R08 completed `check-docs`, and the resumed dependency-decision gate passed.
The Runtime architecture gate then rejected four same-directory Vue component
imports. Its relative resolver recognizes TypeScript files and TypeScript index
files but not `.vue`, so a valid import such as `./FileAssetSelector.vue` is
reported as unresolved or cross-package before the existing package-root check
can classify it.

R09 may change only this contract and `scripts/check-architecture`. The
relative resolver must recognize an exact `.vue` target while retaining the
existing internal module roots, allowed dependency matrix, private-import
rejection, package-root containment check, TypeScript resolution, and cycle
analysis. It must not treat arbitrary extensions as source, scan generated
output, permit parent-directory escape, or remove any architecture check.

After static review and `git diff --check`, R09 reruns only
`PEANUT_RUNTIME_STAGE=runtime ./scripts/check-architecture` once with the
complete R01/R02 environment. If it passes, qualification continues from
`./scripts/check-openapi` through the remaining commands in `scripts/check`, in
the same order and environment. Passed documentation and dependency-decision
groups are not rerun. A failure receives one read-only diagnosis and stops
PKG02 qualification.

## P1-PKG02-R10 Explicit Vue Specifier Resolution Remediation

R09 retained the architecture boundary but appended `.vue` to an import that
already ended in `.vue`, producing a nonexistent `.vue.vue` candidate. The
read-only diagnosis confirmed that the original resolved path exists and is
inside its internal module root.

R10 retains the R09 write set. The resolver must use the original resolved path
only when the specifier explicitly ends in `.vue`; extensionless imports keep
the existing TypeScript and index candidates. Every selected candidate must be
an existing regular file. No other extension, directory, generated file,
parent escape, dependency edge, or public package import becomes allowed.

After static review and `git diff --check`, R10 reruns only
`PEANUT_RUNTIME_STAGE=runtime ./scripts/check-architecture` once with the
complete R01/R02 environment. If it passes, qualification continues from
`./scripts/check-openapi` through the remaining commands in `scripts/check`, in
the same order and environment. Passed groups are not rerun. A failure receives
one read-only diagnosis and stops PKG02 qualification.

## P1-PKG02-R11 Installed Core Migration Path Remediation

R10 resolved the Vue imports, after which the architecture gate rejected five
Host references to monorepo `packages/php/*` migration directories in
`UpgradeWorkflow`. The workflow already resolves its Kernel schema through
Composer `InstalledVersions`; installed applications must use that same public
package root for core migrations instead of requiring a source checkout.

R11 retains the R09/R10 write set and may additionally change only
`backend/app/command/UpgradeWorkflow.php`. Kernel and DataPermission migration
paths must resolve from their package names through the existing fail-closed
`packagePath()` helper and then append the corresponding internal directory.
All current, run, and individual-migration paths must use those helpers.

R11 must not accept the Host repository root as a fallback package root, add a
constructor override, change migration ordering or ledger names, alter release
verification, weaken unavailable-package failure, change a package name, or
relax the architecture prohibition on Host filesystem references to
`packages/php` or `packages/web`.

After static review and `git diff --check`, R11 reruns only
`PEANUT_RUNTIME_STAGE=runtime ./scripts/check-architecture` once with the
complete R01/R02 environment. If it passes, qualification continues from
`./scripts/check-openapi` through the remaining commands in `scripts/check`, in
the same order and environment. Passed groups are not rerun. A failure receives
one read-only diagnosis and stops PKG02 qualification.

## P1-PKG02-R12 Public Package Documentation Remediation

R11 removed the Host's monorepo package paths, after which the architecture
gate found the internal project name `DCS` in two README files included in the
Composer projection. Public package documentation must describe only generic
downstream consumption boundaries.

R12 retains the R09 through R11 changes and may additionally change only:

- `packages/php/import-export/README.md`;
- `packages/php/integration-security/README.md`.

The two references must use generic downstream-consumption wording without
changing capability, ownership, qualification, release, or stop-line meaning.
R12 must not exclude README files from the package, weaken the public-content
gate, rename a product in code, or alter Runtime behavior.

Because these README files are part of `peanut-admin/core`, the resulting R12
commit supersedes `deb85a7e3e65b4d323a6eff4c694724a1fd23338` as package source.
A separate planning commit must record the exact resulting 40-character commit
before any qualification resumes. No prior fixed-candidate qualification result
is evidence for the new projection.
