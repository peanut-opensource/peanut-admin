# Project Generator And Internal Starter

## Create A New Project

`create-project` creates a new code project from the fixed Starter v1 template.
It is not a business `Project` CRUD feature and it never modifies an existing
application.

```bash
./scripts/create-project \
  --target /path/to/new-admin \
  --slug north-star-admin \
  --display-name "North Star Admin" \
  --php-namespace 'NorthStar\Admin' \
  --brand "North Star" \
  --profile standard-admin \
  --tenant-client operations-web=/api/operations/v1/ \
  --tenant-client reporting-web=/api/reporting/v1/ \
  --feature settings \
  --feature reference-codes \
  --feature file-media
```

The target must not exist or must be an empty, non-symlink directory outside
the Peanut Admin source tree. Parent traversal, a symlink target, a non-empty
target, duplicate or unknown features, invalid PSR-4 namespaces, and duplicate
Tenant Client keys or API prefixes fail before publishing output. The generator
does not run Composer or pnpm, initialize Git, create a remote, or contact a
network service.

The generated `peanut-project.json` is deterministic and records:

- generator schema version;
- the exact Peanut Admin input commit and tree;
- project slug, display name, PHP namespace, brand, and profile;
- Tenant Client keys and protected API prefixes;
- enabled first-party Modules;
- the fact that no secret value is embedded.

`standard-admin` is the only current profile. `settings`, `reference-codes`,
and `file-media` are optional first-party Modules; the fictional
`example.greeting` Module remains a removable example. All package snapshots
remain present for the fixed lock files, but only selected Modules are
registered in the generated Host. Dependency versions remain fixed, so
generation itself needs no dependency resolution.

The generator creates a private ownership marker while copying. On failure it
removes partial output only when that marker still exactly belongs to the
current run. A pre-existing empty target directory is retained. On success the
marker is removed, and running the generator against the now non-empty project
is rejected rather than treated as an update.

`.env.example` contains blank secret slots only. Supply application keys,
identifier HMAC keys, Settings encryption keys, database credentials, and
provider credentials through the deployment's secret system.

## Fixed Internal Qualification Fixture

The P0 internal starter is a fixed integration fixture. It proves that a clean
host can consume the current PHP and Web packages through their public package
roots. It remains the qualification source used by `create-project`; the
generator customizes only its copy and never edits `starter/**`.

Create a project in an empty directory:

```bash
./scripts/create-internal-starter /tmp/peanut-admin-starter
```

The command copies the fixed host, committed dependency lock files, and complete
local version `0.1.0` package snapshots, including migrations and schemas. The generated manifests use Composer path
repositories and a pnpm workspace; they do not contain a source-repository
absolute path.

Install, build, and test the generated project:

```bash
cd /tmp/peanut-admin-starter
composer install --working-dir backend
pnpm install --frozen-lockfile
php backend/tests/smoke.php
php backend/tests/auth-clients.php
pnpm typecheck
pnpm test
pnpm build
```

Run the complete clean-directory qualification from the source repository:

```bash
./scripts/verify-internal-starter
```

That verification creates the starter twice, compares the results, installs
both copies, and compares their generated lock files. It compiles a real Module
manifest under the starter's external PHP namespace, migrates a fresh MySQL
database, verifies two registered Tenant Clients with independent sessions and
cookies, runs frontend transport tests, builds Admin Web, starts the ThinkPHP
host and Vite preview, and checks the real HTTP responses.

## Boundary

The starter intentionally contains a fictional `example.greeting` Module and
generic fictional Client definitions. The public generator configures project
identity, Tenant Clients, and current first-party Modules; it does not generate
business CRUD, publish packages, upgrade an existing source tree, or create a
long-term compatibility promise. Application-specific Modules remain in their
own private or product repositories.
