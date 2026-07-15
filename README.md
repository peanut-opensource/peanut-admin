# Peanut Admin

Peanut Admin is an open-source, multi-tenant administration foundation built for reusable management applications.

The project will provide:

- reusable PHP and web packages;
- a ThinkPHP 8 reference backend;
- a Vue 3, TypeScript, Vite, and Element Plus admin shell;
- project scaffolding, documentation, examples, and engineering checks.

## Current Status

The repository is at P0-A01: governance and repository initialization only.

No backend, frontend, package, template, example, database schema, API, or management page has been implemented yet.

## Principles

- Tenant and platform identities remain separate.
- Functional permission and data authorization remain separate.
- Modules own their data and expose public contracts.
- Missing context, permission, provider, or declaration fails closed.
- Product-specific business logic does not belong in this repository.
- Dependencies require an accepted decision before installation.

## Documentation

Start with [the documentation index](docs/README.md).

## Development

Run the current repository checks:

```bash
./scripts/check
```

The `dev` branch is the active development branch. Runtime implementation proceeds through small, sequential P0 tasks.

## License

Apache License 2.0. See [LICENSE](LICENSE) and [NOTICE](NOTICE).
