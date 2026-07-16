# API Contract

[`openapi.yaml`](./openapi.yaml) is the OpenAPI 3.1.2 fact source for Peanut Admin P0. It defines separate tenant and platform audiences, typed target requests, stable operation identifiers, pagination, ETags, idempotency, and RFC 9457 Problem Details.

TypeScript declarations are generated at `packages/web/admin-core/src/generated/api.d.ts`. Backend routes are generated from each operation's `x-handler` and `x-permission`; both generated artifacts are checked for drift by `./scripts/check-openapi`.

All BIGINT identifiers cross the API boundary as decimal strings. Tenant requests obtain `tenant_id` from the validated session context, never from a request body.
