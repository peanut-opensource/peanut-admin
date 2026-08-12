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

No package manifest, lock, Runtime, starter source, Generated Host fixture,
release workflow, application file, schema, migration or prior Generator Gate
may change. The repair must not rerun the already passing Generator groups.
It receives PHP 8.3 lint, exact write-set inspection and `git diff --check`;
the affected Generated Host group is rerun only after the repair and the
five-file Host candidate are separately merged into a clean `dev` candidate.

Failure to preserve an object blocks only generated-project Composer install
and downstream MT01 adoption. It does not authorize a Composer workaround,
schema change, relaxed validation or a second Generator implementation.
