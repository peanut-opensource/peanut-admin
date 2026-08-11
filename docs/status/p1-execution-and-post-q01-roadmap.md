# P1 Execution Reality And Post-Q01 Roadmap

## Status

```text
state: active
scope: completed P1/Starter v1 history and the post-Q01 cross-product sequence
source_basis:
  - docs/status/p1-execution-baseline.md
  - docs/status/runtime-operation-coverage.json
  - docs/status/p1-post-q01-cross-product-capability-plan.md
  - git history and current tree state
```

This document is a navigation summary. It does not replace an executable task
contract or the fixed-commit rules in the cross-product plan.

## Execution Reality

PB09 and Peanut Admin v1.0.0 are complete. Earlier P1, Starter v1 and package
publication records remain historical evidence; their old candidate order is
not an instruction to repeat a qualification or publish an earlier alpha.

P1-WF01 has an accepted product-neutral Workflow contract fixed by
`abeb5afa32dee353b13debe08b23575173979d90`,
`f2f4a21d942f6a24e1ed673c67dfb6a72c531c3d` and
`a2a13b633cfdfdaa14aca5f1d917e4f6865597c2`. Forty implementation candidate
files exist locally but are not committed. WF01 qualification, its obsolete
Alpha.5 publication path and application consumption have not occurred.

The accepted Workflow design remains reusable: it owns versioned definitions,
Tenant instances, human work items and append-only events while using existing
identity, Tenant, authorization, File/Media, Task/Job, Notification/SMS and
audit authorities. It contains no product workflow or editor implementation.

## Post-Q01 Cross-Product Critical Path

The canonical ordering, ownership, API/security boundaries, dependency gates,
test owners and stop lines are now fixed by the
[P1 Post-Q01 Cross-Product Capability Plan](./p1-post-q01-cross-product-capability-plan.md).

```text
CAP00 planning repair
  -> CAP01 HumanWorkflow
  -> CAP02 ArtifactRevision
  -> CAP03 EntitlementQuota
  -> CAP04 Collaboration
  -> CAP05 fixed-commit qualification
  -> CAP06 exact-commit private downstream adoption
  -> separate publication approval, if any
```

HumanWorkflow may precede ArtifactRevision because it depends only on an opaque
subject-revision port and stores an immutable key/digest pin. ArtifactRevision
later implements that authority; Collaboration depends on ArtifactRevision and
must publish a finalized immutable revision before HumanWorkflow can approve
it. If the port boundary cannot be preserved, work stops and ArtifactRevision
moves first through a separate planning correction.

## Active Stop Line

The frozen WF01 target `peanut-admin/core@0.1.0-alpha.5` predates the completed
v1.0.0 baseline and is no longer a valid publication target. The next task is a
documentation-only CAP01 contract reconciliation that fixes the exact v1.0.0
source/artifact baseline, candidate prerequisite, package versioning rule,
whitelist and deferred verification while preserving the existing candidate.

Until that correction lands:

- do not run WF01 or aggregate tests;
- do not commit, discard or rewrite the forty WF01 candidate files;
- do not install a dependency;
- do not publish a Composer/npm package, tag or Release;
- do not start ArtifactRevision, EntitlementQuota, Collaboration or application
  consumption.

Qualification, private downstream adoption and public publication are separate
decisions. A later candidate does not move the v1.0.0 compatibility baseline or
any downstream lock without its own fixed-commit evidence and approval.
