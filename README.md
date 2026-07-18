# Peanut Admin

Peanut Admin is an open-source, multi-tenant administration foundation built for reusable management applications.

The project provides a P0 candidate foundation with:

- reusable PHP and web packages;
- a ThinkPHP 8 reference backend;
- a Vue 3, TypeScript, Vite, and Element Plus admin shell;
- a fixed internal starter, documentation, fictional examples, and engineering checks.

## Current Status

The original P0 sequence reached the historical D04 qualification commit `f351a21`, but a second-wave review found that contract and fixture evidence did not sufficiently prove a real HTTP Runtime, complete P0 handlers, non-intercepted full-stack E2E, or a consumable internal starter. That commit remains historical evidence and is not a qualified Runtime baseline.

The remediation history contains implementation and evidence for `PA-P0-R00` through `PA-P0-R07`, followed by the revised developer-guide and recovery gates. The aggregate D04 gate and fixed-commit D05 nine-role review qualify the Runtime tree fixed at `d26186dfb23af34c62c58b4da94fea77bd63d724` as a P0 internal-alpha foundation.

On 2026-07-18, the qualified candidate was approved for promotion to `dev` and pinned private downstream validation. A downstream project must record the exact 40-character commit and integration mapping; a branch name is not a dependency lock. This approval is not a production-readiness statement, tag, GitHub Release, or Composer/npm package publication. See `docs/status/index.md` for current implementation evidence.

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
