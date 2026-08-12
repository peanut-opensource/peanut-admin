# P1-CAP02-R01 MySQL Parent Guard Remediation Contract

## Status

```text
task: P1-CAP02-R01
state: authorized remediation contract
prerequisite_commit: 3ca731804eb8291408e03c0ae18299d2b7db1cb7
owner: ArtifactRevision schema and repository
test_owner: P1-ARTIFACT-REVISION-001
dependency_change: none
publication_authorized: false
```

CAP06 private downstream adoption exposed MySQL error 3818 while installing the
fixed Alpha.5 ArtifactRevision schema. MySQL 8.4 rejects a `CHECK` constraint
that refers to the table's `AUTO_INCREMENT` `id` column. No product operation
or CAP06 assertion ran before the failure.

## Decision

Remove only `chk_artifact_revision_parent` from the generated table SQL. The
existing Tenant/artifact-scoped parent foreign key remains. The repository
continues to lock the parent, require it to be finalized and require its
revision number to be smaller than the new revision number before insertion.
That Repository rule is the executable owner of `parent_revision_id != id`:
an existing row cannot be its own parent before the new auto-increment identity
exists, and only a strictly earlier finalized revision may be selected.

The real MySQL repository test must install the unchanged public schema and
prove a valid earlier parent succeeds while a non-earlier/self candidate is
rejected without inserting a revision or advancing the artifact counters. The
unit schema test must reject reintroduction of the unsupported constraint.

This remediation does not change APIs, models, migrations, dependencies,
authorization, Tenant scoping, immutable envelopes, package versions or
publication status. It does not adopt the unrelated local `f7b4dd5` draft.

## Exact Write Set

The contract commit may change only:

- this file;
- `docs/content-status.json`;
- `docs/status/index.md`.

The implementation commit may change only:

- `packages/php/artifact-revision/src/Database/Schema.php`;
- `packages/php/artifact-revision/tests/Unit/Database/SchemaTest.php`;
- `packages/php/artifact-revision/tests/Integration/Persistence/PdoArtifactRevisionRepositoryTest.php`.

If those files are insufficient, create a separate remediation contract rather
than expanding this write set.

## Acceptance And Stop Line

1. Static review proves the exact three-file implementation write set and
   `git diff --check` passes.
2. PHP lint and the ArtifactRevision unit group pass once on PHP 8.3.
3. The ArtifactRevision repository integration group passes once on isolated
   MySQL 8.4, including schema installation and the non-earlier parent guard.
4. Existing PR checks run once. Passed CAP01-CAP05 qualification groups are not
   repeated.
5. After merge, CAP05 produces new immutable Composer/npm projection identities
   only as required by the changed source tree; CAP06 updates its locks and
   resumes its single focused downstream adoption test.

This contract does not itself qualify or publish Alpha.5. A failed focused
group may receive one mechanical repair and one rerun; a new architectural
failure becomes a separately named remediation item.
