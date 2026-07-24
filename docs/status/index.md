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
the current 139 OpenAPI operations as 75 P0 and 64 P1 operations, records their
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
`957e7b6`. The current candidate slices implement three tenant-audience
operations for self-scoped account profile and password management and one
permission-gated operation for current Tenant-member effective-access
inspection. The canonical execution order, task contracts, dependency gates,
and stop lines are recorded in the [P1 Execution Baseline](./p1-execution-baseline.md).

The active minimum capability sequence for reusable downstream Module
development is recorded in the
[P1 Downstream Module Readiness Plan](./p1-downstream-module-readiness-plan.md).
It adds operation atomicity, external-host composition, reusable Web Runtime
and Shell behavior, minimal settings/reference-code infrastructure, and a new
fixed-commit qualification gate without moving application-domain logic into
Peanut Admin.

The first independently executable Web security slice is defined by the
[P1-W01 Protected Transport Origin Contract](./p1-w01-protected-transport-origin-contract.md).
It prevents a protected client from attaching credentials outside its
configured API origin while preserving existing audience and refresh behavior.

P1-R01 is the first backend composition candidate. It supplies savepoint-aware
PDO transactions, scoped idempotency lease rejection, typed audit outcomes,
and a failure-injection harness for external Module commands. The external
host continues to own domain and outbox schemas; this candidate has no Runtime
operation or downstream qualification.

The P1-R02 backend composition candidate is fixed by the
[P1-R02 External Operation Host Kit Contract](./p1-r02-external-operation-host-kit-contract.md).
It composes trusted context, Module availability, existing functional and data
authorization, and R01 atomic commands while leaving application namespaces,
API prefixes, OpenAPI, generated types, domain schema, and outbox ownership in
the external host. Its fictional Module proves list, detail, create, update,
status, Tenant isolation, typed targets, idempotency, and failure rollback
without adding a generic domain engine or a reference-host operation.

The reusable administration layout slice is fixed by the
[P1-W03 Workspace Shell Contract](./p1-w03-workspace-shell-contract.md). It
defines host-owned branding, strict Tenant/platform presentation separation,
desktop Sidebar and mobile Drawer behavior, identity and breadcrumb display,
P1-W02 command delegation, explicit status views, accessibility checks, and
the candidate-only stop line.

The execution reality and the next reusable capability waves are summarized in
[P1 Execution Reality And Post-Q01 Roadmap](./p1-execution-and-post-q01-roadmap.md).

The current backend administration candidate is fixed by the
[P1-B03 Minimal Settings Module Contract](./p1-b03-minimal-settings-contract.md).
It defines reusable first-party settings packages, trusted Module-owned typed
definitions, explicit target/Tenant/deployment precedence, secret redaction,
optimistic concurrency, six platform/Tenant Runtime operations, Tenant Web
behavior, and a candidate-only stop line. The candidate remains subject to its
focused and later milestone qualification gates.

The minimal reusable code-set slice is fixed by the
[P1-B04 Minimal Reference Codes Module Contract](./p1-b04-minimal-reference-codes-contract.md).
It defines trusted Module-owned sets, Tenant-isolated immutable code identity,
effective as-of options, optimistic concurrency, Tenant Web behavior, and a
candidate-only stop line without reusing the fictional shared-master fixture.

The next Starter v1 development slice is fixed by the
[File And Media Core Contract](./starter-v1-c02-file-media-contract.md). It
defines a Tenant-private metadata model, a reusable storage-provider boundary,
a local development adapter outside the public Web root, upload and private
download policy, optimistic archive, audit evidence, standard Admin Web
behavior, and internal starter integration without adding a third-party
dependency or claiming production object-storage readiness.

The current Stage C integration candidate composes the accepted Import/Export,
Integration Security, and Ops Console package contracts into the reference
Host, typed OpenAPI artifacts, Runtime coverage ledger, standard Admin Web, and
fixed starter. Import/Export depends on File/Media and Task/Job; Integration
Security remains a selectable Tenant Module; the platform Ops Console is an
always-on, fail-closed `standard-admin` workbench. The resulting 139-operation
ledger contains 75 P0 and 64 P1 operations. These statements describe the
candidate tree only and do not establish qualification, publication, release,
or downstream consumption approval.

Implemented P1 operations are candidates, not qualified downstream capabilities.
The Reference Codes implementation is development-only and remains outside the
qualified downstream lock. P1 work does
not change the external-host consumption lock above. A later P1 commit remains
unqualified for downstream consumption until a new fixed-commit aggregate check
and review approve it.
