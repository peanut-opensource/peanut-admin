# MT01 Admin Web Smoke Contract

## Fixed Stage

```text
task: MT01-ADMIN-WEB-SMOKE-01
state: implementation-ready
contract_prerequisite: 57f1237d3e03ddbcda41a3103f1cbcc5a935f1ad
contract_prerequisite_tree: 67b3d68e2061ec6f7f066b50cee0fe1400ff5ed2
implementation_prerequisite: NEW_DEV_AFTER_CONTRACT_AND_GENERATED_HOST
registry_identity: PENDING_ALPHA5
owner: MT01 Admin Web smoke owner
test_owner: MT01-ADMIN-WEB-SMOKE-001
database: peanut_admin_mt01_admin_web_<run_id>
```

This slice owns MT01 Gate item 7 only: a real-browser smoke for login, explicit
Tenant selection, fail-closed access denial, and one product-neutral external
Module page. It may consume the separately accepted Generated Host fixture but
does not own, modify, repeat, or replace its Generator, installation, Module
schema, command atomicity, or unmount evidence.

The contract starts after Core PR #42 merged to the fixed commit and tree above.
Implementation starts from the new `dev` commit only after this contract PR and
the Generated Host fixture PR merge with every declared check successful. A
branch name, moving `dev`, the abandoned `f7b4dd5` tree, or the historical local
`aa1672a5a6fcaf8652e6042f74293e9172983d3f` three-commit draft is not an
implementation prerequisite or acceptance evidence.

## Objective And Non-Goals

The single smoke must prove all of the following on one generated Host:

1. a real email/password login returns a Tenant-selection challenge and no
   Tenant workspace is entered before an explicit selection;
2. the selector presents two active Tenants, selecting the first establishes a
   trusted `fixture-web` Tenant context, and the selected Tenant identity is
   visible in the workspace;
3. the selected Tenant can open `/app/fixture-records`, whose page contribution
   is owned by external Module `fixture.record`, and can read only that Tenant's
   product-neutral fixture row through `/api/fixture/v1/records`;
4. removing `fixture.record.read` denies direct route navigation before the
   collection request, while a direct backend lookup of the other Tenant's row
   returns the same `404 RESOURCE_NOT_FOUND` shape as an unknown row;
5. no missing, stale, cross-Client, cross-Tenant, disabled-Module, or
   missing-Permission state falls back to an empty successful page or a
   different Tenant.

This slice does not rerun or claim MT01 Gate items 1-6 or 8. It does not start
MT02, add product business behavior, change a Runtime package, Generator,
starter, Module manifest/lock, dependency manifest/lock, generated OpenAPI
artifact, package projection, Registry, tag, release, or publication workflow.
It does not change the Peanut Admin application repository or
`docs/likeadmin-parity-report.md`. Desktop Chromium is the minimum Gate; mobile,
cross-browser, performance, recovery, clean install, upgrade, CAP01-CAP06,
Generator, Generated Host, and aggregate repository suites remain outside this
owner.

## Frozen Dependency And Composition Boundary

The implementation consumes only accepted source already present at its new
`dev` prerequisite plus the Generated Host fixture's published test interface.
No new dependency may be installed. Final Composer and npm version fields may
remain `PENDING_ALPHA5`; the script must accept their later immutable identities
through environment inputs and must not invent a Registry version.

The Generated Host owner retains exclusive ownership of:

- `scripts/test-mt01-generated-host`;
- `tests/mt01/generated-host/run.php`;
- `tests/mt01/generated-host/fixture/module.json`;
- `tests/mt01/generated-host/fixture/CreateFixtureRecord.php`;
- `tests/mt01/generated-host/fixture/FixtureRecordHost.php`;
- `docs/status/mt01-generated-host-fixture-contract.md`;
- `docs/content-status.json`;
- `docs/status/index.md`.

Those paths must not change here. The Admin Web smoke may invoke a stable,
read-only Generated Host preparation mode or consume a generated project path
and redacted fixture metadata exported by that owner. If the merged Generated
Host exposes neither, implementation stops for a separate interface contract;
it must not edit the eight paths or duplicate their backend fixture.

The Web fixture mounts only test-owned files into the disposable generated
project. It composes the existing Admin Web authentication, Tenant selector,
route guard, protected transport, and Shell code with one test-only
`defineAdminModule()` contribution:

```text
module key: fixture.record
client key: fixture-web
route: /app/fixture-records
permission: fixture.record.read
API prefix: /api/fixture/v1/
```

The page owns no schema and performs only a protected list/detail read. Backend
records, Tenant ownership, permissions, Module availability, trusted context,
and stable Problem Details remain authoritative in the Generated Host. The
browser fixture must not replace these decisions with local state.

## Identity Injection And Fail-Closed Rules

The entry script creates a random lowercase hexadecimal `run_id`, a database
named exactly `peanut_admin_mt01_admin_web_<run_id>`, random loopback backend
and frontend ports, a random test password, and ephemeral authentication key
material. Cleanup may stop only processes it started, remove only its temporary
directory, and drop only that exact validated database. No credential, token,
cookie, private path, or generated secret is printed, persisted, or committed.

Fixture preparation creates one account with active memberships in Tenant
`alpha` and Tenant `beta`. Both memberships use Client `fixture-web`. The
browser supplies the account email and random password only through process
environment. Access and challenge tokens remain in memory; refresh cookies use
the existing HttpOnly Client-specific contract. The test never writes an access
token or permission list to local or session storage.

Every protected request uses the existing trusted context path. The Web route
guard requires both effective Module `fixture.record` and Permission
`fixture.record.read` before loading the page. The backend independently checks
Client, Tenant context, Module availability, Permission, and row Tenant. A
wrong Client, absent or stale context, disabled Module, or missing Permission
must yield the existing non-success problem and must not call the record
handler. Cross-Tenant and unknown record identifiers both yield identical
`404 RESOURCE_NOT_FOUND` status, code, title, detail, and response shape.

Playwright must make real HTTP requests. `page.route`, `context.route`, HAR
replay, service-worker API replacement, `installApiFixture`, and any other
`/api/**` interception are forbidden. The smoke fails on a skipped/fixme test,
page error, unexpected console error, missing request ID, API response outside
the asserted sequence, or secret/token material in its captured output.

## Exact Implementation Write Set

After both prerequisites are merged, the implementation owner creates a new
branch from the resulting `origin/dev` and changes only these paths:

- `scripts/test-mt01-admin-web-smoke` — isolated database, ports, process and
  one-group owner;
- `tests/mt01/admin-web/playwright.config.ts` — one desktop Chromium project
  and generated Host web servers;
- `tests/mt01/admin-web/full-stack.e2e.ts` — the four Gate assertions;
- `tests/mt01/admin-web/fixture/setup.php` — redacted two-Tenant identity and
  read-fixture preparation through the Generated Host interface;
- `tests/mt01/admin-web/fixture/main.ts` — test-only Admin Web bootstrap;
- `tests/mt01/admin-web/fixture/modules/fixture-record/index.ts` — external Web
  Module contribution;
- `tests/mt01/admin-web/fixture/modules/fixture-record/FixtureRecordPage.vue` —
  product-neutral list/detail page and explicit error states;
- `tests/mt01/admin-web/fixture/vite.config.ts` — generated Host source aliases,
  loopback proxy and strict random port;
- this contract, only to bind the exact merged prerequisite commits and record
  the focused result.

The implementation is one independently reviewable commit. If the generated
source layout or fixture interface makes this whitelist insufficient, work
stops and an independent contract correction merges before implementation
continues. No shared registration file is part of this write set. Registration
in `docs/content-status.json` and summary integration in `docs/status/index.md`
are explicitly deferred to the main integration owner after its existing
Generated Host ownership ends.

## One Focused Verification Group

The contract PR performs static review, exact write-set inspection,
`git diff --check`, and a clean commit only. Runtime verification is deferred
to `MT01-ADMIN-WEB-SMOKE-001` because no executable behavior exists in the
contract commit.

The implementation owner performs static review, checks the exact write set,
lints the new PHP and TypeScript/Vue fixture through their existing parsers,
runs `git diff --check`, and then runs exactly one focused group once:

```bash
./scripts/test-mt01-admin-web-smoke
```

That command owns generated-project preparation, its isolated MySQL database,
real backend/frontend processes, and one Playwright desktop Chromium project.
It must assert the login, explicit two-Tenant selection, selected Tenant label,
external Module page and Tenant-owned row; frontend denial with zero collection
requests; backend cross-Tenant/unknown indistinguishable 404 responses; no API
interception; and no page/console errors or skipped tests.

If the group fails, the owner performs one read-only diagnosis and one static
repair batch, then reruns only this failed group once. A second failure blocks
the slice. Passed Generator, Generated Host, CAP, package, Registry, security,
performance, recovery, starter, browser matrix, and aggregate groups are not
rerun.

## Pull Request And Stop Line

The contract is committed and reviewed in its own PR to `dev`; it is not
auto-merged and implementation does not begin until every check declared on
that PR succeeds and the PR is merged. The implementation then starts from the
new `dev`, is committed in one separate PR, and is not auto-merged. The focused
group must pass on the exact implementation tree before merge; normal declared
PR CI must also be green.

Completion proves only MT01 Gate item 7 for the recorded exact source/tree and
generated Host prerequisites. It does not complete MT01, nominate
`PA-DCS-ADOPT-01`, fill final Registry identities, authorize application MT02,
move a downstream-consumption lock, publish a package, create a tag or release,
deploy anything, or claim production readiness.
