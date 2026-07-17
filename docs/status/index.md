# P0 Status

P0 produces an internal alpha foundation that is safe to extend. It does not claim the breadth or release maturity of a finished commercial admin framework.

## Available

- Clean public repository and Apache-2.0 governance baseline.
- Canonical dependency decisions and dependency policy.
- Reusable PHP and web packages with enforced dependency boundaries.
- Identity, Tenant, membership, Department, platform, audit, session, and trusted-context schema and services.
- Tenant and platform authentication with separate audiences and rotating refresh tokens.
- Functional RBAC, typed-target data permission, shared-master scope contracts, and security tests.
- Module manifest compiler, migration ownership, TenantModule runtime, and fictional example Modules.
- OpenAPI artifacts, reference Admin Shell, and desktop/mobile browser acceptance tests.
- CLI environment preflight, first install, ProductProfile application, local upgrade, migration checksums, and health checks.
- Buildable developer guide with executable installation and Module examples.
- Verified reference backup, restore-to-new-database, clean-install, and Alpha/Beta recovery drills.
- Concrete handlers and typed success contracts for all 75 current P0 OpenAPI operations.
- Real MySQL, ThinkPHP, and Vite full-stack browser evidence with no `/api/**` interception.
- Fixed internal starter with reproducible clean-directory install, build, start, and test evidence.

## Remaining P0 Gates

- Recovery and clean-install requalification against the current Runtime and internal starter.
- Final aggregate Runtime gate.
- Nine-role Runtime review and candidate qualification report.

## Intentionally Not In P0

- Phone credentials, invitations, password recovery, MFA, and OIDC.
- File management, notifications, spreadsheets, queue management UI, and visual schedulers.
- Plugin marketplace and remote code installation.
- Group tenants, delegation, franchise collaboration, and cross-tenant business access.
- POS, mobile, mini-program, or application-specific domain modules.
- Public project generator, configurable CRUD generator, or long-term template upgrade contract.
- Package publication, release tags, or a claim of production-ready public release.

The repository checks are the authoritative evidence for completed implementation. Documentation never upgrades an unimplemented capability by description alone.
