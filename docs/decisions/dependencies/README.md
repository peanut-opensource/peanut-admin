# P0 Dependency Decisions

This directory is the canonical dependency decision record for Peanut Admin P0.

The machine-readable registry is [`p0-dependencies.json`](p0-dependencies.json). It records every accepted and deferred item with its exact reviewed version, installation constraint, direct-dependency status, license, purpose, alternatives, adapter boundary, exit plan, security status, and official sources.

## Accepted Baseline

| Area | Decision |
| --- | --- |
| PHP host | PHP 8.3, ThinkPHP 8.1.4, Think ORM 4.0.51, Think Migration 3.1.1 |
| PHP quality | PHPUnit 12.5.31, PHPStan 2.2.5, Deptrac 4.6.2, PHP CS Fixer 3.95.15 |
| Structured manifests | Opis JSON Schema 2.6.0; hand-written schema parsing is prohibited |
| Admin Web | Vue 3.5.39, Vite 8.1.4, TypeScript 5.9.3, Element Plus 2.14.3, Pinia 4.0.2, Vue Router 5.2.0 |
| Web quality | Vitest 4.1.10, Playwright 1.61.1, ESLint 10.7.0, vue-tsc 3.3.7 |
| API contract | OpenAPI 3.1 validated by Redocly CLI and consumed through openapi-typescript plus openapi-fetch |
| Documentation | VitePress 1.6.4 |
| Development services | MySQL 8.4.10 and Valkey 9.1.0 Alpine |
| Supply chain | Composer/pnpm audits and license inventories, Gitleaks 8.30.1, GitHub dependency review |

TypeScript intentionally remains on the 5.9 line because the accepted `openapi-typescript` 7.13.0 release declares a TypeScript 5 peer dependency. A newer major number is not, by itself, a valid reason to break a verified toolchain.

Valkey is accepted as the P0 development cache because it provides the required open-source RESP-compatible server. Cache access must remain behind Kernel cache ports and cache data must never become authoritative.

## Explicitly Deferred

P0 does not install or create speculative abstractions for filesystem storage, queue management UI, spreadsheet import/export, notifications, Plugin runtime or marketplace, MFA, or OIDC. Each requires an approved use path and a new dependency decision before installation.

## Installation Boundary

P0-A02 approves decisions only. It does not create `composer.json`, `package.json`, or lockfiles and does not install dependencies. P0-A03 may install only the accepted documentation toolchain. P0-A04 may install the accepted workspace dependencies and must record lock evidence without changing these decisions silently.

Run:

```bash
./scripts/check-dependency-decisions
```

The check fails when a mandatory decision is missing, a P0 deferral is promoted implicitly, or an accepted record contains an unpinned or placeholder version.
