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
