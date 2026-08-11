# P1-CAP05 Cross-Product Alpha.5 Qualification Review

## Decision

The fixed Alpha.5 source tree is qualified for CAP05 projection evidence. This
review binds that decision to the exact source commit and tree below and records
the two public-boundary projection results. It does not authorize CAP06 private
downstream adoption or public package publication.

## Fixed Candidate

| Field | Value |
| --- | --- |
| Source commit | `14010993e47f5e3082ab8f0b53456f282b71f086` |
| Source tree | `3fa7e79730ec9ed8f0349dc1c0d24fa72cfda54f` |
| Integration PR | PR #16; all six repository checks passed and the PR merged |
| Composer candidate | `peanut-admin/core@0.1.0-alpha.5` |
| npm candidate | `@peanut-admin/admin@0.1.0-alpha.5` |

The CAP05-R01 product-neutral fixture repair was the only continuation allowed
after the initial Composer projection stopped. The resumed Composer projection
passed; the npm projection result is retained because `packages/web` was not
changed by the remediation.

## Projection Evidence

| Projection | Fixed result | SHA-256 |
| --- | --- | --- |
| Composer `packages/php/` | 694 files, exactly 14 PSR-4 roots, and an isolated consumer passed | `ca30576ae9f671197c0050fea8a42e7d7e61b5c0f43abebd69aec99cd43e5c0e` |
| npm `packages/web/` | Retained result: 72 files and 15 exports | `5d01076276a4599682b65fcfde812f5fe201c3e597f2fab38b8ef23cbabe8c80` |

The Composer digest is the SHA-256 of
`git archive --format=tar HEAD packages/php` at the fixed source commit. The
npm digest is the retained projection digest from the unchanged Web subtree;
it was not regenerated during the CAP05-R01 continuation.

## Authorization Boundary

- CAP05 fixed-tree projection qualification is complete for the evidence above.
- CAP06 private downstream adoption remains unauthorized and requires a separate
  exact-commit decision.
- Public Composer/npm publication, registry writes, tags, Releases, and stable
  compatibility claims remain unauthorized and require a later approval.

No Runtime, manifest, dependency lock, package lock, plan, or test file changes
are part of this qualification record.

## Review Verification

This review records existing qualification evidence and did not rerun the
passed PR checks or either projection. The review write set was checked as the
single new review plus its two documentation registrations, the status JSON was
parsed successfully, and `git diff --check` passed.
