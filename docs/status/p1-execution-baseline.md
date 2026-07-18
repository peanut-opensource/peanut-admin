# P1 Execution Baseline

## Decision

P1 starts from repository commit
`c63e06e25e35855cfefab890d7ee43c6e0cf839d`. That commit records the
qualified P0 foundation and the separately qualified external-host path. It is
the planning input, not a new downstream-consumption lock.

The existing P0 Runtime is inherited. P1 adds market-usable administration and
developer capabilities without renaming, duplicating, or weakening P0 identity,
Tenant, membership, authorization, Module, audit, API, and Admin Web contracts.

At this baseline:

- 75 OpenAPI operations are classified as `p0`;
- no Runtime operation is classified as `p1`;
- no P1 dependency has been approved merely because a capability is listed here;
- no P1 commit is qualified for downstream consumption, package publication, a
  release tag, or a production-readiness claim.

## Ownership Boundary

Peanut Admin owns reusable administration infrastructure. Product applications
own their domain entities, workflows, calculations, fulfillment rules, and
operational state. A downstream application may consume the contracts below,
but its domain tables, APIs, permissions, pages, and examples do not move into
the Kernel, reusable packages, starter, or reference Modules.

P1 may provide:

- account self-service, credentials, invitations, and security controls;
- Tenant and platform administration enhancements;
- effective access inspection and time-bounded authorization;
- settings, reference-code, file, notification, job, and import/export
  infrastructure;
- signed integration credentials, Webhooks, extension delivery, generation,
  documentation, packaging, and upgrade tooling.

P1 does not provide application-specific catalogs, stock state, ordering,
fulfillment, settlement, customer programs, or other product-domain Runtime.

## Inherited P0 Capability

The following are complete P0 inputs and are not P1 rebuild tasks:

| Area | Inherited capability |
| --- | --- |
| Identity | Email credential, global uniqueness, account state, secure password hashing |
| Session | Tenant and platform audiences, rotating refresh, revocation, trusted context |
| Tenant | Tenant lifecycle, member lifecycle, explicit tenant selection and switching |
| Organization | Department tree, members, multiple roles |
| Authorization | Functional RBAC, typed targets, data policies, Provider contracts |
| Module | Manifest, migration ownership, installation, Tenant enablement, guards |
| Navigation | Manifest projection, permission-filtered menus, route contribution |
| Audit | Tenant, platform, authentication, and security event persistence and query |
| API | Versioned OpenAPI, Problem Details, request correlation, idempotency contracts |
| Web | Tenant and platform shells, protected transport, context lifecycle |
| Operations | CLI install, upgrade, health, backup, restore, recovery, and qualification gates |

Any P1 task that overlaps this table must identify a concrete missing behavior.
It must extend the current contract instead of creating a parallel model.

## Capability Classification

| Capability group | Classification | Entry condition |
| --- | --- | --- |
| Account profile, password change, device/session view | P1 wave 1 | No new dependency; security event and cross-audience revocation defined |
| Phone and multiple credentials | P1 wave 2 | Verification-channel and identifier-conflict decisions accepted |
| Password recovery | P1 wave 2 | One-time-token, channel, abuse-control, and support policy accepted |
| Member invitations | P1 wave 2 | Invitation state machine and delivery adapter accepted |
| MFA and OIDC | P1 wave 3 | Separate dependency and protocol decisions accepted |
| Tenant domain resolution and custom domains | P1 wave 3 | Verification, certificate, routing, and failure policy accepted |
| Tenant plan and quota | P1 wave 2 | Entitlement authority and enforcement ownership accepted |
| Platform support sessions | P1 wave 3 | Explicit consent, duration, scope, display, revocation, and audit accepted |
| Positions | P1 wave 2 | Display-only versus authorization semantics fixed |
| Effective access preview | P1 wave 1 | Existing RBAC and data-policy semantics remain authoritative |
| Temporary authorization | P1 wave 2 | Expiry, approval, conflict, revocation, and audit semantics fixed |
| Visual menu preferences | P1 wave 2 | Manifest remains the deployable source; tenant preferences cannot invent routes |
| Settings and reference codes | P1 wave 1 | Independent Module ownership and scope precedence fixed |
| File and media management | P1 wave 2 | Storage dependency and malware/content policy accepted |
| Notifications and message center | P1 wave 2 | Channel adapters, template ownership, retry, and retention accepted |
| Application error view | P1 wave 2 | Structured source, redaction, access, and retention fixed |
| Scheduler and queue management | P1 wave 2 | Runtime dependency and trusted task-context reuse accepted |
| Import and export | P1 wave 2 | File, task, authorization, row-error, and retention contracts accepted |
| Backup administration API | P1 wave 3 | External backup authority and restoration verification accepted |
| Extension lifecycle and signing | P1 wave 3 | Artifact, trust, compatibility, rollback, and uninstall contracts accepted |
| Code generator | P1 wave 3 | Generated ownership, upgrade boundary, and fixture contract accepted |
| API tokens and machine identity | P1 wave 2 | Audience, scope, rotation, expiry, and audit semantics accepted |
| Webhooks | P1 wave 2 | Signature, retry, idempotency, ordering, and delivery log accepted |
| Public packages and complete documentation | P1 wave 3 | Versioning, compatibility, release, and support policy accepted |
| Marketplace, fleet control, commercial licensing | P2 | Separate product and operations decision required |
| Product-domain Runtime | Excluded | Owned by downstream application Modules |

## Task Graph

```text
P1-B00 execution baseline
  -> P1-B01 account self-service
  -> P1-B02 effective access preview
  -> P1-B03 settings Module
  -> P1-B04 reference-code Module

P1-B01 -> P1-C01 multiple credentials -> P1-C02 recovery and invitations
P1-B02 -> P1-C03 temporary authorization and support-session design
P1-B03 + P1-B04 -> P1-C04 Admin Web contribution and generation contracts

dependency decisions -> file adapter -> media, import/export, notification attachments
dependency decisions -> task adapter -> scheduler, queue, import/export, delivery retry
credential decisions -> API tokens and machine identity -> Webhooks

all accepted slices -> P1-D01 clean install and upgrade
                   -> P1-D02 recovery, browser, security, and performance
                   -> P1-D03 fixed-commit review
```

Tasks sharing migrations, generated OpenAPI artifacts, Runtime coverage, fixed
database names, service names, or ports are integrated serially. Read-only
design review and disjoint source work may run in parallel, but aggregate gates
run exclusively.

## Required Task Contract

Every Runtime task must include all of the following before implementation:

1. Objective and non-goals.
2. Exact prerequisite commit.
3. Exact file whitelist and files that must not change.
4. Table fields, nullability, defaults, indexes, unique constraints, check
   constraints, state transitions, retention, and deletion behavior.
5. Migration owner and clean-install/upgrade/rollback evidence.
6. API method, path, request, response, OpenAPI schema, headers, and audience.
7. Permission keys and data-policy behavior. Self-only operations must derive
   the account from authenticated context and must not accept an account ID.
8. Audit/security event names, actor, target, before/after policy, and redaction.
9. Idempotency, concurrency, precondition, retry, and duplicate-request behavior.
10. Stable Problem Details codes without secret, token, or account-enumeration leaks.
11. A failing test added before behavior, a named Runtime coverage owner, focused
    checks, `./scripts/check`, and `git diff --check`.
12. A single independently reviewable commit and an explicit qualification stop line.

## P1-B01: Account Self-Service Contract

The first Runtime slice is tenant-audience account self-service. It is selected
because it closes a visible market gap, requires no new dependency, and remains
strictly self-scoped.

### Objective

- Read the authenticated account profile.
- Update `display_name` and `avatar_uri` for the authenticated account.
- Change the active email-password secret after verifying the current secret.
- Revoke all tenant and platform sessions for the account after a secret change.
- Expose the behavior in the existing account page.

Platform-audience self-service, device/session listing, phone credentials,
recovery, invitation, MFA, and external identity providers are later tasks.

### Data Contract

No new table is introduced.

| Table | Change |
| --- | --- |
| `pa_account` | Profile update changes `display_name`, nullable `avatar_uri`, and `updated_at`. Secret change increments `security_revision` and updates `updated_at`. |
| `pa_credential` | Active email credential receives a new `secret_hash`, reset failure state, new `secret_changed_at`, incremented `revision`, and updated timestamp. |
| `pa_tenant_session` | Active sessions for the account become `revoked` with reason `credential_changed`. |
| `pa_platform_session` | Active sessions for the account become `revoked` with reason `credential_changed`. |
| session token tables | Active tokens for revoked sessions become `revoked`; token material is never persisted or audited. |
| `pa_tenant_audit_event` | Records `account.profile.changed` and successful `account.password.changed` actions with context-derived actor and target identifiers. |
| `pa_auth_security_event` | Records `password_change_denied` or `password_changed` with redacted metadata. |

`display_name` is trimmed UTF-8 text from 1 to 120 characters. `avatar_uri` is
either null or an absolute `https` URI no longer than 512 characters. File
upload is not part of this task. A new password is 12 to 128 bytes, differs from
the current password, and is hashed by the existing password service.

### API Contract

| Operation | Contract |
| --- | --- |
| `getMyAccount` | `GET /api/v1/account`; authenticated tenant audience; no permission key; returns account profile and active credential metadata without hashes. |
| `updateMyAccount` | `PATCH /api/v1/account`; authenticated tenant audience; no account ID, no permission key; body contains `display_name` and `avatar_uri`; returns the updated profile. |
| `changeMyPassword` | `POST /api/v1/account/password`; authenticated tenant audience; no account ID, no permission key; body contains `current_password`, `new_password`; returns 204 and clears the current refresh cookie. |

Profile update is naturally repeatable and does not use the generic idempotency
store. Password change is intentionally non-idempotent: replay after success
fails current-secret verification. The service runs each write in one database
transaction. Account identity comes only from validated tenant context.

Problem codes are `ACCOUNT_PROFILE_INVALID`, `AVATAR_URI_INVALID`,
`CURRENT_PASSWORD_INVALID`, `NEW_PASSWORD_INVALID`, `PASSWORD_UNCHANGED`, and
`ACCOUNT_CREDENTIAL_UNAVAILABLE`. Error responses never return credential
identifiers, hashes, session tokens, or existence information for another account.

### File Whitelist

The implementation task may change only:

- `packages/php/kernel/src/Identity/SelfService/*`;
- `packages/php/kernel/tests/Unit/Identity/*`;
- `packages/php/kernel/tests/Integration/Identity/*`;
- `backend/app/controller/api/v1/AccountController.php`;
- `backend/app/middleware/TenantAccountRuntimeFactory.php`;
- `backend/tests/Http/AccountSelfServiceRuntimeTest.php`;
- `backend/tests/Integration/AccountSelfServiceIntegrationTest.php`;
- `docs/api/openapi.yaml` and `docs/api/schemas/auth.yaml`;
- generated OpenAPI route and TypeScript artifacts;
- `docs/status/runtime-operation-coverage.json`;
- `scripts/check-openapi` only to replace the fixed total with explicit P0/P1 totals;
- `frontend/src/pages/common/AccountPage.vue`;
- focused frontend tests for the account page.

Changing Kernel schema, Module manifests, product profiles, dependency locks,
starter templates, example domain code, or another administration resource is
outside this slice.

### Acceptance

- The three operations are classified as `p1` and have executable test ownership.
- Cross-account IDs cannot be supplied by any request shape.
- Wrong current password changes no state and records a denied security event.
- Successful profile update exposes no credential secret and records a tenant audit event with redacted evidence.
- Successful secret change revokes tenant and platform sessions, invalidates old
  access and refresh tokens, and never logs either password.
- Desktop and mobile account-page flows pass against a real backend.
- OpenAPI, Runtime coverage, unit, integration, security, browser, recovery,
  workspace, and aggregate checks pass without weakening P0 assertions.

## Dependency Gates

No dependency is added by P1-B01 or P1-B02. Every later direct dependency starts
with a decision that records exact version, license, official sources,
alternatives, adapter boundary, removal plan, and current security status.

The following capabilities are blocked until their decisions are accepted:

- phone delivery, email delivery, MFA, and external identity protocols;
- filesystem and object-storage abstraction;
- queue and scheduling Runtime;
- spreadsheet parsing and generation;
- notification transport and templating;
- extension signature verification and package installation.

No placeholder schema, adapter, package, or directory is reserved while a
capability is blocked.

## Qualification Stop Line

- Completing a slice proves only that slice on its resulting commit.
- The existing P0 downstream-consumption lock does not move.
- Downstream consumers must not adopt a P1 commit until a fixed-commit P1
  aggregate gate and independent review explicitly approve it.
- P1 work does not authorize package publication, a tag, a release, automatic
  deployment, production sizing, or a stable compatibility promise.
- A failed security, isolation, upgrade, recovery, browser, or supply-chain gate
  blocks integration; checks are fixed rather than skipped or weakened.
