# MT01 Generator Integration Fixture Repair Contract

## Fixed Repair

```text
task: MT01-GENERATOR-INTEGRATION-R01
state: implementation authorized
prerequisite: 6f24e7ab42e37b56066a3b3be8833a54f087eb3b
failed_candidate: 0aae10ee4c5ccba6d3949924f6dd1b8a6e23b622
owner: MT01 Generator fixture repair owner
verification_owner: MT01 Generator integration owner
registry_identity: PENDING_ALPHA5
```

The first authorized integration round reached the intended Generator tests on
PHP 8.3.24 and exposed two fixture defects. `tests/project-generator/run.php`
invoked the repository shell entry point directly and the process launcher
returned `posix_spawn() failed`; `tests/project-generator/static-contract.php`
then required its generated migration fixture before loading the repository
autoload that provides `OwnedMigration`. Neither failure is a Generator product
behavior, Registry, CAP01-CAP06, database, browser, or package publication
failure.

## Exact Write Set And Owners

The repair implementation changes only:

- `tests/project-generator/run.php`, owned by the Generator fixture owner, to
  invoke `scripts/create-project` through `/bin/bash` while retaining the same
  arguments, exit-code assertions, output assertions and working directory;
- `tests/project-generator/static-contract.php`, owned by the static fixture
  owner, to resolve and require the existing repository Composer autoload
  before loading the `OwnedMigration` fixture. The existing explicit
  `PEANUT_PROJECT_GENERATOR_AUTOLOAD` override and missing-autoload failure stay
  fail closed.

No Generator source, starter, package, manifest, lock, Runtime, schema,
migration, workflow, release or Registry file may change. The failed
integration candidate remains unmerged and must not record a passing result.

## Acceptance And Stop Line

The implementation owner performs static review, exact write-set inspection,
PHP 8.3 lint of the two files and `git diff --check`, then commits without
running an automated Generator group. After that implementation is merged with
all declared PR checks successful, the integration owner rebuilds the
three-file record from the new `dev` identity and runs only the two failed
groups once:

```bash
php tests/project-generator/run.php
PEANUT_PHP83="$(command -v php)" php tests/project-generator/static-contract.php
```

Both must pass on one exact tree before integration is recorded. Another
failure becomes a new explicit blocker; passed CAP, PR, aggregate, database,
browser and Registry checks are not repeated. Completion repairs only the MT01
Generator parameterization integration slice. It does not complete MT01,
nominate `PA-DCS-ADOPT-01`, publish Alpha.5 or start MT02.
