# P1-CAP05-R01 Product-Neutral Projection Fixture Contract

## Status

```text
task: P1-CAP05-R01
state: accepted remediation contract
prerequisite_commit: bcbf77d4a6cc112d22ca1a67611e98afc8fc804a
prerequisite_tree: 35eb3971f8fe7522c76a3814861235cb6e57bf3e
failed_owner: P1-CROSS-PRODUCT-QUALIFICATION-001 Composer projection
runtime_change: forbidden
npm_projection_result: retained because packages/web is unchanged
publication_authorized: false
```

## Finding

CAP04 passed all six required PR checks and merged as the exact prerequisite
above. CAP05 then started the existing Alpha.5 Composer projection check. The
check stopped before manifest validation or isolated-consumer installation
because six integration-test files use `article` as an example artifact name.
No production source file matched the product-specific vocabulary guard.

The test fixtures are part of the projected Composer subtree, so they cannot
be waived or hidden from inspection. They must use a product-neutral record
example while preserving every assertion, branch, authorization context,
digest, rollback and isolation scenario.

The npm Alpha.5 projection from the same fixed source passed packaging and
metadata inspection as 72 files, 15 exports and SHA-256
`5d01076276a4599682b65fcfde812f5fe201c3e597f2fab38b8ef23cbabe8c80`.
This remediation cannot change `packages/web`, so that result is retained.

## Exact Implementation Write Set

One remediation commit may change only:

- `packages/php/artifact-revision/tests/Integration/Application/ArtifactRevisionServiceTest.php`;
- `packages/php/artifact-revision/tests/Integration/Persistence/PdoArtifactRevisionRepositoryTest.php`;
- `packages/php/artifact-revision/tests/Integration/Workflow/ArtifactWorkflowSubjectRevisionResolverTest.php`;
- `packages/php/collaboration/tests/Integration/Application/CollaborationServiceTest.php`;
- `packages/php/collaboration/tests/Integration/ArtifactRevision/ArtifactRevisionCollaborationPublisherTest.php`;
- `packages/php/collaboration/tests/Integration/Persistence/PdoCollaborationRepositoryTest.php`.

Within those files only product fixture identifiers may change:
`document.article` to `document.record`, `article` keys and payload paths to
`record`, `article.body` to `record.body`, and the invalid display fixture
`Document Article` to `Document Record`. Runtime source, schemas, package
manifests, dependencies, locks, tests and assertion meaning must not change.

## Verification And Stop Line

The implementation performs the exact-write-set review and `git diff --check`,
then the repository PR automation runs once on the resulting candidate. After
merge, CAP05 may resume only the failed Composer projection group against the
new exact commit. The retained npm digest must not be regenerated because its
source subtree is unchanged.

A Composer projection failure receives one read-only diagnosis and stops.
This task does not qualify CAP05, change an application lock, publish Alpha.5,
create a tag or Release, nominate DCS, or start CAP06.
