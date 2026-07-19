# API Contract

[`openapi.yaml`](./openapi.yaml) is the OpenAPI 3.1.2 fact source for Peanut Admin Runtime operations. It defines separate tenant and platform audiences, typed target requests, stable operation identifiers, pagination, ETags, idempotency, and RFC 9457 Problem Details.

TypeScript declarations are generated at `packages/web/admin-core/src/generated/api.d.ts`. Backend routes are generated from each operation's `x-handler` and `x-permission`; both generated artifacts are checked for drift by `./scripts/check-openapi`.

All BIGINT identifiers cross the API boundary as decimal strings. Tenant requests obtain `tenant_id` from the validated session context, never from a request body.

## Implementation Status

All 75 P0 operations in the current OpenAPI document bind to concrete reference-host handlers. The three additional P1 account self-service operations are candidate capabilities and remain outside the qualified downstream-consumption baseline. `./scripts/check-openapi` verifies that each handler exists, accepts the declared path parameters, returns `think\Response`, and carries success status, body, and header metadata matching the generated route.

This statement applies only to operations classified in the current Runtime coverage ledger. A future operation is unavailable until its OpenAPI schema, concrete handler, authorization metadata, classification, and automated evidence land together. The unused fail-closed contract fallback classes do not publish an operation.
