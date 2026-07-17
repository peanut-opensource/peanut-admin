# API Contract

[`openapi.yaml`](./openapi.yaml) is the OpenAPI 3.1.2 fact source for Peanut Admin P0. It defines separate tenant and platform audiences, typed target requests, stable operation identifiers, pagination, ETags, idempotency, and RFC 9457 Problem Details.

TypeScript declarations are generated at `packages/web/admin-core/src/generated/api.d.ts`. Backend routes are generated from each operation's `x-handler` and `x-permission`; both generated artifacts are checked for drift by `./scripts/check-openapi`.

All BIGINT identifiers cross the API boundary as decimal strings. Tenant requests obtain `tenant_id` from the validated session context, never from a request body.

## Implementation Status

All 75 operations in the current P0 OpenAPI document bind to concrete reference-host handlers. `./scripts/check-openapi` verifies that each handler exists, accepts the declared path parameters, returns `think\Response`, and carries success status, body, and header metadata matching the generated route.

This statement applies only to operations present in the current P0 document. A future operation is unavailable until its OpenAPI schema, concrete handler, authorization metadata, and automated evidence land together. The unused fail-closed contract fallback classes do not publish an operation.
