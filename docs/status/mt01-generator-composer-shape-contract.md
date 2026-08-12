# MT01 Generator Composer Shape Repair Contract

## Fixed Repair

```text
task: MT01-GENERATOR-COMPOSER-SHAPE-01
state: implementation-ready
prerequisite: f9cec4df7d4336c4d67138482feda56ff346544f
owner: MT01 Generator Composer shape repair owner
blocked_gate: MT01-GENERATED-HOST-INTEGRATION-001
```

The Generated Host Gate reached `composer install` on a clean generated
project and stopped before migration because the Generator decoded the empty
`config.allow-plugins` JSON object as a PHP array and serialized it as `[]`.
Composer 2.8 requires that value to be an object or boolean.

The implementation owner may change only:

- `tools/project-generator/src/ProjectGenerator.php`, to preserve an empty
  `allow-plugins` value as a JSON object while adapting project manifests;
- `tests/project-generator/static-contract.php`, to assert the generated JSON
  node remains an object.

After that exact implementation is merged, a separate baseline rollover owner
may change only `tools/project-generator/source-baseline.json`. It binds the
controlled Generator content to implementation commit
`2f8b3c027ef4f6066aa417dc10ebd56823fac047`, tree
`67b3d68e2061ec6f7f066b50cee0fe1400ff5ed2`, 683-file digest
`62f5f1c8ed3fdb4393e43b21170809011b07dfc732b659b750286ceb123ae734`,
and archive tree `efa83712117e3d53bb690730543444ccf9bc1462`.
This owner does not change Generator source/tests or Generated Host fixtures.

No package manifest, lock, Runtime, starter source, Generated Host fixture,
release workflow, application file, schema, migration or prior Generator Gate
may change. The repair must not rerun the already passing Generator groups.
The source repair receives PHP 8.3 lint, exact write-set inspection and
`git diff --check`; the rollover receives JSON validation, exact single-file
inspection and `git diff --check` only;
the affected Generated Host group is rerun only after the repair and the
five-file Host candidate are separately merged into a clean `dev` candidate.

Failure to preserve an object blocks only generated-project Composer install
and downstream MT01 adoption. It does not authorize a Composer workaround,
schema change, relaxed validation or a second Generator implementation.
