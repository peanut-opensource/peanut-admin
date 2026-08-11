# P1-WF01 Configurable Workflow Runtime Contract

## Status

```text
task: P1-WF01
state: implementation-ready
prerequisite_commit: 7fbd445d8fa547830b7782a7ac147d9ed414e0fd
public_boundary: peanut-admin/core
target_candidate: 0.1.0-alpha.5
dependency_change: none
reference_host_operation: none
test_owner: P1-WORKFLOW-RUNTIME-001
qualification: required before publication or downstream consumption
```

This contract is the first executable decision derived from the read-only
media-resource-management evidence. That evidence establishes a need for
versioned approval graphs, immutable submitted subject revisions, human work
items, attachment references, and an append-only action history. It does not
authorize copying the legacy source, fixed three-review fields, weak access
checks, serialized history payloads, media product names, or customer data.

The existing `peanut-admin/core` package remains the only public PHP boundary.
Workflow is an internal source directory and PSR-4 root inside that package,
not a third Composer package.

## Objective And Non-Goals

P1-WF01 provides a product-neutral Tenant workflow Runtime that:

- publishes immutable versions of a validated directed workflow definition;
- starts an instance for one Host-owned typed subject and immutable subject
  revision;
- creates Tenant-scoped human work items from explicit assignment rules;
- applies declared transitions with optimistic concurrency and idempotency;
- appends an immutable workflow event and the existing Tenant audit evidence in
  the same transaction;
- snapshots approved File/Media attachment references;
- produces typed notification and asynchronous-task intents that a Host adapter
  must publish through the existing Notification/SMS and Task/Job contracts;
- keeps identity, Tenant, organization, permission, file, task, notification,
  audit, transaction, idempotency, and Problem Details authorities in their
  existing owners.

The Host owns its subject schema, business calculations, content, form rules,
workflow templates, permission keys, typed-target providers, pages, routes,
OpenAPI, notification copy, automation handlers, and subject-side projection.

P1-WF01 does not add:

- a media article, column, newsroom, audio, video, publishing, broadcast,
  copyright, transcoding, or other product model;
- a fixed three-review sequence or hard-coded review count;
- a universal form builder, BPMN interpreter, script engine, arbitrary code
  execution, expression language, or source generator;
- a second account, Tenant, Department, Role, permission, file, task,
  notification, audit, or idempotency table;
- CRDT, OT, WebSocket, document locking, presence, cursor, editor, or realtime
  gateway implementation;
- delegation, proxy approval, escalation, SLA timers, calendar scheduling,
  cross-Tenant workflow, anonymous approval, or AI approval;
- a reference Host API, Admin Web page, npm change, new public package, stable
  compatibility promise, or SaaS entitlement/quota behavior.

## Existing Owners And Required Reuse

| Concern | Existing authority | WF01 rule |
| --- | --- | --- |
| Identity and Tenant | Kernel `TenantContext`, membership and session contracts | Actor and Tenant come only from a trusted context; request data cannot supply them. |
| Organization | Kernel members, Roles and Departments | Assignment resolution uses a Host adapter backed by these authorities; workflow stores only immutable assignee snapshots and identifiers. |
| Functional/data permission | Kernel `PermissionRequirement` and Data Permission typed targets | Host operation authorization happens before WF01; each transition additionally names a declared Permission requirement and subject target contract. Missing declarations fail closed. |
| Transactions/idempotency | Kernel R01/R02 primitives | Definition publication, instance start and transition commands use one caller-owned PDO and existing scoped idempotency records. |
| Files | File/Media `FileObject` and attachment snapshot semantics | WF01 stores file keys plus immutable name/media-type/size/SHA-256 snapshots returned by an attachment resolver; it never reads a Host file table directly. |
| Human work | WF01 | Human review work items are workflow state, not background jobs. They do not duplicate Task/Job execution leases. |
| Background work | Task/Job `TrustedJobPublisher` | Automation is an explicit post-transition task intent with a registered task type; no inline arbitrary handler. WF01 defines the intent port and the Host owns the real publisher adapter. |
| Notification | Notification/SMS `NotificationService` | A definition may declare a template intent; recipient and attachment resolution remain in the existing service. WF01 defines the intent port and the Host owns a separately authorized producer adapter; it must not forge an `AuthorizationDecision`. |
| Audit | Kernel `PdoAuditRepository` | Every successful write appends one redacted Tenant audit record; workflow events provide domain-neutral transition history, not a second security audit authority. |

The package adapter layer may depend on existing internal namespaces because
they are part of the same Composer package. It must not deep-import a Host,
scan `vendor/`, or copy an existing implementation under a Workflow name.

## Definition And Graph Contract

A workflow is identified by `(tenant_id, module_key, workflow_key)`. The Host
declares `module_key`; `workflow_key`, node keys, transition keys, task types,
template keys, and permission keys are bounded ASCII identifiers. Display
labels are data and are not identifiers.

Definitions are edited as drafts. Publishing creates an immutable monotonically
increasing version with a canonical JSON graph digest. An active version is
never edited or deleted. Retiring a definition prevents new instances but does
not alter existing instances. New versions affect only subsequently started
instances.

The graph contains:

- exactly one `start` node;
- one or more `review` or `action` nodes;
- one or more `terminal` nodes;
- directed transitions with unique keys, explicit source and target nodes,
  action kind, Permission requirement, optional assignment policy, optional
  notification intent, optional task intent, and whether a human decision is
  required;
- no unreachable node, missing target, duplicate edge key, self-loop,
  transition from a terminal node, or cycle without an explicitly declared
  bounded return edge.

A review node declares `completion_policy=any|all` and one or more assignment
rules. WF01 supports member, Role, Department, initiator, and previous-actor
rules through `WorkflowAssignmentResolver`. Empty resolution fails before the
instance or transition commits. `all` snapshots the resolved member set and
requires every member; `any` closes sibling pending items after one completion.
Role or Department membership changes do not silently rewrite an existing
work-item snapshot.

Return and withdrawal are ordinary declared edges. The engine does not infer
“previous review”, author self-review, skip-level approval, or three-review
semantics. A Host template may model those rules explicitly.

## Data And Ownership Contract

Workflow owns only these product-neutral tables, all under the existing `pa_`
prefix and all Tenant scoped:

| Table | Purpose and required constraints |
| --- | --- |
| `pa_workflow_definition` | Stable identity, status `draft|active|retired`, latest version and revision; unique `(tenant_id,module_key,workflow_key)`. |
| `pa_workflow_definition_version` | Immutable canonical graph, digest, publisher and publication time; unique `(definition_id,version)` and `(definition_id,graph_sha256)`. |
| `pa_workflow_instance` | Instance key, fixed definition version, Host subject type/key, current node, status `active|completed|cancelled`, pinned subject revision, initiator, revision and timestamps; unique `(tenant_id,instance_key)` and one active instance per declared subject/workflow scope. |
| `pa_workflow_work_item` | Instance/node/round, assignee kind/key snapshot, status `pending|completed|cancelled`, actor, decision and revision; unique active assignee item within an instance node/round. |
| `pa_workflow_event` | Append-only per-instance sequence, event/action, from/to node, actor, pinned subject revision, comment digest, attachment snapshots and redacted metadata; unique `(instance_id,sequence)`. |

Foreign keys remain inside Workflow except actor/member/Role/Department/file
references, which are logical references validated through their owners. The
tables do not cascade into Host subject data. Definitions and events are never
physically deleted by Runtime APIs. Retention and administrative purge are a
later contract.

The schema is shipped as a package-owned migration resource. A Host migration
runner may execute that resource and record its digest; it must not transcribe
or fork the schema into a second implementation. Clean install, upgrade from
Alpha.2, and idempotent re-entry belong to qualification. Automatic down
migration is not provided; rollback after adoption leaves inert additive
tables and requires forward recovery.

## Command Contract

The framework-neutral Runtime exposes four command families:

1. `saveDraft` validates owner, identifiers, graph shape and optimistic
   definition revision without publishing it.
2. `publishDefinition` freezes the draft as the next immutable version and
   activates it atomically.
3. `startInstance` verifies an active definition, Host subject visibility,
   immutable subject revision, start Permission, assignment resolution and
   attachment snapshots before creating the instance and first work items.
4. `applyTransition` locks the instance and active work items, verifies
   `expected_revision`, current subject revision, transition declaration,
   actor assignment, Permission and typed target, then changes state, closes or
   creates work items, appends the event/audit, emits declared side effects and
   completes idempotency in one transaction.

`startInstance` and `applyTransition` require an `Idempotency-Key`; draft save
and definition publication use optimistic revision plus scoped idempotency.
An exact replay returns the stored safe receipt and creates no second event,
work item, notification, task or audit row. A key reused for another request
hash fails with the existing idempotency code.

All writes use one caller-owned PDO. A failure at definition/instance/work-item,
event, audit, notification outbox, task publication, or idempotency completion
rolls back every effect. No cross-connection atomicity is claimed.

## Permission, Audit And Security

WF01 introduces no super-user switch and no default allow. Package-level
administration keys are:

- `peanut.workflow.definition.read`;
- `peanut.workflow.definition.write`;
- `peanut.workflow.definition.publish`;
- `peanut.workflow.instance.read`;
- `peanut.workflow.instance.start`;
- `peanut.workflow.instance.transition`.

A Host transition may require additional Module-owned Permission keys. The
Host registers them in the existing catalog and binds the workflow subject to
an existing typed-target resource. Package and Host requirements are both
required; neither substitutes for the other. Cross-Tenant subjects,
attachments, assignees and targets are non-enumerating failures.

Successful commands append exactly one Tenant audit event with action,
workflow/instance identifiers, definition version, from/to node, transition,
subject type and a digest of subject key/revision. Audit metadata never includes
raw content, comments, filenames, file keys, recipient addresses, target IDs,
credentials, SQL, stack traces or private paths. The workflow event may retain
the bounded human comment and approved attachment snapshots for business
traceability, but API serializers must apply the caller's visibility policy.

Human-required transitions accept only an active Tenant member resolved from
trusted context. An AI, service credential, background worker or anonymous
actor cannot approve, reject or satisfy an `all` review item. Explicit
non-approval automation edges may be executed by a registered task handler and
are audited as system actions.

Stable failures include `WORKFLOW_DEFINITION_INVALID`,
`WORKFLOW_DEFINITION_CONFLICT`, `WORKFLOW_DEFINITION_RETIRED`,
`WORKFLOW_INSTANCE_CONFLICT`, `WORKFLOW_TRANSITION_UNAVAILABLE`,
`WORKFLOW_ASSIGNMENT_DENIED`, `WORKFLOW_SUBJECT_NOT_FOUND`,
`WORKFLOW_SUBJECT_REVISION_CONFLICT`, `WORKFLOW_ATTACHMENT_UNAVAILABLE`, and
the existing authorization, idempotency and internal error codes. Errors do not
reveal cross-Tenant existence or adapter implementation details.

## Realtime Collaboration Interface Freeze

WF01 does not select or implement CRDT or OT. It freezes only the boundary
between collaborative editing and approval:

- `WorkflowSubjectRevisionResolver` returns a Host-issued immutable revision
  key and digest for a typed subject;
- starting or transitioning an instance pins that revision;
- a human decision supplies the expected pinned revision and fails if the Host
  says the subject has advanced;
- a transition receipt returns the accepted subject revision and workflow
  instance revision;
- collaboration sessions, mutable drafts, presence and merge state never enter
  Workflow tables or audit metadata.

This permits a later collaboration implementation to choose CRDT or OT without
changing workflow authority. The Host remains responsible for creating an
immutable submitted revision before approval.

## Exact Write Sets

The independent contract commit may change only:

- this file;
- `docs/content-status.json`;
- `docs/status/index.md`;
- `README.md`.

After the contract commit, development is split into independently reviewable
commits. The integration owner may narrow but must not silently widen these
sets:

### WF01-A — schema, definition and instance core

- `packages/php/workflow/database/20260811-workflow-runtime.sql`;
- `packages/php/workflow/src/Package.php`;
- `packages/php/workflow/src/Application/WorkflowException.php`;
- `packages/php/workflow/src/Application/WorkflowReceipt.php`;
- `packages/php/workflow/src/Application/WorkflowRuntime.php`;
- `packages/php/workflow/src/Definition/WorkflowDefinition.php`;
- `packages/php/workflow/src/Definition/WorkflowGraph.php`;
- `packages/php/workflow/src/Definition/WorkflowNode.php`;
- `packages/php/workflow/src/Definition/WorkflowTransition.php`;
- `packages/php/workflow/src/Instance/WorkflowInstance.php`;
- `packages/php/workflow/src/Instance/WorkflowWorkItem.php`;
- `packages/php/workflow/src/Persistence/WorkflowRepository.php`;
- `packages/php/workflow/src/Persistence/PdoWorkflowRepository.php`;
- `packages/php/workflow/tests/Unit/Definition/WorkflowGraphTest.php`;
- `packages/php/workflow/tests/Integration/Persistence/PdoWorkflowRepositoryTest.php`;
- `packages/php/workflow/tests/Integration/Application/WorkflowRuntimeTest.php`;
- `packages/php/composer.json`, root `composer.json`, `deptrac.yaml` and
  `phpunit.xml` only to register the new namespace/source/test layer;
- `scripts/check-alpha2-package-projections` and `scripts/check-workspace` only
  to include the new internal directory in `peanut-admin/core`;
- `docs/status/index.md` and this contract for precise candidate state.

### WF01-B — existing-capability ports and failure atomicity

- `packages/php/workflow/src/Adapter/WorkflowAssignmentResolver.php`;
- `packages/php/workflow/src/Adapter/WorkflowSubjectRevisionResolver.php`;
- `packages/php/workflow/src/Adapter/WorkflowAttachment.php`;
- `packages/php/workflow/src/Adapter/WorkflowAttachmentResolver.php`;
- `packages/php/workflow/src/Adapter/WorkflowNotificationIntent.php`;
- `packages/php/workflow/src/Adapter/WorkflowTaskIntent.php`;
- `packages/php/workflow/src/Adapter/WorkflowSideEffectPublisher.php`;
- `packages/php/workflow/src/Adapter/WorkflowTransitionEffects.php`;
- `packages/php/workflow/tests/Unit/Adapter/WorkflowAttachmentTest.php`;
- `packages/php/workflow/tests/Integration/Application/WorkflowCapabilityCompositionTest.php`;
- `packages/php/testing/src/Workflow/WorkflowAtomicityContractHarness.php`;
- `packages/php/testing/tests/Unit/Workflow/WorkflowAtomicityContractHarnessTest.php`;
- root `composer.json`, `packages/php/composer.json` and `phpunit.xml` only if
  required to autoload the exact new files above;
- this contract and `docs/status/index.md` only for precise candidate state.

WF01-B does not change Kernel, File/Media, Task/Job or Notification/SMS source.
If a real Host cannot implement a safe adapter using their public contracts, a
separate integration contract must name the exact missing source files and
security behavior before any existing package changes.

### WF01-Q — fixed-candidate qualification and publication records

- new Workflow qualification/release decision records under `docs/reviews/`
  and `docs/decisions/releases/`;
- current status files, package manifest/version, projection metadata,
  publication workflow and generated license inventory only as required for
  `peanut-admin/core@0.1.0-alpha.5`;
- no npm package version or content change unless a separately contracted Web
  Workflow surface is later approved.

If implementation needs a file outside the selected set, it stops for an
independent contract correction before that file changes.

## Test Ownership And Qualification

`P1-WORKFLOW-RUNTIME-001` owns all executable evidence. Development commits add
tests but, under the repository policy, run only static review, exact write-set
inspection and `git diff --check`. One fixed-candidate qualification owner then
runs each group once:

1. Workflow definition/graph and state-machine unit tests;
2. MySQL clean install, Alpha.2 upgrade, idempotent migration and Tenant
   isolation integration tests;
3. R01/R02 transaction, idempotency, permission, typed-target and audit
   composition tests with failure injection at every write checkpoint;
4. File snapshot, human assignment, Notification/SMS outbox and Task/Job intent
   adapter tests, including missing-provider fail-closed behavior;
5. package projection and isolated PHP 8.3 registry-consumer tests;
6. the existing fixed-candidate aggregate, security, recovery, performance,
   documentation, license and repository-tail guards exactly once.

Acceptance requires at least:

- sequential, return, withdrawal and `any|all` review graphs without a fixed
  number of stages;
- immutable active versions and old instances remaining pinned after a new
  version publishes;
- one winner under concurrent transition attempts and no duplicate side
  effects under exact replay;
- cross-Tenant subject, actor, Role, Department, attachment and target denial;
- stale workflow or subject revision rejection before any state change;
- human-only approval and a negative AI/service-actor case;
- one-PDO rollback after every domain/event/audit/notification/task/idempotency
  checkpoint;
- no product-specific term, table, permission, page, route or example in the
  package projection;
- a clean Composer consumer installing the immutable published candidate from
  the registry.

## Stop Line

The contract commit authorizes implementation only from its exact resulting
commit. Development completion is not qualification. Qualification does not by
itself publish. Publication of `peanut-admin/core@0.1.0-alpha.5`, tag/Release,
Packagist update and downstream adoption occur only after the fixed tree passes
WF01-Q and an explicit publication record binds the exact source/projection
commits and digests.

The Peanut Admin application may consume only that immutable registry version.
It must provide a real ThinkPHP Host, one product-owned example definition,
documentation and one minimum business acceptance while deleting any duplicate
Workflow Runtime. The media project source snapshots remain read-only, its
planned code repository remains `exists: false`, realtime collaboration stays
interface-only, entitlement/quota/cost attribution stays deferred, and SaaS01
does not start under this contract.
