# API Contract

[`openapi.yaml`](./openapi.yaml) is the OpenAPI 3.1.2 fact source for Peanut Admin P0. It defines separate tenant and platform audiences, typed target requests, stable operation identifiers, pagination, ETags, idempotency, and RFC 9457 Problem Details.

TypeScript declarations are generated at `packages/web/admin-core/src/generated/api.d.ts`. Backend routes are generated from each operation's `x-handler` and `x-permission`; both generated artifacts are checked for drift by `./scripts/check-openapi`.

All BIGINT identifiers cross the API boundary as decimal strings. Tenant requests obtain `tenant_id` from the validated session context, never from a request body.

## Implementation Status

The contract includes operations whose reference-host business handler is not yet implemented. A generated route bound to a `ContractController` returns `503 API_OPERATION_UNAVAILABLE` by design. Generated types and routes prove contract consistency, not handler completeness. Authentication, organization administration, typed-target examples, and the Admin Shell have dedicated runtime tests; consult [P0 Status](../status/) before depending on another operation.
