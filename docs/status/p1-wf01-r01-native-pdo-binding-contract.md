# P1-WF01-R01 Native PDO Binding Remediation Contract

## Status

```text
task: P1-WF01-R01
state: authorized remediation contract
prerequisite_commit: 20e672d942ef352566785667bdce1f14db4dd470
owner: Workflow persistence
test_owner: P1-WORKFLOW-001
dependency_change: none
publication_authorized: false
```

CAP06 reached `WorkflowRuntime::saveDraft()` on real MySQL 8.4 with native PDO
prepares and received the safe internal error before the definition was
created. Static diagnosis found three statements on the contracted positive
Workflow path that reuse one named placeholder: definition creation reuses
`:member_id` for creator and updater, instance creation reuses it for initiator
and last actor, and human work-item completion reuses it for completion actor
and assignee matching. Native MySQL PDO permits each named placeholder only
once and reports `HY093` for these statements.

## Decision And Write Sets

Give each repeated use a distinct role-specific placeholder name bound to the
same trusted member ID. No identity, authorization, graph, audit, schema,
transaction, idempotency or public API behavior changes.

The contract commit may change only this file, `docs/content-status.json` and
`docs/status/index.md`.

The implementation commit may change only:

- `packages/php/workflow/src/Persistence/PdoWorkflowRepository.php`;
- `packages/php/workflow/tests/Integration/Application/WorkflowRuntimeTest.php`.

The existing integration path must use native prepares and prove definition
save/publish, instance start and human transition succeed. A duplicate in
Collaboration heartbeat is outside the contracted CAP06 open/join/save/publish
path and is not changed here. Any other statement requires an explicit contract
update before implementation.

## Acceptance

1. Exact write set and `git diff --check` pass.
2. PHP 8.3 lint passes.
3. The focused Workflow Runtime MySQL 8.4 group runs once with native prepares.
4. Existing PR checks run once; passed CAP01-CAP05 groups are not repeated
   locally.
5. After merge, only the affected Composer projection is regenerated; the
   unchanged npm subtree retains its fixed digest, and CAP06 updates its lock
   before resuming the focused adoption group.
