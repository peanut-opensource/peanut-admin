# P1 Alpha.5 npm Reproducible Identity Repair

## Fixed Failure And Scope

```text
task: P1-ALPHA5-NPM-PUBLICATION-R01
state: implementation authorized
failed_run: 31589966713
artifact_commit: aeeff105df4960db6a70da7ee5597da9a85abdaa
packages_web_tree: 562043a0a294392a66ea0a305de6554d02646bc8
package: @peanut-admin/admin@0.1.0-alpha.5
files: 72
exports: 15
qualified_tar_payload_sha256: 5850166ab965aa7ccb8d85058dd68a025c4741e9bc7b7af5c51536a8977b6eb6
publication: not performed
```

The first publication dispatch passed the immutable source, environment,
toolchain and absent-version checks, then stopped before `npm publish`. The
projection still reported the fixed artifact commit, package tree, 72 files and
15 exports, but the gzip-wrapped tarball SHA-256 differed between the original
macOS qualification and Ubuntu publication runner. The package tar payload is
the stable content boundary; the gzip wrapper produced by `pnpm pack` is not an
accepted cross-platform identity boundary.

No Registry write occurred. This is a publication identity contract defect,
not a Runtime, package-content, CAP01-CAP06, MT01 or trusted-publisher failure.

## Exact Repair Write Set

The implementation owner changes only:

- `scripts/check-alpha5-npm-package-projection` to report the SHA-256 of the
  decompressed tar payload in addition to the generated tarball path;
- `.github/workflows/alpha5-npm-publish.yml` to compare that payload digest
  before publication and after downloading the Registry tarball;
- `docs/status/p1-alpha5-npm-provenance-qualification.md`,
  `docs/status/p1-alpha5-npm-publication-path.md` and `docs/status/index.md` to
  replace the cross-platform gzip-wrapper claim with the qualified payload
  identity and record failed run `31589966713`.

The fixed source commit/tree, package version, 72-file inventory, 15 exports,
license, repository provenance metadata, forbidden-credential scan, `latest`
guard, OIDC workflow/environment and clean Registry consumer remain unchanged.
No package source, manifest, lock, dependency, Runtime, schema, Generator,
release tag or Registry value may change.

## Verification And Stop Line

Before commit, run Bash syntax, YAML/JSON parse, exact write-set inspection and
`git diff --check`. Repository PR checks own their normal verification and must
all finish successfully before merge. Do not re-run CAP01-CAP06, Generator,
database, browser, aggregate or the failed publication dispatch locally.

After the repair PR is fully green and merged, dispatch the fixed publication
workflow once from `dev`. A failure before `npm publish` remains isolated to
publication; a failure after npm accepts the immutable version follows the
existing partial-publication stop line and must not unpublish, overwrite or
retag it. Completion does not finish MT01 or authorize MT02.
