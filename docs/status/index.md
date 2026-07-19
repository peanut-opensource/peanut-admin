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

## Runtime Operation Coverage

[`runtime-operation-coverage.json`](./runtime-operation-coverage.json) classifies
the current 78 OpenAPI operations as 75 P0 and 3 P1 operations, records their
concrete handlers and success schemas, and assigns each operation to an
executable test owner. `./scripts/check-runtime-coverage` rejects missing,
duplicate, stale, or unowned operations.

## P0 Qualification

- The complete aggregate Runtime gate passed on the tree fixed by commit
  `d26186dfb23af34c62c58b4da94fea77bd63d724`.
- The fixed-commit nine-role review found and closed one documentation
  reproducibility issue and has no remaining P0 finding.
- See the [P0 Runtime Qualification Review](../reviews/p0-runtime-qualification.md)
  and [P0 Candidate](../releases/p0-candidate.md) records.

P0 qualification does not create a release. On 2026-07-18, a separate decision
approved promotion to `dev` and exact-commit private downstream validation. Every
consumer must pin the resulting 40-character commit and record its integration
mapping. Tags, package publication, production claims, and public stable-release
claims remain unapproved.

## External Host Consumption

The subsequent [External Host Consumption Qualification](../reviews/external-host-consumption-qualification.md)
fixes commit `0ab02a9b735ba9f4c23509cb366b9bf04039ebf8` for exact-commit
private validation. It proves application-owned Module namespaces and layouts,
server-registered Tenant Clients, Client-scoped authentication and refresh, an
application-owned protected Web transport, and a real external-host starter.

This qualification does not broaden P0 into product business logic and does not
approve a public generator, package publication, release tag, production claim,
or later unqualified Runtime changes.

## Intentionally Not In P0

- Phone credentials, invitations, password recovery, MFA, and OIDC.
- File management, notifications, spreadsheets, queue management UI, and visual schedulers.
- Plugin marketplace and remote code installation.
- Group tenants, delegation, franchise collaboration, and cross-tenant business access.
- POS, mobile, mini-program, or application-specific domain modules.
- Public project generator, configurable CRUD generator, or long-term template upgrade contract.
- Package publication, release tags, or a claim of production-ready public release.

The repository checks are the authoritative evidence for completed implementation. Documentation never upgrades an unimplemented capability by description alone.

## P1 Execution

P1 planning starts from repository commit
`c63e06e25e35855cfefab890d7ee43c6e0cf839d` and is fixed by baseline commit
`957e7b6`. The first candidate slice implements three tenant-audience operations
for self-scoped account profile and password management. The canonical execution
order, task contracts, dependency gates, and stop lines are recorded in the
[P1 Execution Baseline](./p1-execution-baseline.md).

These P1 operations are implemented candidates, not qualified downstream
capabilities. P1 work does not change the external-host consumption lock above.
A later P1 commit remains unqualified for downstream consumption until a new
fixed-commit aggregate check and review approve it.
