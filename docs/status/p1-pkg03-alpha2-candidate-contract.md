# P1-PKG03 Alpha.2 Candidate Contract

## Status

```text
state: accepted
prerequisite_commit: fea3098d027acbea416610f207a83421829449a3
composer_package: peanut-admin/core@0.1.0-alpha.2
npm_package: @peanut-admin/admin@0.1.0-alpha.2
npm_dist_tag: alpha
immutable_tag: v0.1.0-alpha.2
qualification_status: pending
publication_authorized: false
```

## Objective

Replace the unpublished `0.1.0-alpha.1` publication candidate with one fixed
`0.1.0-alpha.2` candidate that includes the two public package boundaries, the
qualified PHP and Web override Host chains, and the UI-neutral `./client`,
`./client/nuxt`, and `./client/uniapp` subpaths.

`0.1.0-alpha.1` remains historical qualification evidence and is not published
first. No registry version, split repository, tag, Release, application
migration, or downstream-consumption decision is created by this task.

## Package Boundary

The candidate still contains exactly two public runtime packages:

- Composer `peanut-admin/core`;
- npm `@peanut-admin/admin`.

Domain directories and client subpaths are internal module or entry boundaries,
not independently versioned packages. No alias, compatibility package,
metapackage, `replace`, `provide`, copied source package, or third client package
may be added.

The version change must not alter Runtime behavior, PHP namespaces, Web public
contracts except for the already committed client subpaths, dependencies,
schemas, migrations, OpenAPI, authorization, Tenant isolation, audit,
idempotency, status transitions, UI, or user-visible results.

## Version Candidate Write Set

After this contract is committed independently, the version-candidate task may
change only:

- `packages/php/composer.json`;
- `packages/web/package.json`;
- `composer.json` and `composer.lock`;
- `backend/composer.json`;
- `starter/backend/composer.json` and `starter/backend/composer.lock`;
- `package.json` and `pnpm-lock.yaml`;
- `starter/frontend/package.json` and `starter/pnpm-lock.yaml`;
- `scripts/check-workspace`, only to update Alpha.2 version assertions and add
  `./client`, `./client/nuxt`, and `./client/uniapp` to its exact expected Web
  export list;
- `tests/starter/assert-generated-starter.php`;
- `README.md` and `starter/README.md` for current candidate wording;
- `docs/status/index.md` for current candidate wording;
- `docs/reference/third-party-licenses.generated.md`, generated only by its
  existing writer;
- new `docs/decisions/dependencies/p1-pkg03-lock-evidence.json`;
- `docs/content-status.json` to register that evidence.

Historical P1-PKG01/P1-PKG02 contracts, qualifications, lock evidence, override
contracts, and their fixed hashes must not be rewritten. Package source,
dependencies, exports, lock resolution other than the first-party version,
tests, scripts other than the exact version and export assertions above, and
publication records must not change.

The integration owner updates structured manifests first, regenerates the four
locks with the existing package managers, regenerates the license inventory,
records exact lock evidence, verifies the exact write set, and runs:

```bash
./scripts/check-workspace
```

This group runs once. A failure receives one read-only diagnosis and one static
batch correction inside this write set; only the failed group may run once
more. The passing clean commit becomes the fixed Alpha.2 candidate.

## Fixed Candidate Qualification

After a separate planning record fixes the exact version-candidate commit, the
qualification owner runs one aggregate candidate gate:

```bash
./scripts/check
```

Passing groups from the fixed candidate are not repeated. A failing group is
diagnosed once and requires an independent remediation contract before one
new fixed candidate may be qualified.

After the aggregate gate passes, qualification performs one content inspection
without publishing:

1. archive the exact `packages/php/` subtree and verify the root manifest,
   Apache-2.0 license, ten runtime PSR-4 roots, expected override contracts, and
   absence of Host, Web, credential, or unrelated monorepo content;
2. run `composer validate --strict` against that projection;
3. pack the exact `packages/web/` subtree and verify metadata, license, all
   existing Admin entries plus `./client`, `./client/nuxt`, and
   `./client/uniapp`, and absence of Host, secrets, and unrelated content;
4. record SHA-256 digests, file counts, tool versions, and the exact candidate
   commit in a new qualification record.

Qualification evidence may change only a new P1-PKG03 review document,
`docs/status/index.md`, and `docs/content-status.json`. Qualification does not
authorize publication.

## External Publication Gates

The historical P1-PKG02 approval record is not authority for Alpha.2. A new
approval record must verify, without exposing credentials:

- administrator ownership of the npm `@peanut-admin` scope;
- Packagist ownership for `peanut-admin/core`;
- a public generated-only `peanut-opensource/peanut-admin-core` split repository
  with protected immutable tags;
- GitHub workflow permission and non-interactive npm/Packagist identities;
- version and tag uniqueness;
- exact projection digests and provenance-capable publication;
- one isolated Composer registry consumer and one isolated npm registry
  consumer after publication.

Only after every gate is verified may a separate execution contract set
`publication_authorized: true` and perform external writes. Published versions
and tags are immutable; correction uses a newer version and deprecation, never
overwrite, retag, or deletion.

## Stop Line

This task does not publish, push a split, create a repository, tag, Release,
registry package, token, secret, or application dependency. Peanut Admin must
not consume a path-mapped or unpublished package. Application migration starts
only after exact registry consumers pass and a downstream decision names the
published version.
