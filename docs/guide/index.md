# Developer Guide

This guide describes the P0 runtime that exists in this repository. It separates reusable package contracts, the reference host, and the fictional example modules so downstream projects can adopt only the layers they need.

## Recommended Reading Order

1. [Install the reference runtime](./installation.md).
2. Review [authentication and trusted context](./authentication.md).
3. Learn [functional and data authorization](./authorization.md).
4. Understand [typed targets](../reference/typed-targets.md) and [shared master scope](../reference/shared-master.md).
5. Build a capability with the [Module tutorial](./module-development.md).
6. Compose routes with the [Admin Web guide](./admin-web.md).
7. Use the [testing](./testing.md), [upgrade](./upgrade.md), and [troubleshooting](./troubleshooting.md) runbooks.

## Runtime Boundary

P0 is a reusable foundation and reference implementation. It provides identity, tenant membership, platform identity, RBAC, data permission contracts, Module composition, typed targets, an Admin Shell, installation and local upgrade workflows, and executable examples.

P0 does not provide domain-specific commerce, inventory, finance, customer, or transaction modules. It also does not yet claim public production release qualification. Recovery, performance, supply-chain, and final runtime qualification remain separate gates.

The OpenAPI document is broader than the currently implemented controllers. Operations handled by a `ContractController` intentionally return `503 API_OPERATION_UNAVAILABLE`; do not treat a generated operation as proof that its business handler exists.
