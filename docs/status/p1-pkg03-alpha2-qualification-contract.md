# P1-PKG03 Alpha.2 Qualification Contract

## Status

```text
state: accepted
package_candidate_commit: f837d3bf037d7da895cbe987f503fc9e7bf13388
composer_package: peanut-admin/core@0.1.0-alpha.2
npm_package: @peanut-admin/admin@0.1.0-alpha.2
qualification_status: pending
publication_authorized: false
```

## Objective

Qualify the exact Alpha.2 candidate after its workspace gate passed. The
candidate contains exactly two public packages, the PHP and Admin Web override
Host chains, and the UI-neutral `./client`, `./client/nuxt`, and
`./client/uniapp` npm subpaths. This task does not change package source,
publish a registry version, create a split repository, tag, Release, or approve
downstream application consumption.

## Fixed Environment

The qualification owner runs from the planning commit above the fixed
candidate with no package or Runtime source changes. These six host ports were
confirmed free before this contract:

```bash
export COMPOSE_PROJECT_NAME=peanut-admin-pkg03-q01
export MYSQL_PORT=33432
export CACHE_PORT=36432
export BACKEND_PORT=38132
export FRONTEND_PORT=35232
export PEANUT_BROWSER_BACKEND_PORT=38232
export PEANUT_BROWSER_FRONTEND_PORT=35332
export MYSQL_DATABASE=peanut_admin_pkg03_qualification
export DB_HOST=127.0.0.1
export DB_PORT=33432
export PATH=/opt/homebrew/opt/php@8.3/bin:$PATH
export PEANUT_COMPOSER=/tmp/peanut-composer-2.10.2-wrapper
```

Immediately before the gate, PHP must report `8.3.24`, Composer `2.10.2`, Node
`24.13.0`, and pnpm `11.13.0`. The owner then runs exactly once:

```bash
./scripts/check
```

A failure receives one read-only diagnosis and stops qualification. A fix
requires an independent remediation contract and a new fixed candidate.

## Package Content Inspection

After the aggregate gate passes, one read-only package inspection must:

1. archive the exact `packages/php/` subtree with deterministic ordering and
   record its SHA-256 digest and file count;
2. verify its root manifest, Apache-2.0 license, exactly ten Runtime PSR-4
   roots, override contracts, and absence of Host, Web, secrets, credentials,
   and unrelated monorepo content;
3. run Composer strict validation against that projection;
4. pack the exact `packages/web/` subtree with pnpm 11.13.0, record its
   SHA-256 digest and file count, and verify all fourteen declared export
   subpaths including the three client entries;
5. verify the npm projection excludes Host applications, environment files,
   secrets, credentials, and unrelated monorepo content.

The inspection does not contact npm, Packagist, or GitHub and does not mutate a
registry, repository, tag, Release, or application lock.

## Qualification Write Set

Only the following evidence may change after all gates pass:

- new `docs/reviews/p1-pkg03-alpha2-publication-qualification.md`;
- `docs/status/index.md` for the exact result;
- `docs/content-status.json` to register the review.

The evidence must record the candidate commit, planning commit, toolchain,
aggregate result, both projection digests and file counts, public package and
export boundaries, retained warnings, and remaining publication gates.

## Stop Line

Qualification does not authorize publication or Peanut Admin application
migration. Alpha.2 remains unpublished until a separate approval verifies npm
scope ownership, Packagist ownership, the generated Composer split repository,
workflow identities and credentials, immutable version uniqueness, provenance,
and isolated registry consumers.
