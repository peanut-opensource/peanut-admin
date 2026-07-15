# Peanut Admin Developer Documentation

Peanut Admin is a reusable multi-tenant administration foundation. It combines reusable PHP and web packages, a ThinkPHP reference host, a Vue Admin Shell, module contracts, examples, and project checks in one public repository.

P0 is building an internal alpha foundation that downstream teams can extend safely. It is not yet a LikeAdmin-level finished product and does not contain product-specific store, warehouse, inventory, finance, or transaction logic.

## Start Here

- [Core Concepts](./core-concepts/): understand accounts, tenants, members, platform operators, departments, and typed business targets.
- [Architecture](./architecture/): understand package boundaries, module ownership, isolation, and composition.
- [Engineering Standards](./standards/): follow dependency, security, documentation, and implementation rules.
- [API Contract](./api/): track the OpenAPI 3.1 contract as it is implemented.
- [P0 Status](./status/): see what is implemented and what remains intentionally unavailable.

## Stable Principles

1. A tenant is the SaaS customer and data-isolation root. A store, warehouse, supplier, or project is a business target inside a tenant, not a tenant alias.
2. Login identity and tenant membership are separate: `Credential -> Account -> Tenant -> TenantMember`.
3. Platform operators use separate sessions, guards, APIs, and roles. Platform authority never implies tenant business access.
4. Functional permission answers whether an operation may be attempted. Data permission answers which records or typed targets it may affect.
5. Missing tenant context, module state, permission, provider, or operation declaration fails closed.
6. A module owns its schema, rules, repositories, migrations, APIs, permissions, and public contracts. Other modules do not read or write its tables directly.
7. Shared master data keeps one truth source and one identifier space. Ownership and scope decide who may view, use, or maintain each record.

## Current Runtime Status

The documentation and dependency contracts are available. Runtime packages, database schema, APIs, and Admin Shell behavior are delivered by later P0 tasks and must not be inferred from this site before their status becomes canonical.
