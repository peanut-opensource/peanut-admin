# P0 Candidate

## Status

Peanut Admin has a qualified P0 internal-alpha candidate at:

```text
d26186dfb23af34c62c58b4da94fea77bd63d724
```

The complete evidence and nine-role review are recorded in
[`P0 Runtime Qualification Review`](../reviews/p0-runtime-qualification.md).

This candidate is not production ready and has not been released. No `main`
branch, tag, GitHub Release, Composer package, npm package, or downstream
baseline is created or approved by this record.

## Candidate Capability

- ThinkPHP 8 reference Runtime and Vue 3 Admin Web using real HTTP and MySQL.
- Separate tenant and platform authentication, sessions, audiences, guards,
  workspaces, RBAC, and audit streams.
- Tenant membership, Department, Role, Permission, TenantModule, typed-target
  data permission, operation cardinality, and shared-master scope contracts.
- Three fictional Modules proving multi-target reads, single-target writes,
  shared-master visibility, Module ownership, and public-contract composition.
- 75 concrete P0 OpenAPI operations and generated TypeScript contracts.
- Reusable PHP and Web packages consumed through a reproducible internal
  starter.
- Executable documentation, security, browser, recovery, performance,
  dependency, license, and architecture gates.

## Qualification Snapshot

- 117 PHP unit tests and 25 Web unit tests passed.
- 82 MySQL integration tests passed.
- G-07 security qualification passed with zero skipped security tests.
- 26 desktop, mobile, and real full-stack browser tests passed.
- Clean install, Alpha/Beta backup and restore, and internal starter passed.
- All seven performance scenarios stayed below their p95 regression limits.
- 75 P0 Runtime operations and 0 P1 Runtime operations were classified.
- 484 third-party package licenses were inventoried; high-risk dependency,
  secret, architecture, and license gates passed.

## Not Included

P0 does not include phone login, invitations, recovery, MFA, SSO, files,
notifications, jobs UI, import/export, plugins, marketplace, public project or
CRUD generators, package publication, commercial control plane, POS/mobile
clients, or product-specific business Modules.

## Next Decision

An independent release decision must define the intended consumer, branch/tag
policy, package strategy, deployment target, production hardening, support
expectation, and downstream pinning rules. Until then, development may continue
on an approved next-phase branch, but this candidate must not be represented as
a public stable release or production baseline.
