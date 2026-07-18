# Admin Web Composition

The reference Admin Web is one Vue 3 build with strictly separated tenant and platform workspaces. Reusable behavior lives in `@peanut-admin/admin-core` and `@peanut-admin/admin-shell`; final routing, pages, branding, and Module assembly live in `frontend`.

## Audience Clients

Create tenant and platform clients separately. Each client rejects the other API prefix, stores access tokens only in memory, attaches request IDs, includes the matching refresh cookie, and coordinates a single refresh rotation for concurrent 401 responses.

Every Tenant Client supplies a stable refresh scope such as `single-store-web:tenant`. The default browser coordinator combines Web Locks and `BroadcastChannel` so independent tabs of the same Client can consume one rotated access token without reusing the old refresh token. Different Client keys use different scopes and never coordinate or exchange tokens.

An application with its own OpenAPI schema uses the exported `createProtectedFetch()` with its own allowed-path predicate, credential-exchange predicate, and generated `openapi-fetch` Client. It does not need to reuse Peanut Admin's generated `paths` type or duplicate the authentication replay logic. Tests and non-browser hosts can inject `createMemoryRefreshCoordinator()`.

Non-idempotent requests are replayed only when they carry an `Idempotency-Key`.

## Module Contribution

```ts
import { defineAdminModule } from '@peanut-admin/admin-core'

export default defineAdminModule({
  key: 'example.work-item',
  routes: [{
    name: 'example.work-item.list',
    path: '/app/example-work-items',
    component: () => import('./WorkItemListPage.vue'),
    access: {
      moduleKey: 'example.work-item',
      permissionKeys: ['example.work-item.read'],
    },
  }],
  disposeOnTenantChange: true,
})
```

Remote component paths, `eval`, and runtime Plugin JavaScript are not supported in P0.

## Zero, One, And Many Targets

The operation target store is keyed by Module, protected resource, operation, target type, and cardinality.

- Zero candidates: disable the operation and show an empty scope state.
- One candidate: select it automatically for `one_required` operations.
- Several candidates: require one explicit selection for ordinary writes.
- Several readable candidates: allow a narrowed multi-selection and show an ownership column.
- Aggregate reads: show a scope summary and remain read-only.

The selection is only request input. The backend resolver and provider remain authoritative.

## Tenant Switch

Tenant switch disposes Module stores, menus, target selections, pending requests, and cached collections before the new context renders. Responses from an older tenant generation are ignored. Shell preferences may persist only when they contain no tenant or business data.

## Explicit Error States

The Shell provides dedicated states for forbidden access, missing resources, stale ETags, rate limiting, Module unavailability, service unavailability, and session expiry. It does not convert a denied or unavailable operation into an empty successful page.
