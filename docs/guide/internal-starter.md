# Internal Starter

The P0 internal starter is a fixed integration fixture. It proves that a clean
host can consume the current PHP and Web packages through their public package
roots. It is not the public generator planned for a later phase.

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

## P0 Boundary

The starter intentionally contains only a fictional `example.greeting` Module
and generic fictional Client definitions. It does not provide configurable template variables, CRUD generation,
external package publication, automatic source upgrades, or a long-term
compatibility contract. Application-specific Modules remain in their own
private or product repositories.
