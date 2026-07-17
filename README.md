# Peanut Admin

Peanut Admin is an open-source, multi-tenant administration foundation built for reusable management applications.

The project provides a P0 candidate foundation with:

- reusable PHP and web packages;
- a ThinkPHP 8 reference backend;
- a Vue 3, TypeScript, Vite, and Element Plus admin shell;
- a fixed internal starter, documentation, fictional examples, and engineering checks.

## Current Status

The original P0 sequence reached the historical D04 qualification commit `f351a21`, but a second-wave review found that contract and fixture evidence did not sufficiently prove a real HTTP Runtime, complete P0 handlers, non-intercepted full-stack E2E, or a consumable internal starter. That commit remains historical evidence and is not a qualified Runtime baseline.

The remediation history now contains implementation and evidence for `PA-P0-R00` through `PA-P0-R07`, followed by the revised developer-guide and recovery gates. A candidate becomes qualified only after a fresh aggregate D04 run and a fixed-commit D05 nine-role review are both recorded against the resulting tree.

Until those records exist and a separate release decision is made, do not describe Peanut Admin as P0 complete, production ready, released, or suitable as a downstream baseline. See `docs/status/index.md` for current implementation evidence.

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

The `dev` branch is the collaboration branch. A task is complete only after its current acceptance checks pass on a clean commit; commit subjects and historical qualification results are evidence, not release approval.

## License

Apache License 2.0. See [LICENSE](LICENSE) and [NOTICE](NOTICE).
