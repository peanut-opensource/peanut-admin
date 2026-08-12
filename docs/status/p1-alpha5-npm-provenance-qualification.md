# P1 Alpha.5 npm Provenance Qualification

## Fixed Result

```text
task: P1-ALPHA5-NPM-PROVENANCE-QUALIFICATION
state: qualified for exact-commit publication-path implementation
artifact_commit: aeeff105df4960db6a70da7ee5597da9a85abdaa
packages_web_tree: 562043a0a294392a66ea0a305de6554d02646bc8
package: @peanut-admin/admin@0.1.0-alpha.5
files: 72
exports: 15
qualification_tarball_sha256: 0397260512cb167f3705ddc89d6c03553420339d342ad2f7348ecb52eb7b86a3
tar_payload_sha256: 5850166ab965aa7ccb8d85058dd68a025c4741e9bc7b7af5c51536a8977b6eb6
publication: not performed
```

The artifact commit adds only the required public repository identity to the
npm manifest and adds the exact projection checker. Runtime, dependencies,
locks, public exports, package version, Composer projection and CAP06 inputs do
not change. The new digest supersedes the prior `5d010762...` npm digest only
for the provenance publication artifact.

The qualification owner ran once:

```bash
./scripts/check-alpha5-npm-package-projection /private/tmp/pa-alpha5-npm-qual.nEOr7t
```

It passed against the artifact commit and reported the fixed tree, file/export
counts, local qualification wrapper digest and stable tar payload digest above.
The gzip wrapper digest is historical local evidence, not the cross-platform
publication identity. JSON parse, Bash syntax, exact two-file artifact write
set and `git diff --check` also passed. No CAP01-CAP06, Composer projection,
database, browser, Registry consumer or repository aggregate Gate was repeated.

Publication remains blocked until the separate exact-commit OIDC workflow is
contracted and merged green and npm trusted publishing binds that exact
workflow plus the `npm-release` GitHub environment. `latest` must remain
`0.1.0-alpha.2`; MT01 final Registry fields and `PA-DCS-ADOPT-01` remain
unresolved.
