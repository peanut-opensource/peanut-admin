# P1-PKG03 Alpha.2 Publication Approval

## Status

```text
state: preflight-open
qualified_candidate_commit: b0dc376c2147b98522764486342c9525fe5678ce
source_repository: peanut-opensource/peanut-admin
source_branch: dev
composer_split_repository: peanut-opensource/peanut-admin-core
composer_package: peanut-admin/core@0.1.0-alpha.2
npm_package: @peanut-admin/admin@0.1.0-alpha.2
npm_dist_tag: alpha
immutable_tag: v0.1.0-alpha.2
publication_authorized: false
```

Alpha.2 replaces the unpublished Alpha.1 candidate. Publication remains
unauthorized until every pending gate below is verified and a separate
execution contract changes this record to `approved`.

## Ownership And Gate Record

| Gate | State | Evidence or required completion |
| --- | --- | --- |
| Qualified source | verified | Candidate `b0dc376c2147b98522764486342c9525fe5678ce`; qualification records both projection digests |
| Source monorepo | verified | Public `peanut-opensource/peanut-admin`; current GitHub identity has organization administration |
| Composer split name | verified | `peanut-opensource/peanut-admin-core` is absent and available |
| Composer split creation/protection | pending | Create a public generated-only repository with `main`; protect immutable tags and disallow direct development |
| npm package availability | verified | Public registry returns 404 for `@peanut-admin/admin`; no version exists |
| npm scope ownership | pending | Verify or establish administrator ownership of `@peanut-admin` through an authenticated npm session |
| npm publisher | pending | Configure provenance-capable trusted publishing or a granular automation token stored only as an Actions secret |
| Packagist package availability | verified | Packagist returns 404 for `peanut-admin/core`; no version exists |
| Packagist ownership | pending | Verify an authenticated owner and submit only the generated split repository |
| Packagist update identity | pending | Configure the provider hook/token without committing or printing it |
| GitHub publication workflow | pending | Generate both projections from the qualified candidate, verify digests, publish npm provenance, tag immutable outputs, and create a prerelease |
| Version and tag uniqueness | verified | Neither Alpha.1 nor Alpha.2 is published; Alpha.2 is the only current target |
| Isolated registry consumers | pending | After publication, install each registry version in one clean isolated consumer and resolve every public namespace/subpath |

## Immutable Projection Mapping

| Artifact | Source | Qualified SHA-256 |
| --- | --- | --- |
| Composer split | Candidate subtree `packages/php/` | `314f7eb0aaff6f288859b8dfab950487bd2b8b933b41a92dd02c49a09cf84411` |
| npm tarball | Candidate subtree `packages/web/`, packed by pnpm 11.13.0 | `94b15ddcbe031b109e687b01c61002b343c8259d4b0745b05e64b391718b13ef` |

The monorepo remains authoritative. The Composer split is generated output and
accepts no direct feature or fix commits. A release record must map the source
candidate, split commit, npm tarball, both digests, tags, registry URLs,
workflow run, provenance, and consumer evidence.

## Publication Sequence

1. Verify npm and Packagist ownership without exposing credentials.
2. Create and protect `peanut-opensource/peanut-admin-core` as generated-only.
3. Generate the exact Composer split and confirm its qualified digest.
4. Generate and confirm the npm tarball, then publish it with dist-tag `alpha`
   and provenance.
5. Tag immutable outputs `v0.1.0-alpha.2`, update Packagist, and create a GitHub
   prerelease on the monorepo.
6. Run one clean Composer and one clean npm registry consumer.
7. Mark this record completed only after every external URL, digest,
   provenance record, and consumer probe is fixed.

## Rollback And Credential Boundary

Published registry versions and Git tags are immutable. A defect is corrected
by a newer prerelease and deprecation, never mutation, retagging, or deletion.
No npm, Packagist, or GitHub credential belongs in Git, command output, build
logs, package contents, release artifacts, or qualification evidence.

## Stop Line

`publication_authorized` remains `false`. No publication, split push, tag,
Release, registry write, or application migration may occur while any ownership,
protection, workflow identity, credential, or consumer gate is pending.
