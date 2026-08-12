# P1 Alpha.5 npm Provenance Remediation Contract

## Status

```text
task: P1-ALPHA5-NPM-PROVENANCE
state: implementation authorized after this contract merges
prerequisite: P1-ALPHA5-PUBLICATION contract merged to dev
source_artifact_commit: 0f3c0a530f2b6369bf5883b2508f40a79501ed98
package: @peanut-admin/admin@0.1.0-alpha.5
publication: frozen until qualification and publication-path PRs merge green
```

The Alpha.5 publication contract requires registry-verifiable npm provenance.
The adopted npm projection has no `repository` metadata. npm provenance requires
the public repository identity in the published manifest, including the
monorepo package directory. Adding that metadata changes `package.json`, the
projection tree and the packed tarball digest, so the previously qualified
`5d01076276a4599682b65fcfde812f5fe201c3e597f2fab38b8ef23cbabe8c80`
artifact cannot be published as the provenance artifact.

This contract authorizes a metadata-only candidate rollover. It does not change
Runtime source, public exports, dependency resolution, locks, the Composer
projection or the source commit adopted by CAP06. It does not itself publish,
tag, release, move a dist-tag or start MT01.

## Candidate Qualification Stage

The qualification owner may change only:

- `packages/web/package.json`;
- `scripts/check-alpha5-npm-package-projection`;
- `docs/status/p1-alpha5-npm-provenance-qualification.md`;
- `docs/content-status.json`;
- `docs/status/index.md`.

The manifest change is limited to this exact repository declaration:

```json
"repository": {
  "type": "git",
  "url": "git+https://github.com/peanut-opensource/peanut-admin.git",
  "directory": "packages/web"
}
```

The checker must materialize `packages/web` from the resulting committed
candidate, use pnpm `11.13.0` for both dry-run and real packing, and prove:

- the exact 40-character candidate commit and `packages/web` tree;
- package name `@peanut-admin/admin`, version `0.1.0-alpha.5`, Apache-2.0
  license and the repository declaration above;
- exactly 72 packed files and the same exact 15 exports and `typesVersions`;
- identical normalized dry-run and tarball file lists;
- the approved license identity and absence of credential files or payloads;
- one newly computed SHA-256 tarball digest.

The qualification record freezes the resulting commit, tree, package subtree,
tarball digest, file count and exports. It explicitly supersedes only the old
npm projection digest for publication. The adopted Composer and CAP06
identities remain unchanged.

The owning stage runs the checker once on the final tree plus exact write-set,
JSON parse and `git diff --check`. A mechanical failure may receive one static
repair batch and only the failed group may run once more. Repository CI is not
duplicated locally. The passing tree is committed without further edits.

## Publication Path Stage

After the qualification commit and digest are known and merged green, a second
independent contract and PR may add only:

- `.github/workflows/alpha5-npm-publish.yml`;
- the Alpha.5 npm publication-path record and its existing documentation
  registration/status entries.

The workflow must:

- have `workflow_dispatch` with no candidate, ref, version or tag input;
- run only for `peanut-opensource/peanut-admin` from `refs/heads/dev` in the
  protected `npm-release` environment;
- use GitHub-hosted `ubuntu-24.04` with job permissions limited to
  `contents: read` and `id-token: write`;
- pin every action by full commit, Node `24.13.0`, pnpm `11.13.0` and an npm
  CLI version supporting trusted publishing;
- checkout the fixed qualification commit with full history and
  `persist-credentials: false`, then reject any non-exact checkout identity;
- run the fixed checker and publish that verified tarball, never repack in the
  publish step;
- use npm trusted publishing without `NPM_TOKEN`; publish public with dist-tag
  `alpha` and provenance;
- reject an existing Alpha.5 before the external write; afterwards verify the
  package provenance/signatures, fixed version, tarball identity, all 15
  exports, `alpha=0.1.0-alpha.5` and unchanged `latest=0.1.0-alpha.2`.

Before that PR is opened, the publication owner must verify the npm trusted
publisher binds the exact repository, workflow filename and `npm-release`
environment, and verify the environment's deployment restrictions. Missing or
different configuration blocks publication; a token-based fallback is not
authorized.

## Merge And Stop Line

The contract, qualification and publication-path PRs are separate commits and
PRs to `dev`. Auto-merge remains disabled. Each PR may merge only after every
declared check is `COMPLETED` with `SUCCESS`; a final green outcome does not
retroactively cure an early merge.

No existing passing CAP01-CAP06, projection, MySQL, consumer or CI group is
repeated. Until the exact qualification and publication-path PRs both merge
green, Alpha.5 external publication, adoption lock movement and MT01 writes
remain frozen.
