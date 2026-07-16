# Testing

The stable repository entry point is:

```bash
./scripts/check
```

It builds the documentation and Admin Web, validates OpenAPI and Module manifests, runs architecture checks, PHP unit and MySQL integration tests, authorization security tests, browser tests, PHPStan, Deptrac, PHP-CS-Fixer, ESLint, TypeScript checks, Vitest, and production builds.

## Focused Commands

```bash
./scripts/test-unit
./scripts/test-integration
./scripts/test-security
./scripts/test-browser
./scripts/check-openapi
./scripts/check-architecture
./scripts/check-docs
```

Run the fictional cross-Module contract example with MySQL available:

```bash
PEANUT_INTEGRATION=1 php vendor/bin/phpunit \
  examples/module-contract/ExampleModuleContractTest.php
```

That example proves typed Project and Queue targets, one shared Reference identity space, a single-target write, a multi-target read, per-target policy publication, category-confusion denial, private-scope denial, and P0 bulk-write rejection.

## Security Test Rules

- A skipped security test is not qualification evidence.
- Tenant and platform audiences require separate negative tests.
- Lists and single-object actions must use the same provider semantics.
- Tests must include missing context, stale revision, disabled Module, wrong target type, cross-tenant identifiers, and shared-master scope denial.
- Browser state must be cleared on tenant switch; late responses from the previous tenant must not render.

Documentation examples are checked by `./scripts/verify-doc-examples`. The verifier binds prose markers to current source symbols, performs a temporary database install, and runs the Module tutorial.
