# P1 Alpha.5 Public Publication Contract

## Status

```text
task: P1-ALPHA5-PUBLICATION
state: execution authorized after this contract merges
source_repository: peanut-opensource/peanut-admin
composer_split_repository: peanut-opensource/peanut-admin-core
composer_package: peanut-admin/core@0.1.0-alpha.5
npm_package: @peanut-admin/admin@0.1.0-alpha.5
npm_dist_tag: alpha
immutable_tag: v0.1.0-alpha.5
publication_authorized: true after the pre-publish identity checks below pass
```

CAP05 qualification, the bounded remediation rollover, and CAP06 private
adoption are complete. This independent contract authorizes one public Alpha.5
publication from the exact adopted candidate. It does not authorize Runtime
changes, a stable compatibility promise, production use, MT01, MT02, or
`PA-DCS-ADOPT-01`.

## Immutable Candidate

| Field | Value |
| --- | --- |
| Core source commit | `0f3c0a530f2b6369bf5883b2508f40a79501ed98` |
| Core source tree | `691cf4812d08dc4a3927a78331be3267aa1e9c77` |
| Composer split commit | `ef06da45c9e77ae4b194bfc1f859ec007aa0e022` |
| Composer split tree | `e7beef2fe583ec6778e92b0d88702b1065fdb419` |
| Composer projection | 694 files / 14 PSR-4 roots / SHA-256 `8779231b00f8bd634635c246d569e896e36183f0d0ece8807584a8aa2632dcbd` |
| npm projection source | source commit above, subdirectory `packages/web` |
| npm projection | 72 files / 15 exports / SHA-256 `5d01076276a4599682b65fcfde812f5fe201c3e597f2fab38b8ef23cbabe8c80` |
| CAP06 application | `bafdf5b5aeb34d63e3b6c21a29817e688783ed21` / tree `8193d219f2109f8d7b7ea0366a575cc2956715e4` |
| CAP06 record merge | `76fa36e461ca73cb9a4e8367cbcc3d71e4672ba7` |

The source and split commits have different histories because the split is
mechanically generated from `packages/php/`. The monorepo remains authoritative;
the split repository accepts no direct development.

## Read-Only External Preflight

The 2026-08-12 preflight verified:

- the current GitHub identity has administration on both public repositories;
- the npm identity has read-write access to `@peanut-admin/admin`;
- Packagist lists the same maintainer identity for `peanut-admin/core` and is
  connected to the generated split repository;
- Alpha.5 is absent from npm, Packagist, both repositories' tags, and the
  monorepo Releases;
- the source repository protects release tags against update and deletion;
- npm `latest` remains Alpha.2 and `alpha` remains Alpha.4 before publication.

Immediately before the first external write, recheck only the identities,
version/tag absence and exact remote branch tips above. Any mismatch blocks the
publication; do not choose another commit or version implicitly.

## Publication Order And Exact Writes

After this contract PR passes and merges, the publication owner may perform
only the following ordered writes:

1. Create annotated `v0.1.0-alpha.5` on the source commit and the generated
   Composer split commit, then push each tag without force.
2. Materialize the exact npm projection from the source commit, verify its
   recorded digest/content, then publish public
   `@peanut-admin/admin@0.1.0-alpha.5` with dist-tag `alpha` and npm provenance.
   `latest` must remain Alpha.2.
3. Confirm Packagist exposes `peanut-admin/core@0.1.0-alpha.5` from the split
   tag. If its repository hook does not refresh automatically, request one
   authenticated package update; never change the split source or tag.
4. Create one GitHub prerelease `v0.1.0-alpha.5` in the source repository that
   records both commits/trees, both projection digests, package URLs and alpha
   stop line.
5. Run one clean PHP 8.3 Registry consumer and one clean npm Registry consumer.
   The Composer probe must resolve the split commit and all 14 PSR-4 roots; the
   npm probe must resolve all 15 exports from the published tarball.
6. Record the registry digests, URLs, consumer results, dist-tags and immutable
   tag targets in a separate completion record.

No source, manifest, lock, projection, dependency, Runtime, schema, test,
workflow, application file or historical record may change during publication.
The npm token or OIDC identity, Packagist session, GitHub credential and any OTP
must not enter Git, logs, artifacts or documentation.

## Provenance, Failure And Rollback

npm publication must use registry-verifiable provenance from a supported
GitHub Actions identity or an externally generated trusted provenance bundle.
An interactive local publish without provenance is not acceptable. The
publication task may add a narrowly scoped, exact-commit workflow only through
a separate contract if the existing repository lacks that identity path.

Each published version and tag is immutable. If a package is already published
when a later step fails, do not unpublish, overwrite, retag, force-push, move
`latest`, or claim completion. Finish the missing operational step when safe;
otherwise deprecate the affected npm version and publish a newer immutable
prerelease. Composer correction likewise uses a newer version and tag.

## Verification And Stop Line

This contract task changes only this file, `docs/content-status.json`, and
`docs/status/index.md`. It performs JSON parsing, exact-write-set inspection,
`./scripts/check-doc-content-status`, and `git diff --check`; repository CI owns
the normal PR gates. It does not repeat CAP01–CAP06 or either adopted consumer
Gate.

Publication is complete only after both registry versions, both immutable tags,
the prerelease and both clean Registry consumers are recorded. Until then,
Peanut Admin remains on private candidate locks and MT01 implementation stays
blocked.
