# Core 0.1.0 Publication Contract

```text
state: development-candidate
version: 0.1.0
source_repository: peanut-opensource/peanut-admin
composer_split_repository: peanut-opensource/peanut-admin-core
composer_package: peanut-admin/core@0.1.0
npm_package: @peanut-admin/admin@0.1.0
source_prerequisite: 53a872d00d932e2cd2985c17115df4fbd5ecff8a
publication_trigger: annotated v0.1.0 tag on the exact origin/main tip
```

## Scope

This release promotes the two existing public package boundaries to their first
stable version. It includes the application-neutral infrastructure extracted by
P1-PKG12 and the preceding qualified Core capabilities already present on
`dev`. It does not add another public package, application product behavior,
database migration, OpenAPI operation, or third-party dependency.

Stable compatibility applies to the documented Composer PSR-4 roots and npm
exports. Internal reference Host, testing, Generator and starter source remain
repository development surfaces and are not separately published packages.

## Fixed Candidate Qualification

The release owner must first merge the complete release preparation to `dev`,
merge `dev` to `main`, and then qualify the exact `origin/main` commit and tree.
Qualification consists of one clean fixed-candidate repository aggregate plus
the two isolated package projections:

```bash
./scripts/check
./scripts/check-composer-package-projection <empty-output> 0.1.0
./scripts/check-npm-package-projection <empty-output> 0.1.0
```

The resulting source commit/tree and projection digests are external release
evidence. They are not embedded into the candidate tree because doing so would
change the identity being qualified. Any Runtime or package-content failure
invalidates the candidate and returns work to `dev`.

## Tag Publication

Only an annotated `v0.1.0` tag whose target is the exact qualified
`origin/main` tip may trigger `.github/workflows/release.yml`. The workflow:

1. re-verifies source, tag, package version and Registry absence;
2. generates and atomically publishes the Composer split commit and tag;
3. refreshes Packagist and installs the immutable dist in a clean consumer;
4. publishes npm through the protected `npm-release` trusted-publishing
   environment with provenance and verifies a clean Registry consumer;
5. creates the GitHub Release with source, split and npm identities.

The protected environment must provide a repository-scoped Composer split
deploy key and Packagist credential references. npm trusted publishing must be
bound to this repository, `.github/workflows/release.yml`, and environment
`npm-release`. Secrets never enter source, logs or release notes.

Published tags and Registry versions are immutable. A partial publication is
completed only through the remaining safe step or a newer version; it is never
repaired by force-push, retag, overwrite or unpublish.

## Stop Line

This contract and the version changes create a development candidate only.
They do not by themselves qualify Core, authorize downstream lock movement, or
claim that `0.1.0` has been published. Those states require the exact main
qualification and external Registry results above.
