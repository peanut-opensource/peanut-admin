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

That focused integration run also exposed the pre-existing create SQL reusing
one named `:member_id` placeholder twice. Native MySQL PDO prepares reject the
statement with `HY093`; emulated prepares had hidden the defect. R01 therefore
also assigns the ArtifactRevision Repository to give the creator and updater
columns distinct named placeholders with the same trusted member value. This
is a SQL binding correction only and does not change identity or audit
semantics.

After native binding was corrected, the retained integration assertion exposed
another fixture error: it hashes MySQL's textual rendering of a JSON column.
MySQL may add whitespace or reorder object keys, while the stored digest is
defined over `ArtifactRevision::encodeEnvelope(expectedEnvelope())`. R01 owns
only that assertion correction so the test verifies the public canonical
encoder rather than storage formatting. Production integrity validation already
reconstructs the canonical envelope from typed columns and remains unchanged.

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
- `packages/php/artifact-revision/src/Persistence/PdoArtifactRevisionRepository.php`;
- `packages/php/artifact-revision/tests/Unit/Database/SchemaTest.php`;
- `packages/php/artifact-revision/tests/Integration/Persistence/PdoArtifactRevisionRepositoryTest.php`.

If those files are insufficient, create a separate remediation contract rather
than expanding this write set.

## Acceptance And Stop Line

1. Static review proves the exact three-file implementation write set and
   `git diff --check` passes.
2. PHP lint and the ArtifactRevision unit group pass once on PHP 8.3.
3. The ArtifactRevision repository integration group passes once on isolated
   MySQL 8.4 with native prepares, including schema installation, artifact
   creation and the non-earlier parent guard.
4. Existing PR checks run once. Passed CAP01-CAP05 qualification groups are not
   repeated.
5. After merge, CAP05 produces new immutable Composer/npm projection identities
   only as required by the changed source tree; CAP06 updates its locks and
   resumes its single focused downstream adoption test.

This contract does not itself qualify or publish Alpha.5. A failed focused
group may receive one mechanical repair and one rerun; a new architectural
failure becomes a separately named remediation item.
