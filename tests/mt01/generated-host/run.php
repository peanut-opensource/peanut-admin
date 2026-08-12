<?php

declare(strict_types=1);

use Composer\InstalledVersions;
use PeanutAdmin\DataPermission\Exception\DataAuthorizationException;
use PeanutAdmin\DataPermission\Package as DataPermissionPackage;
use PeanutAdmin\Kernel\Auth\TenantContext;
use PeanutAdmin\Kernel\Auth\ValidatedTenantSession;
use PeanutAdmin\Kernel\Authorization\DataPermissionAdapter;
use PeanutAdmin\Kernel\Authorization\EffectivePermissionSet;
use PeanutAdmin\Kernel\Authorization\RevisionPermissionCache;
use PeanutAdmin\Kernel\Authorization\TenantAuthorizationEvaluator;
use PeanutAdmin\Kernel\Authorization\TenantAuthorizationRepository;
use PeanutAdmin\Kernel\Host\AtomicOperationAdapter;
use PeanutAdmin\Kernel\Host\ExternalHostConfiguration;
use PeanutAdmin\Kernel\Host\ExternalOperationHost;
use PeanutAdmin\Kernel\Host\ExternalOperationResponse;
use PeanutAdmin\Kernel\Host\ModuleAvailabilityAdapter;
use PeanutAdmin\Kernel\Host\PermissionAdapter;
use PeanutAdmin\Kernel\Host\ProblemDetailsAdapter;
use PeanutAdmin\Kernel\Host\TrustedContextAdapter;
use PeanutAdmin\Kernel\Host\TypedTargetAdapter;
use PeanutAdmin\Kernel\Http\PermissionMiddleware;
use PeanutAdmin\Kernel\Module\CompiledModuleRegistry;
use PeanutAdmin\Kernel\Module\ManifestDocument;
use PeanutAdmin\Kernel\Module\ModuleGuard;
use PeanutAdmin\Kernel\Module\ModuleHostLayout;
use PeanutAdmin\Kernel\Module\Persistence\PdoModuleRuntimeRepository;
use PeanutAdmin\Kernel\Package as KernelPackage;
use PeanutAdmin\Kernel\Platform\Authorization\PlatformAuthorizationEvaluator;
use PeanutAdmin\Kernel\Platform\Authorization\PlatformAuthorizationRepository;
use GeneratedHost\Admin\Modules\Fixture\Record\FixtureRecordHost;

const MODULE_KEY = 'fixture.record';

function command(array $command, string $workingDirectory): string
{
    $pipes = [];
    $process = proc_open($command, [
        0 => ['pipe', 'r'],
        1 => ['pipe', 'w'],
        2 => ['pipe', 'w'],
    ], $pipes, $workingDirectory);
    if (!is_resource($process)) {
        throw new RuntimeException('Could not start fixture process.');
    }
    fclose($pipes[0]);
    $stdout = stream_get_contents($pipes[1]);
    $stderr = stream_get_contents($pipes[2]);
    fclose($pipes[1]);
    fclose($pipes[2]);
    $code = proc_close($process);
    if ($code !== 0) {
        throw new RuntimeException(sprintf('Command failed (%d): %s%s', $code, $stdout, $stderr));
    }

    return is_string($stdout) ? $stdout : '';
}

function removeFixture(string $path): void
{
    if (is_link($path) || is_file($path)) {
        if (!unlink($path)) {
            throw new RuntimeException("Could not remove fixture file: {$path}");
        }

        return;
    }
    if (!is_dir($path)) {
        return;
    }
    $entries = scandir($path);
    if (!is_array($entries)) {
        throw new RuntimeException("Could not scan fixture directory: {$path}");
    }
    foreach ($entries as $entry) {
        if ($entry !== '.' && $entry !== '..') {
            removeFixture($path . '/' . $entry);
        }
    }
    if (!rmdir($path)) {
        throw new RuntimeException("Could not remove fixture directory: {$path}");
    }
}

function assertTrue(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

function tableCount(PDO $pdo, string $table): int
{
    if (preg_match('/^[a-z][a-z0-9_]{0,63}$/D', $table) !== 1) {
        throw new RuntimeException('Unsafe fixture table name.');
    }
    $statement = $pdo->query("SELECT COUNT(*) FROM `{$table}`");
    if ($statement === false) {
        throw new RuntimeException("Could not count fixture table: {$table}");
    }

    return (int) $statement->fetchColumn();
}

/** @param array<string, int|string|null> $values */
function insert(PDO $pdo, string $table, array $values): int
{
    if (preg_match('/^[a-z][a-z0-9_]{0,63}$/D', $table) !== 1) {
        throw new RuntimeException('Unsafe fixture table name.');
    }
    $columns = array_keys($values);
    $statement = $pdo->prepare(sprintf(
        'INSERT INTO `%s` (`%s`) VALUES (:%s)',
        $table,
        implode('`, `', $columns),
        implode(', :', $columns),
    ));
    $statement->execute($values);

    return (int) $pdo->lastInsertId();
}

/** @return list<int> */
function state(PDO $pdo): array
{
    return [
        tableCount($pdo, 'fixture_record'),
        tableCount($pdo, 'pa_tenant_audit_event'),
        tableCount($pdo, 'fixture_outbox'),
        tableCount($pdo, 'pa_tenant_idempotency_record'),
    ];
}

/** @return array<string, mixed> */
function target(string $scopeId): array
{
    return [
        'target_resource_key' => 'fixture.scope',
        'target_id' => $scopeId,
        'target_role' => 'primary',
    ];
}

function context(int $tenantId, int $accountId, int $memberId, string $requestId, string $clientKey = 'fixture-web'): TenantContext
{
    return TenantContext::fromValidatedSession(new ValidatedTenantSession(
        $tenantId,
        "fixture-session-{$tenantId}",
        $tenantId,
        $accountId,
        $memberId,
        $clientKey,
        new DateTimeImmutable('2026-08-12T00:00:00Z'),
        1,
    ), $requestId);
}

/**
 * @param array<int, list<string>> $permissions
 */
function host(PDO $pdo, array $permissions, bool $denyTargets = false): ExternalOperationHost
{
    $configuration = new ExternalHostConfiguration(
        new ModuleHostLayout('backend/src/Modules', 'GeneratedHost\\Admin\\Modules', 'frontend/src/modules'),
        ['backend/src/Modules/Fixture/Record'],
        '/api/fixture/v1',
        '/api/platform/v1',
        'docs/api/openapi.yaml',
        'backend/route/openapi-generated.php',
        'frontend/src/generated/api.d.ts',
        ['fixture-web'],
        'X-Request-ID',
    );
    $registry = new CompiledModuleRegistry([
        ManifestDocument::fromArray('backend/src/Modules/Fixture/Record', ['key' => MODULE_KEY]),
    ], ['fixture.scope' => MODULE_KEY], [
        'fixture_scope' => MODULE_KEY,
        'fixture_record' => MODULE_KEY,
        'fixture_outbox' => MODULE_KEY,
    ], [], 'mt01-fixture-record');
    $tenantAuthorization = new class ($permissions) implements TenantAuthorizationRepository {
        /** @param array<int, list<string>> $permissions */
        public function __construct(private array $permissions) {}
        public function member(int $tenantId, int $memberId): ?array { return null; }
        public function activeRoles(int $tenantId, int $memberId): array { return []; }
        public function revision(int $tenantId, int $memberId): string { return '1'; }
        public function permissions(int $tenantId, int $memberId): EffectivePermissionSet
        {
            return new EffectivePermissionSet($this->permissions[$memberId] ?? []);
        }
    };
    $platformAuthorization = new class implements PlatformAuthorizationRepository {
        public function revision(int $operatorId): string { return '1'; }
        public function permissions(int $operatorId): EffectivePermissionSet { return new EffectivePermissionSet([]); }
    };
    $middleware = new PermissionMiddleware(
        new TenantAuthorizationEvaluator($tenantAuthorization, new RevisionPermissionCache()),
        new PlatformAuthorizationEvaluator($platformAuthorization, new RevisionPermissionCache()),
    );
    $targets = new DataPermissionAdapter(
        static fn(): object => throw new DataAuthorizationException(
            'AUTHZ_QUERY_DECLARATION_MISSING',
            'The fixture command does not use query authorization.',
        ),
        static function (TenantContext $context, string $resource, string $operation, array $requested) use ($pdo, $denyTargets): void {
            if ($denyTargets || $resource !== MODULE_KEY || $requested === []) {
                throw new DataAuthorizationException('AUTHZ_TARGET_NOT_FOUND', 'The requested targets are denied.');
            }
            foreach ($requested as $set) {
                if ($set->targetResourceKey !== 'fixture.scope') {
                    throw new DataAuthorizationException('AUTHZ_TARGET_TYPE_MISMATCH', 'The requested targets are denied.');
                }
                foreach ($set->targetIds as $id) {
                    $statement = $pdo->prepare(
                        'SELECT COUNT(*) FROM fixture_scope WHERE tenant_id = :tenant AND id = :id',
                    );
                    $statement->execute(['tenant' => $context->tenantId, 'id' => $id]);
                    if ((int) $statement->fetchColumn() !== 1) {
                        throw new DataAuthorizationException('AUTHZ_TARGET_NOT_FOUND', 'The requested targets are denied.');
                    }
                }
            }
        },
    );

    return new ExternalOperationHost(
        $configuration,
        new TrustedContextAdapter($configuration),
        new ModuleAvailabilityAdapter(
            $registry,
            new ModuleGuard(new PdoModuleRuntimeRepository($pdo)),
        ),
        new PermissionAdapter($middleware),
        new TypedTargetAdapter($targets),
        new AtomicOperationAdapter($pdo),
        new ProblemDetailsAdapter(),
    );
}

function responseCode(ExternalOperationResponse $response): ?string
{
    $code = $response->body['code'] ?? null;

    return is_string($code) ? $code : null;
}

$root = dirname(__DIR__, 3);
$mode = $argv[1] ?? 'run';
$database = getenv('MT01_DATABASE') ?: getenv('DB_DATABASE') ?: '';
if (preg_match('/^peanut_admin_mt01_generated_host_[a-f0-9]{16}$/D', $database) !== 1) {
    throw new RuntimeException('Generated Host database identity is invalid.');
}
$port = getenv('DB_PORT') ?: getenv('MYSQL_PORT') ?: '3306';
$password = getenv('DB_PASSWORD') ?: getenv('MYSQL_ROOT_PASSWORD') ?: 'peanut_admin_root_dev';
$admin = new PDO(
    "mysql:host=127.0.0.1;port={$port};charset=utf8mb4",
    getenv('DB_USERNAME') ?: 'root',
    $password,
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION],
);
if ($mode === 'cleanup') {
    $admin->exec("DROP DATABASE IF EXISTS `{$database}`");
    exit(0);
}
if ($mode !== 'run') {
    throw new RuntimeException('Expected run or cleanup.');
}

$temporaryRoot = getenv('MT01_TEMP_ROOT');
if (!is_string($temporaryRoot) || !is_dir($temporaryRoot)) {
    throw new RuntimeException('MT01_TEMP_ROOT must be an existing isolated directory.');
}
$generated = $temporaryRoot . '/generated-host';
$arguments = [
    '/bin/bash', $root . '/scripts/create-project', '--target', $generated,
    '--slug', 'generated-host-admin', '--display-name', 'Generated Host Admin',
    '--php-namespace', 'GeneratedHost\\Admin', '--brand', 'Generated Host',
    '--profile', 'standard-admin', '--tenant-client', 'fixture-web=/api/fixture/v1/',
    '--admin-client', 'fixture-web', '--example-module', 'remove',
];
command($arguments, $root);

$inventory = [];
$iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($generated, FilesystemIterator::SKIP_DOTS));
foreach ($iterator as $file) {
    if ($file->isFile()) {
        $relative = substr($file->getPathname(), strlen($generated) + 1);
        $inventory[$relative] = hash_file('sha256', $file->getPathname());
    }
}
$joined = implode("\n", array_keys($inventory));
assertTrue(!str_contains($joined, 'example-greeting') && !str_contains($joined, 'Example/Greeting'), 'Removed fictional example remains.');

$fixtureSource = __DIR__ . '/fixture';
$moduleRoot = $generated . '/backend/src/Modules/Fixture/Record';
$migrationRoot = $moduleRoot . '/Database/Migrations';
assertTrue(mkdir($migrationRoot, 0700, true), 'Could not mount fixture Module.');
foreach ([
    'module.json' => $moduleRoot . '/module.json',
    'FixtureRecordHost.php' => $moduleRoot . '/FixtureRecordHost.php',
    'CreateFixtureRecord.php' => $migrationRoot . '/20260812000101_create_fixture_record.php',
] as $source => $targetPath) {
    assertTrue(copy($fixtureSource . '/' . $source, $targetPath), "Could not mount fixture file: {$source}");
}

$manifest = json_decode((string) file_get_contents($moduleRoot . '/module.json'), true, 512, JSON_THROW_ON_ERROR);
assertTrue(($manifest['key'] ?? null) === MODULE_KEY, 'Fixture Module key is invalid.');
assertTrue(($manifest['database']['owned_tables'] ?? null) === ['fixture_scope', 'fixture_record', 'fixture_outbox'], 'Fixture table ownership is invalid.');

command([
    'composer', 'install', '--working-dir', $generated . '/backend',
    '--no-interaction', '--prefer-dist', '--no-progress',
], $generated);
require $generated . '/backend/vendor/autoload.php';
require_once $moduleRoot . '/FixtureRecordHost.php';

$admin->exec("CREATE DATABASE `{$database}` CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci");
$pdo = new PDO(
    "mysql:host=127.0.0.1;port={$port};dbname={$database};charset=utf8mb4",
    getenv('DB_USERNAME') ?: 'root',
    $password,
    [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ],
);

$kernelRoot = InstalledVersions::getInstallPath(KernelPackage::NAME);
$dataPermissionRoot = InstalledVersions::getInstallPath(DataPermissionPackage::NAME);
assertTrue(is_string($kernelRoot) && is_string($dataPermissionRoot), 'Installed Core package paths are unavailable.');
foreach ([$kernelRoot . '/kernel/database/migrations', $dataPermissionRoot . '/data-permission/database/migrations'] as $path) {
    $files = glob($path . '/*.php');
    assertTrue(is_array($files), "Could not scan package migrations: {$path}");
    sort($files, SORT_STRING);
    foreach ($files as $file) {
        $before = get_declared_classes();
        require_once $file;
        $classes = array_values(array_filter(
            array_diff(get_declared_classes(), $before),
            static fn(string $class): bool => is_subclass_of($class, think\migration\Migrator::class),
        ));
        assertTrue(count($classes) === 1, "Package migration class is ambiguous: {$file}");
        (new $classes[0]())->up();
    }
}
require_once $migrationRoot . '/20260812000101_create_fixture_record.php';
assertTrue(CreateFixtureRecord::moduleKey() === MODULE_KEY, 'Fixture migration owner is invalid.');
assertTrue(CreateFixtureRecord::ownedTables() === $manifest['database']['owned_tables'], 'Fixture migration tables differ from its manifest.');
(new CreateFixtureRecord())->up();

$now = '2026-08-12 00:00:00.000';
$moduleProvider = new FixtureRecordHost();
assertTrue($moduleProvider->moduleKey() === MODULE_KEY, 'Fixture Module provider key is invalid.');
insert($pdo, 'pa_module_installation', [
    'module_key' => MODULE_KEY, 'installed_version' => '1.0.0', 'manifest_schema_version' => 1,
    'manifest_digest' => hash('sha256', (string) file_get_contents($moduleRoot . '/module.json')),
    'status' => 'active', 'revision' => 1, 'installed_at' => $now, 'activated_at' => $now,
    'created_at' => $now, 'updated_at' => $now,
]);
$accounts = $members = $scopes = [];
foreach ([1, 2] as $sequence) {
    $account = insert($pdo, 'pa_account', [
        'display_name' => "Fixture {$sequence}", 'status' => 'active', 'security_revision' => 1,
        'created_at' => $now, 'updated_at' => $now,
    ]);
    $tenant = insert($pdo, 'pa_tenant', [
        'code' => "fixture-{$sequence}", 'name' => "Fixture {$sequence}",
        'display_name' => "Fixture {$sequence}", 'status' => 'active', 'locale' => 'zh-CN',
        'timezone' => 'Asia/Shanghai', 'security_revision' => 1, 'authorization_revision' => 1,
        'revision' => 1, 'activated_at' => $now, 'created_at' => $now, 'updated_at' => $now,
    ]);
    assertTrue($tenant === $sequence, 'Fixture Tenant IDs drifted.');
    $member = insert($pdo, 'pa_tenant_member', [
        'tenant_id' => $tenant, 'account_id' => $account, 'member_type' => 'internal',
        'status' => 'active', 'security_revision' => 1, 'authorization_revision' => 1,
        'joined_at' => $now, 'created_at' => $now, 'updated_at' => $now,
    ]);
    insert($pdo, 'pa_tenant_module', [
        'tenant_id' => $tenant, 'module_key' => MODULE_KEY, 'status' => 'enabled', 'source' => 'manual',
        'config_revision' => 1, 'authorization_revision' => 1, 'effective_at' => $now,
        'enabled_at' => $now, 'created_at' => $now, 'updated_at' => $now,
    ]);
    $scope = insert($pdo, 'fixture_scope', ['tenant_id' => $tenant, 'name' => "Scope {$sequence}"]);
    $accounts[$tenant] = $account;
    $members[$tenant] = $member;
    $scopes[$tenant] = $scope;
}

$permissionMap = [$members[1] => ['fixture.record.create'], $members[2] => ['fixture.record.create']];
$operation = FixtureRecordHost::operation();
$handlerCalls = $outboxCalls = 0;
$request = FixtureRecordHost::request(
    context(1, $accounts[1], $members[1], 'mt01-create'),
    'mt01-create',
    ['name' => 'Created once'],
    [target((string) $scopes[1])],
    'MT01-GENERATED-HOST-CREATE-0001',
);
$runtime = host($pdo, $permissionMap);
$created = $runtime->command(
    $operation,
    $request,
    FixtureRecordHost::handler($handlerCalls),
    FixtureRecordHost::outbox($outboxCalls, 1),
);
assertTrue($created->status === 201, 'Positive fixture command failed.');
$replay = $runtime->command($operation, $request, FixtureRecordHost::handler($handlerCalls), FixtureRecordHost::outbox($outboxCalls, 1));
assertTrue($replay->status === 201 && $replay->body === $created->body, 'Exact replay was not stable.');
assertTrue($handlerCalls === 1 && $outboxCalls === 1, 'Replay reran domain or outbox work.');
assertTrue(state($pdo) === [1, 1, 1, 1], 'Positive command did not commit one atomic state set.');

$conflict = $runtime->command(
    $operation,
    FixtureRecordHost::request(context(1, $accounts[1], $members[1], 'mt01-conflict'), 'mt01-conflict', ['name' => 'Changed'], [target((string) $scopes[1])], 'MT01-GENERATED-HOST-CREATE-0001'),
    FixtureRecordHost::handler($handlerCalls),
);
assertTrue($conflict->status === 409 && responseCode($conflict) === 'IDEMPOTENCY_KEY_REUSED', 'Changed replay did not conflict.');
assertTrue($handlerCalls === 1 && state($pdo) === [1, 1, 1, 1], 'Conflict reached a Core write.');

$denials = [
    'wrong-client' => [host($pdo, $permissionMap), context(1, $accounts[1], $members[1], 'deny-client', 'other-web'), (string) $scopes[1], 'AUTHENTICATION_REQUIRED'],
    'cross-tenant' => [host($pdo, $permissionMap), context(2, $accounts[2], $members[2], 'deny-tenant'), (string) $scopes[1], 'AUTHZ_DATA_DENIED'],
    'permission' => [host($pdo, [$members[1] => []]), context(1, $accounts[1], $members[1], 'deny-permission'), (string) $scopes[1], 'AUTHZ_PERMISSION_DENIED'],
    'typed-target' => [host($pdo, $permissionMap, true), context(1, $accounts[1], $members[1], 'deny-target'), (string) $scopes[1], 'AUTHZ_DATA_DENIED'],
];
foreach ($denials as $name => [$denialHost, $denialContext, $scope, $expectedCode]) {
    $before = state($pdo);
    $calls = 0;
    $response = $denialHost->command(
        $operation,
        FixtureRecordHost::request($denialContext, "deny-{$name}", ['name' => 'Denied'], [target($scope)], "MT01-DENIED-{$name}-0001"),
        FixtureRecordHost::handler($calls),
    );
    assertTrue(responseCode($response) === $expectedCode, "{$name} denial code drifted.");
    assertTrue($calls === 0 && state($pdo) === $before, "{$name} denial reached a Core write.");
}

$pdo->prepare("UPDATE pa_tenant_module SET status = 'disabled' WHERE tenant_id = 1 AND module_key = :module")
    ->execute(['module' => MODULE_KEY]);
$calls = 0;
$disabled = $runtime->command(
    $operation,
    FixtureRecordHost::request(context(1, $accounts[1], $members[1], 'deny-module'), 'deny-module', ['name' => 'Disabled'], [target((string) $scopes[1])], 'MT01-DENIED-MODULE-0001'),
    FixtureRecordHost::handler($calls),
);
assertTrue(responseCode($disabled) === 'MODULE_UNAVAILABLE' && $calls === 0, 'Disabled Module did not fail before domain work.');
$pdo->prepare("UPDATE pa_tenant_module SET status = 'enabled' WHERE tenant_id = 1 AND module_key = :module")
    ->execute(['module' => MODULE_KEY]);

foreach (['domain', 'outbox', 'completion'] as $failure) {
    $before = state($pdo);
    $calls = $outboxFailureCalls = 0;
    $response = $runtime->command(
        $operation,
        FixtureRecordHost::request(context(1, $accounts[1], $members[1], "fail-{$failure}"), "fail-{$failure}", ['name' => "Fail {$failure}"], [target((string) $scopes[1])], "MT01-FAILURE-{$failure}-0001"),
        FixtureRecordHost::handler($calls, $failure === 'domain' || $failure === 'completion' ? $failure : null),
        FixtureRecordHost::outbox($outboxFailureCalls, 1, $failure === 'outbox'),
    );
    $expectedStatus = $failure === 'completion' ? 409 : 500;
    assertTrue($response->status === $expectedStatus, "{$failure} failure status drifted.");
    assertTrue(state($pdo) === $before, "{$failure} failure left partial state.");
}

$pdo->exec("DELETE FROM pa_tenant_module WHERE module_key = 'fixture.record'");
$pdo->exec("DELETE FROM pa_module_installation WHERE module_key = 'fixture.record'");
(new CreateFixtureRecord())->down();
foreach (['fixture_scope', 'fixture_record', 'fixture_outbox'] as $table) {
    $statement = $pdo->prepare('SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = :table');
    $statement->execute(['table' => $table]);
    assertTrue((int) $statement->fetchColumn() === 0, "Unmount left owned table: {$table}");
}
removeFixture($moduleRoot);
assertTrue(is_file($generated . '/backend/public/index.php'), 'Generated Host smoke entry disappeared after unmount.');
assertTrue(!str_contains(implode("\n", array_keys($inventory)), 'fixture.record'), 'Original generated inventory unexpectedly owned fixture files.');

fwrite(STDOUT, "MT01 generated Host fixture: OK\n");
