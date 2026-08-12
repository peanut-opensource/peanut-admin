# P1 Alpha.5 npm Trusted Publication Path

## Fixed Contract

```text
task: P1-ALPHA5-NPM-PUBLICATION-PATH
state: publication path candidate
prerequisite_merge: 0c894a8c6b3dc4873ccffc066011d80e3b4227ee
artifact_commit: aeeff105df4960db6a70da7ee5597da9a85abdaa
packages_web_tree: 562043a0a294392a66ea0a305de6554d02646bc8
package: @peanut-admin/admin@0.1.0-alpha.5
tarball_sha256: 0397260512cb167f3705ddc89d6c03553420339d342ad2f7348ecb52eb7b86a3
workflow: .github/workflows/alpha5-npm-publish.yml
environment: npm-release
publication: not performed
```

The qualification merge preserves the exact qualified `packages/web` tree.
This path publishes only the tarball materialized from the fixed artifact
commit. It does not repack during publication, change Runtime or package
content, move `latest`, publish Composer, create a tag or Release, or repeat a
CAP01-CAP06 Gate.

The workflow has no ref, version, tag or candidate input. It runs only from the
`dev` branch of `peanut-opensource/peanut-admin`, uses the protected
`npm-release` environment, grants only `contents: read` and `id-token: write`,
and checks out the fixed artifact commit with Git credentials disabled. Every
action, Node, pnpm and npm tool version is fixed.

Before the external write, the workflow rejects an existing Alpha.5, confirms
that `latest` remains Alpha.2, runs the qualified projection checker, and
matches the exact source tree and tarball SHA-256. It publishes that tarball
through npm trusted publishing with provenance and the `alpha` dist-tag.

After publication it verifies the immutable version, unchanged `latest`, the
registry tarball SHA-256, package signatures and all 15 exports in one clean npm
consumer. A failure after npm accepts the immutable version must not be repaired
by unpublish, overwrite or retag; the publication completion record reports the
partial state and follows the existing newer-version recovery rule.

## External Binding And Ordered Stop Line

The npm package owner must bind exactly:

- organization/user `peanut-opensource`;
- repository `peanut-admin`;
- workflow filename `alpha5-npm-publish.yml`;
- environment `npm-release`;
- allowed action `npm publish` only.

The GitHub environment has one custom deployment branch policy, `dev`. The
trusted-publisher binding is created only after this workflow merges green.
Publication remains forbidden until the source and Composer split immutable
tags required by the parent publication contract exist. The workflow is then
dispatched once from `dev`; it is never used as a CI check or retried after npm
has accepted the version.

This task changes only the workflow, this record, `docs/content-status.json`
and `docs/status/index.md`. Static acceptance is YAML parse, JSON parse,
documentation registration, exact write-set inspection and `git diff --check`.
Repository CI owns the PR checks. Registry publication, clean Composer consumer,
GitHub prerelease and the final completion record remain later ordered actions.
