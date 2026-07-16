# Authentication And Trusted Context

P0 implements one global email-password credential per login identifier. Authentication resolves an `Account`; authorization happens only after selecting the correct audience and membership.

## Tenant Flow

```text
Credential
-> Account
-> active Tenant choice
-> active TenantMember
-> TenantSession
-> TenantContext
```

An Account may have memberships in several tenants. A login that resolves several active memberships returns tenant choices instead of guessing. Tenant selection creates a new tenant-bound session. A request-body or query-string `tenant_id` never establishes the tenant context.

Tenant switching uses a short-lived challenge and creates a new session. The Admin Web clears tenant stores and rejects late responses from the previous tenant generation.

## Platform Flow

Platform operators use a separate `PlatformOperator`, `PlatformSession`, refresh cookie, API prefix, guard, context, roles, and audit stream. Platform authority can manage tenant lifecycle and TenantModule state, but it does not imply access to tenant business records.

The two refresh cookies are:

```text
__Host-pa_tenant_refresh
__Host-pa_platform_refresh
```

Both are `Secure`, `HttpOnly`, `SameSite=Lax`, and use `Path=/`. Access tokens stay in memory. The web clients reject requests sent through the wrong audience client.

## Session Validation

A validated tenant session binds Account, Tenant, TenantMember, client key, security revisions, expiration, and audience. Suspending or closing an Account, Tenant, or membership invalidates future validation. Refresh tokens rotate; reuse revokes the session family and records a security event.

The trusted HTTP context contains identifiers derived by the server plus a request ID. It does not carry a mutable global current business target. Typed targets belong to each operation.

## Async Work

Asynchronous handlers accept only a signed trusted envelope and revalidate authorization at execution time. A queued action cannot reuse stale browser authority, silently change audience, or infer a tenant from payload data.
