# P1 Execution Reality And Starter v1 Roadmap

## Status

```text
state: active
scope: current P1 execution and the Starter v1 reusable capability waves
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

So the remaining immediate integration path is:

1. `P1-B03` minimal settings module.
2. `P1-B04` minimal reference-code module.
3. Integrate the accepted slices into one current Starter v1 candidate line.

Q01 is not a prerequisite for continuing unrelated reusable capabilities. It
is the concentrated qualification task after the complete Starter v1 candidate
commit has been fixed.

## Concurrency Rule

`P1-B03` and `P1-B04` can be prepared in parallel at the planning level, but
their implementation and generated artifacts must be integrated serially.

Reason:

- they both touch module manifests, runtime coverage, docs, and starter
  evidence;
- the downstream lock does not move until a single fixed-commit qualification
  finishes;
- a later `P1-Q01` run is exclusive.

## Recommended Critical Path

```text
current implemented slices
  -> B03 settings
  -> B04 reference-code
  -> selected Starter v1 capabilities
  -> Q01 fixed-commit qualification of the complete Starter v1 candidate
  -> downstream lock remains pinned until re-qualified
```

## Reusable Capability Waves

These waves are planning inputs, not a declaration that every listed capability
belongs in Starter v1. The project plan selects the Starter v1 subset and may
develop those slices before the final Q01 qualification. Each slice uses
focused verification; the complete fixed candidate receives the aggregate gate.

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

Wave E remains outside Starter v1 and requires a separate product decision.

## Planning Checkpoint

After `B03` and `B04` are integrated, the project should issue one bounded
contract per Starter v1 capability slice, with exact file whitelists and
risk-proportionate acceptance checks. When the selected Starter v1 capabilities
are integrated, fix one candidate commit and run Q01. Until Q01 and separate
approval pass, the downstream lock remains
`0ab02a9b735ba9f4c23509cb366b9bf04039ebf8`.
