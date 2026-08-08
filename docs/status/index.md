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

The historical P1-PKG01 package-boundary candidate first exposed the same
Runtime through two installable boundaries: `peanut-admin/core` for PHP and
`@peanut-admin/admin` for Admin Web, both at `0.1.0-alpha.1`. Internal domain
directories remain ownership boundaries, not packages. P1-PKG03 below replaces
that unpublished version as the current publication candidate; publication,
release, stable compatibility, and downstream lock movement remain separate
decisions.

The [P1-PKG02 Alpha Publication Contract](./p1-pkg02-alpha-publication-contract.md)
fixes `b84b8876cf24e7b749f0e79ab95053e772c922e7` as the package source
candidate. Its retained R32-R38 browser remediation passed all 46 declared
Playwright tests; R39 passed recovery, clean-install, and internal Starter
verification; R40 passed all seven performance scenarios; and R41-R44
completed the remaining fixed-tree groups through explicit no-repeat resume
contracts. The
[alpha publication qualification](../reviews/p1-pkg02-alpha-publication-qualification.md)
records passing fixed-tree and package-content evidence for both projections.
No registry version, Composer split repository, tag, or Release is authorized
until registry ownership, credentials, provenance, immutable rollback, and
isolated registry-consumer evidence are recorded.

The [P1-PKG02 publication approval record](../decisions/releases/p1-pkg02-publication-approval.md)
fixes the intended public repositories, package names, alpha dist-tag, immutable
tag, projection digests, publication order, and rollback policy. Its state is
`preflight-open`; npm scope ownership, Packagist ownership, the generated split
repository, publication workflow, and registry consumer probes remain pending,
so `publication_authorized` is still false.

The post-`alpha.1` [P1-OVR01 Admin Web Override Registry Contract](./p1-ovr01-admin-web-override-registry-contract.md)
defines the first standard application override boundary for services,
components, pages, and route loaders. It is an unqualified candidate task and
does not change the fixed publication source or downstream-consumption lock.
The candidate implementation provides one typed, fail-closed build-time
registry with exact contract-version matching and immutable resolution source
metadata; it remains unqualified and is not approved for downstream use.

The [P1-OVR02 PHP Service Override Registry Contract](./p1-ovr02-php-service-override-contract.md)
defines the matching framework-independent interface-to-implementation
selection boundary for `peanut-admin/core`. Host container wiring is explicitly
deferred until a real application service slot is migrated.
The candidate implementation exposes immutable resolutions, bindings, and
redacted diagnostics with exact interface and contract-version enforcement; it
remains unqualified and has no Runtime wiring.

The [P1-OVR03 SMS Provider Host Consumption Contract](./p1-ovr03-sms-provider-host-consumption-contract.md)
defines the first real ThinkPHP Host consumption path for the PHP registry. It
moves SMS provider selection to one declared service slot with a disabled
package default and one application-owned override list. The accepted task is
implemented as a candidate-only reference Host chain and does not authorize
package publication or application consumption.

The [P1-OVR04 Admin Shell Host Consumption Contract](./p1-ovr04-admin-shell-host-consumption-contract.md)
defines the matching first Web Host chain. It moves tenant/platform workspace
shell selection to one package-owned resolver slot and one application-owned
override list. The implemented reference Host chain remains candidate-only and
does not authorize npm publication or Peanut Admin application migration.

The [P1-OVR05 Override Host Consumption Qualification Contract](./p1-ovr05-override-host-qualification-contract.md)
fixes the combined post-`alpha.1` source candidate and assigns one consolidated
PHP behavior group, Web behavior group, and Web public-type group. Qualification
passed: PHP completed 20 tests and 30 assertions, Web behavior completed 10
tests, and both the public npm package and reference Host passed typecheck. The
[qualification record](../reviews/p1-override-host-consumption-qualification.md)
carries no publication or downstream-consumption authority.

The first P1-OVR05 Q3 type group exposed one control-flow narrowing error in the
Web registry. [P1-OVR05-R01](./p1-ovr05-r01-web-type-narrowing-remediation-contract.md)
authorizes only the fail-closed `Map.get()` expression repair and one Q3 rerun;
the Q3 rerun passed and the earlier Q1 and Q2 groups remained sealed.

The [P1-CL01 UI-Neutral Client Transport Contract](./p1-cl01-ui-neutral-client-transport-contract.md)
defines `./client`, `./client/nuxt`, and `./client/uniapp` as three subpaths of
the existing `@peanut-admin/admin` package. It is an accepted candidate task for
shared request/session behavior and does not add a third public package or
authorize Peanut Admin application migration.

The current candidate implements the core state machine and its Nuxt and UniApp
transport adapters, with focused tests and package subpath registration. The
[Alpha.2 publication qualification](../reviews/p1-pkg03-alpha2-publication-qualification.md)
records the passing retained aggregate groups, Q04 performance evidence,
repository tail guards, and exact Composer/npm projection digests. Alpha.2 is
qualified and published as an alpha release; stable compatibility and
downstream-consumption locks remain separate decisions.

The [Alpha.2 publication approval record](../decisions/releases/p1-pkg03-alpha2-publication-approval.md)
records completed, authorized publication from source commit
`b0dc376c2147b98522764486342c9525fe5678ce` and generated-only Composer split
commit `330e76787ba754e1c7c11c2204c1c7f1e9560bb1`. Composer
`peanut-admin/core@0.1.0-alpha.2` and npm
`@peanut-admin/admin@0.1.0-alpha.2` passed clean PHP 8.3 and npm consumer
probes, with the recorded 604-file/10-PSR-4-root and 14-export projections.
The [GitHub prerelease](https://github.com/peanut-opensource/peanut-admin/releases/tag/v0.1.0-alpha.2),
[Packagist package](https://packagist.org/packages/peanut-admin/core), and
[npm package](https://www.npmjs.com/package/@peanut-admin/admin/v/0.1.0-alpha.2)
are the published destinations; `publication_authorized` is true.

Post-publication operations record two limits: npm `alpha` and `latest` both
point to Alpha.2, and Packagist is currently not auto-updated. The Packagist
condition does not block Alpha.2 consumption.

The [P1-PKG03 Alpha.2 Candidate Contract](./p1-pkg03-alpha2-candidate-contract.md)
defined the `0.1.0-alpha.2` fixed candidate containing the qualified override
Host chains and the three client subpaths. Its versioning and qualification
gates are historical evidence for the completed approval record; stable
compatibility and downstream-consumption decisions remain separate.

The [P1-PKG04 Alpha.2 Projection Workflow Contract](./p1-pkg04-alpha2-projection-workflow-contract.md)
adds a manually dispatched, read-only preflight for the fixed Alpha.2 candidate.
It regenerates the Composer and npm projections, checks their package metadata,
public boundaries, file lists, and qualified SHA-256 digests. Its pre-publication
stop line is historical; the completed approval record above captures the actual
publication result.

The [P1-PKG05 Admin Web Alpha.3 Classic Type Resolution Contract](./p1-pkg05-alpha3-classic-types-resolution-contract.md)
authorizes a metadata-only npm correction after a real TypeScript `node`
resolver consumer found that Alpha.2 lacked `typesVersions` mappings for its
fourteen public subpaths. The task leaves Runtime behavior and Composer Alpha.2
unchanged and requires a clean classic-resolution consumer before publication.
Its first focused consumer also exposed one ES2020 library mismatch in request
ID normalization; the corrected contract authorizes the single equivalent
regular-expression replacement before the consumer gate is restarted.

Implemented P1 operations are candidates, not qualified downstream capabilities.
The Reference Codes implementation is development-only and remains outside the
qualified downstream lock. P1 work does
not change the external-host consumption lock above. A later P1 commit remains
unqualified for downstream consumption until a new fixed-commit aggregate check
and review approve it.
