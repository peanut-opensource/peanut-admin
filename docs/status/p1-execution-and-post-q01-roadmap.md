# P1 Execution Reality And Post-Q01 Roadmap

## Status

```text
state: draft
scope: current P1 execution and the next reusable capability waves
source_basis:
  - docs/status/p1-execution-baseline.md
  - docs/status/p1-downstream-module-readiness-plan.md
  - docs/status/runtime-operation-coverage.json
  - git history and current tree state
```

This document is a planning synthesis. It does not replace the execution
baseline or any task contract.

## What Has Already Landed

The current repository history already contains these executed P1 slices:

- `P1-B01` account self-service
- `P1-B02` effective access preview
- `P1-R01` operation atomicity primitives
- `P1-R02` external operation host kit
- `P1-W01` protected transport origin
- `P1-W03` workspace shell

These are real commits, not just written plans.

## What Is Still Open In The Current P1 Critical Path

The current tree does not yet contain the reusable settings or reference-code
packages, and the corresponding host modules are still missing:

- `packages/php/settings`
- `packages/php/reference-code`
- `backend/app/Modules/Peanut/Settings`
- `backend/app/Modules/Peanut/ReferenceCode`

So the remaining immediate P1 path is:

1. `P1-B03` minimal settings module.
2. `P1-B04` minimal reference-code module.
3. `P1-Q01` fixed-commit external-host qualification.

## Concurrency Rule

`P1-B03` and `P1-B04` can be prepared in parallel at the planning level, but
their implementation and generated artifacts must be integrated serially.

Reason:

- they both touch module manifests, runtime coverage, docs, and starter
  evidence;
- the downstream lock does not move until a single fixed-commit qualification
  finishes;
- `P1-Q01` is exclusive.

## Recommended Critical Path

```text
current implemented slices
  -> B03 settings
  -> B04 reference-code
  -> Q01 fixed-commit qualification
  -> downstream lock remains pinned until re-qualified
```

## Post-Q01 Capability Waves

After Q01, the next reusable capability work should be split into waves.

### Wave A: Identity And Access Expansion

- phone credentials
- multiple credentials per account
- password recovery
- member invitations
- MFA
- OIDC / SSO

### Wave B: Tenant Administration Ergonomics

- tenant domain resolution
- support sessions
- tenant plans and quotas
- positions
- temporary authorization
- visual menu preferences

### Wave C: Operational Infrastructure

- file and media management
- notifications and message center
- application error views
- scheduler and queue management
- import and export
- backup administration

### Wave D: Extension And Ecosystem

- extension lifecycle and signing
- code generator
- public packages
- full documentation site

### Wave E: Commercial Platform

- marketplace
- fleet control
- commercial licensing

## Planning Checkpoint

Once `B03`, `B04`, and `Q01` are closed, the next task plan should be
reissued as one contract per wave, with exact file whitelists and independent
acceptance gates.
