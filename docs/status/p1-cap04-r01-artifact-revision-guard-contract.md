# P1-CAP04-R01 Artifact Revision Guard Remediation Contract

## Status

```text
task: P1-CAP04-R01
state: authorized remediation contract
prerequisite_commit: db348c783ff8620fd77615294c946a36bca25a49
owner: Collaboration ArtifactRevision adapter
test_owner: P1-COLLABORATION-001
publication_authorized: false
```

CAP06 reached real Collaboration publication and received
`ARTIFACT_REVISION_CONFLICT`. The adapter creates a child revision for an
existing artifact but passes a null optimistic precondition. The public
ArtifactRevision Repository intentionally rejects that shape for an existing
artifact.

## Decision

While the caller's Collaboration transaction is active on the same PDO, lock
the artifact, require the pinned finalized parent to remain its latest finalized
revision, and pass the locked artifact revision to `createRevision()`. This
preserves fail-closed concurrency and does not change schemas or public APIs.

The implementation may change only:

- `packages/php/collaboration/src/ArtifactRevision/ArtifactRevisionCollaborationPublisher.php`;
- `packages/php/collaboration/tests/Integration/ArtifactRevision/ArtifactRevisionCollaborationPublisherTest.php`.

Acceptance is exact write set, PHP 8.3 lint, `git diff --check`, and one focused
real MySQL publisher group. CAP06, projection rollover and publication remain
separate gates.
