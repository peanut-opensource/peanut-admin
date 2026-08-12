<?php

declare(strict_types=1);

use Composer\InstalledVersions;
use Phinx\Config\Config;
use Phinx\Migration\Manager;
use PeanutAdmin\DataPermission\Package as DataPermissionPackage;
use PeanutAdmin\Kernel\Api\RequestId;
use PeanutAdmin\Kernel\Auth\AuthException;
use PeanutAdmin\Kernel\Auth\Persistence\PdoTenantAuthRepository;
use PeanutAdmin\Kernel\Auth\SystemClock;
use PeanutAdmin\Kernel\Auth\TenantAuthService;
use PeanutAdmin\Kernel\Auth\TenantClientRegistry;
use PeanutAdmin\Kernel\Auth\TokenIssuer;
use PeanutAdmin\Kernel\Authorization\PdoTenantAuthorizationRepository;
use PeanutAdmin\Kernel\Http\TenantAuthEndpoint;
use PeanutAdmin\Kernel\Identity\PasswordHasher;
use PeanutAdmin\Kernel\Package as KernelPackage;
use PeanutAdmin\Kernel\Persistence\Pdo\PdoTransactionManager;
use think\console\Input;
use think\migration\NullOutput;

const MODULE_KEY = 'fixture.record';
const PERMISSION_KEY = 'fixture.record.read';
const CLIENT_KEY = 'fixture-web';

function requiredEnvironment(string $name): string
{
    $value = getenv($name);
    if (!is_string($value) || $value === '') {
        throw new RuntimeException("{$name} is required.");
    }

    return $value;
}

function databaseName(): string
{
    $database = requiredEnvironment('MT01_DATABASE');
    if (preg_match('/^peanut_admin_mt01_admin_web_[a-f0-9]{16}$/D', $database) !== 1) {
        throw new RuntimeException('MT01 Admin Web database identity is invalid.');
    }

    return $database;
}

function adminPdo(): PDO
{
    $port = getenv('DB_PORT') ?: getenv('MYSQL_PORT') ?: '3306';

    return new PDO(
        "mysql:host=127.0.0.1;port={$port};charset=utf8mb4",
        getenv('DB_USERNAME') ?: 'root',
        getenv('DB_PASSWORD') ?: getenv('MYSQL_ROOT_PASSWORD') ?: 'peanut_admin_root_dev',
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION],
    );
}

function fixturePdo(): PDO
{
    $port = getenv('DB_PORT') ?: getenv('MYSQL_PORT') ?: '3306';

    return new PDO(
        "mysql:host=127.0.0.1;port={$port};dbname=" . databaseName() . ';charset=utf8mb4',
        getenv('DB_USERNAME') ?: 'root',
        getenv('DB_PASSWORD') ?: getenv('MYSQL_ROOT_PASSWORD') ?: 'peanut_admin_root_dev',
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ],
    );
}

function generatedRoot(): string
{
    $requested = requiredEnvironment('MT01_GENERATED_HOST_ROOT');
    $physical = realpath($requested);
    if (!is_string($physical) || !is_dir($physical) || is_link($requested)) {
        throw new RuntimeException('Generated Host root is unavailable or unsafe.');
    }
    foreach (['peanut-project.json', 'backend/vendor/autoload.php'] as $required) {
        if (!is_file($physical . '/' . $required)) {
            throw new RuntimeException("Generated Host artifact is incomplete: {$required}");
        }
    }

    return $physical;
}

/** @return array{manifest: string, provider: string, migration: string} */
function mountGeneratedHostFixture(string $root, string $repository): array
{
    $source = $repository . '/tests/mt01/generated-host/fixture';
    $module = $root . '/backend/src/Modules/Fixture/Record';
    $migrations = $module . '/Database/Migrations';
    if (!is_dir($source) || (!is_dir($migrations) && !mkdir($migrations, 0700, true))) {
        throw new RuntimeException('Generated Host fixture mount is unavailable.');
    }

    $paths = [
        'manifest' => [$source . '/module.json', $module . '/module.json'],
        'provider' => [$source . '/FixtureRecordHost.php', $module . '/FixtureRecordHost.php'],
        'migration' => [
            $source . '/CreateFixtureRecord.php',
            $migrations . '/20260812000101_create_fixture_record.php',
        ],
    ];
    foreach ($paths as [$fixture, $mounted]) {
        if (!is_file($fixture) || file_exists($mounted) || is_link($mounted) || !symlink($fixture, $mounted)) {
            throw new RuntimeException('Generated Host fixture mount failed.');
        }
        if (realpath($mounted) !== realpath($fixture)) {
            throw new RuntimeException('Generated Host fixture mount identity drifted.');
        }
    }

    return array_map(static fn(array $path): string => $path[1], $paths);
}

/** @return Manager */
function migrationManager(PDO $pdo, string $path, string $environment, string $ledger): Manager
{
    return new Manager(new Config([
        'paths' => ['migrations' => $path],
        'environments' => [
            'default_environment' => $environment,
            'default_migration_table' => $ledger,
            $environment => [
                'adapter' => 'mysql',
                'connection' => $pdo,
                'name' => databaseName(),
                'migration_table' => $ledger,
            ],
        ],
        'version_order' => Config::VERSION_ORDER_CREATION_TIME,
    ]), new Input([]), new NullOutput());
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

function prepareFixture(): void
{
    $root = generatedRoot();
    $repository = requiredEnvironment('MT01_REPOSITORY_ROOT');
    require $root . '/backend/vendor/autoload.php';

    $fixture = mountGeneratedHostFixture($root, $repository);
    $manifest = json_decode((string) file_get_contents($fixture['manifest']), true, 512, JSON_THROW_ON_ERROR);
    if (($manifest['key'] ?? null) !== MODULE_KEY
        || ($manifest['database']['owned_tables'] ?? null) !== ['fixture_scope', 'fixture_record', 'fixture_outbox']) {
        throw new RuntimeException('Generated Host fixture manifest drifted.');
    }
    require_once $fixture['provider'];
    $provider = new GeneratedHost\Admin\Modules\Fixture\Record\FixtureRecordHost();
    if ($provider->moduleKey() !== MODULE_KEY) {
        throw new RuntimeException('Generated Host fixture provider drifted.');
    }

    $database = databaseName();
    $admin = adminPdo();
    $admin->exec("CREATE DATABASE `{$database}` CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci");
    $pdo = fixturePdo();

    $kernelRoot = InstalledVersions::getInstallPath(KernelPackage::NAME);
    $dataPermissionRoot = InstalledVersions::getInstallPath(DataPermissionPackage::NAME);
    if (!is_string($kernelRoot) || !is_string($dataPermissionRoot)) {
        throw new RuntimeException('Generated Host package installation paths are unavailable.');
    }
    migrationManager($pdo, $kernelRoot . '/kernel/database/migrations', 'kernel', 'pa_kernel_migration')->migrate('kernel');
    migrationManager(
        $pdo,
        $dataPermissionRoot . '/data-permission/database/migrations',
        'data_permission',
        'pa_data_permission_migration',
    )->migrate('data_permission');

    $fixtureMigration = $fixture['migration'];
    require_once $fixtureMigration;
    if (CreateFixtureRecord::moduleKey() !== MODULE_KEY) {
        throw new RuntimeException('Generated Host fixture migration owner drifted.');
    }
    migrationManager(
        $pdo,
        dirname($fixtureMigration),
        'fixture_record',
        'pa_fixture_record_migration',
    )->migrate('fixture_record');

    $now = gmdate('Y-m-d H:i:s.v');
    $accountId = insert($pdo, 'pa_account', [
        'display_name' => 'MT01 Browser Owner',
        'status' => 'active',
        'security_revision' => 1,
        'created_at' => $now,
        'updated_at' => $now,
    ]);
    insert($pdo, 'pa_credential', [
        'account_id' => $accountId,
        'kind' => 'email_password',
        'identifier_type' => 'email',
        'identifier_normalized' => strtolower(requiredEnvironment('MT01_FIXTURE_EMAIL')),
        'secret_hash' => (new PasswordHasher())->hash(requiredEnvironment('MT01_FIXTURE_PASSWORD')),
        'status' => 'active',
        'verified_at' => $now,
        'secret_changed_at' => $now,
        'created_at' => $now,
        'updated_at' => $now,
    ]);
    $permissionId = insert($pdo, 'pa_permission', [
        'key' => PERMISSION_KEY,
        'module_key' => MODULE_KEY,
        'type' => 'api',
        'name' => 'Read fixture records',
        'description' => 'MT01 product-neutral browser fixture permission',
        'risk_level' => 'normal',
        'status' => 'active',
        'manifest_version' => '1.0.0',
        'created_at' => $now,
        'updated_at' => $now,
    ]);
    insert($pdo, 'pa_module_installation', [
        'module_key' => MODULE_KEY,
        'installed_version' => '1.0.0',
        'manifest_schema_version' => 1,
        'manifest_digest' => hash_file('sha256', $fixture['manifest']),
        'status' => 'active',
        'revision' => 1,
        'installed_at' => $now,
        'activated_at' => $now,
        'created_at' => $now,
        'updated_at' => $now,
    ]);

    foreach ([
        ['alpha', 'Alpha Tenant', 'Alpha Member', 'Alpha Row'],
        ['beta', 'Beta Tenant', 'Beta Member', 'Beta Row'],
    ] as [$code, $tenantLabel, $memberLabel, $recordLabel]) {
        $tenantId = insert($pdo, 'pa_tenant', [
            'code' => $code,
            'name' => $tenantLabel,
            'display_name' => $tenantLabel,
            'status' => 'active',
            'locale' => 'en-US',
            'timezone' => 'UTC',
            'security_revision' => 1,
            'authorization_revision' => 1,
            'revision' => 1,
            'activated_at' => $now,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        $memberId = insert($pdo, 'pa_tenant_member', [
            'tenant_id' => $tenantId,
            'account_id' => $accountId,
            'display_name' => $memberLabel,
            'member_type' => 'internal',
            'status' => 'active',
            'security_revision' => 1,
            'authorization_revision' => 1,
            'joined_at' => $now,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        $roleId = insert($pdo, 'pa_role', [
            'tenant_id' => $tenantId,
            'key' => 'fixture.reader',
            'name' => 'Fixture Reader',
            'is_builtin' => 0,
            'status' => 'active',
            'authorization_revision' => 1,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        insert($pdo, 'pa_member_role', [
            'tenant_id' => $tenantId,
            'tenant_member_id' => $memberId,
            'role_id' => $roleId,
            'assigned_at' => $now,
        ]);
        insert($pdo, 'pa_role_permission', [
            'tenant_id' => $tenantId,
            'role_id' => $roleId,
            'permission_id' => $permissionId,
            'granted_at' => $now,
        ]);
        insert($pdo, 'pa_tenant_module', [
            'tenant_id' => $tenantId,
            'module_key' => MODULE_KEY,
            'status' => 'enabled',
            'source' => 'manual',
            'config_revision' => 1,
            'authorization_revision' => 1,
            'effective_at' => $now,
            'enabled_at' => $now,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        $scopeId = insert($pdo, 'fixture_scope', [
            'tenant_id' => $tenantId,
            'name' => "{$tenantLabel} Scope",
        ]);
        insert($pdo, 'fixture_record', [
            'tenant_id' => $tenantId,
            'scope_id' => $scopeId,
            'name' => $recordLabel,
            'revision' => 1,
        ]);
    }
}

function requestId(): string
{
    $candidate = $_SERVER['HTTP_X_REQUEST_ID'] ?? null;

    return RequestId::fromHeader(is_string($candidate) ? $candidate : null)->value;
}

function currentRequestId(): string
{
    static $value = null;
    if (!is_string($value)) {
        $value = requestId();
    }

    return $value;
}

/** @param array<string, mixed>|null $body @param array<string, string> $headers */
function respond(int $status, ?array $body, array $headers = []): never
{
    http_response_code($status);
    header('Cache-Control: no-store');
    header('X-Request-Id: ' . currentRequestId());
    foreach ($headers as $name => $value) {
        if ($name !== 'Set-Cookie') {
            header("{$name}: {$value}", false);
        }
    }
    if ($body !== null) {
        header('Content-Type: application/json');
        echo json_encode($body, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES);
    }
    exit;
}

function problem(int $status, string $code, string $title, string $detail): never
{
    http_response_code($status);
    header('Cache-Control: no-store');
    header('Content-Type: application/problem+json');
    header('X-Request-Id: ' . currentRequestId());
    echo json_encode([
        'type' => '/docs/api/problems/' . strtolower(str_replace('_', '-', $code)),
        'title' => $title,
        'status' => $status,
        'detail' => $detail,
        'code' => $code,
        'request_id' => currentRequestId(),
    ], JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES);
    exit;
}

/** @return array<string, mixed> */
function jsonBody(): array
{
    $contents = file_get_contents('php://input');
    if (!is_string($contents) || $contents === '') {
        return [];
    }
    $decoded = json_decode($contents, true, 512, JSON_THROW_ON_ERROR);

    return is_array($decoded) ? $decoded : [];
}

function authService(PDO $pdo): TenantAuthService
{
    return new TenantAuthService(
        new PdoTransactionManager($pdo),
        new PdoTenantAuthRepository($pdo),
        new PasswordHasher(),
        new SystemClock(),
        new TokenIssuer(),
        requiredEnvironment('MT01_AUTH_HMAC_KEY'),
        new TenantClientRegistry([CLIENT_KEY]),
        CLIENT_KEY,
    );
}

function moduleAvailable(PDO $pdo, int $tenantId): bool
{
    $statement = $pdo->prepare(<<<'SQL'
SELECT COUNT(*)
FROM pa_tenant_module tm
JOIN pa_module_installation mi
  ON mi.module_key = tm.module_key AND mi.status = 'active'
WHERE tm.tenant_id = :tenant AND tm.module_key = :module AND tm.status = 'enabled'
  AND (tm.effective_at IS NULL OR tm.effective_at <= CURRENT_TIMESTAMP(3))
  AND (tm.expires_at IS NULL OR tm.expires_at > CURRENT_TIMESTAMP(3))
SQL);
    $statement->execute(['tenant' => $tenantId, 'module' => MODULE_KEY]);

    return (int) $statement->fetchColumn() === 1;
}

function bearer(): string
{
    $authorization = $_SERVER['HTTP_AUTHORIZATION'] ?? null;
    if (!is_string($authorization)
        || preg_match('/^Bearer ([^\s]+)$/iD', $authorization, $matches) !== 1) {
        problem(401, 'AUTH_TOKEN_INVALID', 'Authentication required', 'A valid bearer token is required.');
    }

    return $matches[1];
}

/** @return array<string, mixed> */
function contextPayload(PDO $pdo, PeanutAdmin\Kernel\Auth\TenantContext $context): array
{
    $statement = $pdo->prepare(<<<'SQL'
SELECT a.display_name AS account_label, t.code, t.display_name AS tenant_label,
       m.display_name AS member_label
FROM pa_tenant_member m
JOIN pa_account a ON a.id = m.account_id
JOIN pa_tenant t ON t.id = m.tenant_id
WHERE m.tenant_id = :tenant AND m.id = :member AND m.account_id = :account
  AND m.status = 'active' AND a.status = 'active' AND t.status = 'active'
SQL);
    $statement->execute([
        'tenant' => $context->tenantId,
        'member' => $context->memberId,
        'account' => $context->accountId,
    ]);
    $row = $statement->fetch();
    if (!is_array($row)) {
        throw new RuntimeException('Tenant context is unavailable.');
    }
    $permissions = (new PdoTenantAuthorizationRepository($pdo))
        ->permissions($context->tenantId, $context->memberId)
        ->keys();
    $authorizationRevision = (new PdoTenantAuthorizationRepository($pdo))
        ->revision($context->tenantId, $context->memberId);
    $modules = moduleAvailable($pdo, $context->tenantId) ? ['core', MODULE_KEY] : ['core'];
    sort($permissions, SORT_STRING);
    sort($modules, SORT_STRING);

    return [
        'audience' => 'tenant',
        'account' => ['id' => (string) $context->accountId, 'display_name' => (string) $row['account_label']],
        'tenant' => [
            'id' => (string) $context->tenantId,
            'code' => (string) $row['code'],
            'display_name' => (string) $row['tenant_label'],
        ],
        'member' => ['id' => (string) $context->memberId, 'display_name' => (string) $row['member_label']],
        'module_keys' => array_values(array_unique($modules)),
        'permission_keys' => $permissions,
        'authorization_revision' => $authorizationRevision,
    ];
}

function dispatch(): never
{
    $pdo = fixturePdo();
    $method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
    $path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
    if (!is_string($path)) {
        problem(404, 'RESOURCE_NOT_FOUND', 'Resource not found', 'The requested resource was not found.');
    }
    if ($method === 'GET' && $path === '/health') {
        respond(200, ['status' => 'ok']);
    }

    try {
        $endpoint = new TenantAuthEndpoint(authService($pdo));
        if ($method === 'POST' && $path === '/api/v1/auth/login') {
            $body = jsonBody();
            $result = $endpoint->login(
                (string) ($body['email'] ?? ''),
                (string) ($body['password'] ?? ''),
                isset($body['tenant_code']) && is_string($body['tenant_code']) ? $body['tenant_code'] : null,
                $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1',
                $_SERVER['HTTP_USER_AGENT'] ?? null,
                currentRequestId(),
            );
            respond($result->status, $result->body, $result->headers);
        }
        if ($method === 'POST' && $path === '/api/v1/auth/tenants/select') {
            $body = jsonBody();
            $tenantId = filter_var($body['tenant_id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
            if (!is_int($tenantId)) {
                problem(422, 'VALIDATION_FAILED', 'Validation failed', 'Tenant identifier is invalid.');
            }
            $result = $endpoint->selectTenant(
                (string) ($body['challenge_token'] ?? ''),
                $tenantId,
                $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1',
                $_SERVER['HTTP_USER_AGENT'] ?? null,
                currentRequestId(),
            );
            respond($result->status, $result->body, $result->headers);
        }
        if ($method === 'GET' && $path === '/api/v1/auth/context') {
            $context = authService($pdo)->context(bearer(), currentRequestId());
            respond(200, ['data' => contextPayload($pdo, $context), 'meta' => ['request_id' => currentRequestId()]]);
        }
        if ($method === 'GET' && $path === '/api/v1/menus') {
            authService($pdo)->context(bearer(), currentRequestId());
            respond(200, ['data' => [], 'meta' => ['request_id' => currentRequestId()]]);
        }
        if ($method === 'GET' && $path === '/api/fixture/v1/records') {
            $context = authService($pdo)->context(bearer(), currentRequestId());
            $permissions = (new PdoTenantAuthorizationRepository($pdo))->permissions($context->tenantId, $context->memberId);
            if (!$permissions->allows(PERMISSION_KEY)) {
                problem(403, 'AUTHZ_PERMISSION_DENIED', 'Permission denied', 'The required permission is unavailable.');
            }
            if (!moduleAvailable($pdo, $context->tenantId)) {
                problem(409, 'MODULE_UNAVAILABLE', 'Module unavailable', 'The requested Module is unavailable.');
            }
            $statement = $pdo->prepare(
                'SELECT id, name FROM fixture_record WHERE tenant_id = :tenant ORDER BY id',
            );
            $statement->execute(['tenant' => $context->tenantId]);
            $items = array_map(
                static fn(array $row): array => ['id' => (string) $row['id'], 'name' => (string) $row['name']],
                $statement->fetchAll(),
            );
            respond(200, ['data' => ['items' => $items], 'meta' => ['request_id' => currentRequestId()]]);
        }
        if ($method === 'GET' && preg_match('~^/api/fixture/v1/records/([1-9][0-9]*)$~D', $path, $matches) === 1) {
            $context = authService($pdo)->context(bearer(), currentRequestId());
            $statement = $pdo->prepare(
                'SELECT id, name FROM fixture_record WHERE tenant_id = :tenant AND id = :record',
            );
            $statement->execute(['tenant' => $context->tenantId, 'record' => $matches[1]]);
            $row = $statement->fetch();
            if (!is_array($row)) {
                problem(404, 'RESOURCE_NOT_FOUND', 'Resource not found', 'The requested resource was not found.');
            }
            respond(200, ['data' => ['id' => (string) $row['id'], 'name' => (string) $row['name']]]);
        }
        if ($method === 'POST' && $path === '/__mt01/permissions/deny') {
            $control = $_SERVER['HTTP_X_MT01_CONTROL_KEY'] ?? null;
            if (!is_string($control) || !hash_equals(requiredEnvironment('MT01_CONTROL_KEY'), $control)) {
                problem(404, 'RESOURCE_NOT_FOUND', 'Resource not found', 'The requested resource was not found.');
            }
            $statement = $pdo->prepare(<<<'SQL'
DELETE rp FROM pa_role_permission rp
JOIN pa_permission p ON p.id = rp.permission_id
WHERE rp.tenant_id = 1 AND p.`key` = :permission
SQL);
            $statement->execute(['permission' => PERMISSION_KEY]);
            $pdo->exec('UPDATE pa_tenant_member SET authorization_revision = authorization_revision + 1 WHERE tenant_id = 1');
            respond(204, null);
        }
    } catch (AuthException $exception) {
        problem($exception->httpStatus, $exception->errorCode, 'Authentication failed', $exception->getMessage());
    } catch (Throwable) {
        problem(500, 'INTERNAL_ERROR', 'Internal error', 'The request could not be completed.');
    }

    problem(404, 'RESOURCE_NOT_FOUND', 'Resource not found', 'The requested resource was not found.');
}

$mode = $argv[1] ?? null;
if (PHP_SAPI === 'cli' && $mode === 'prepare') {
    prepareFixture();
    exit(0);
}
if (PHP_SAPI === 'cli' && $mode === 'cleanup') {
    $database = databaseName();
    adminPdo()->exec("DROP DATABASE IF EXISTS `{$database}`");
    exit(0);
}
if (PHP_SAPI !== 'cli-server') {
    throw new RuntimeException('Expected prepare, cleanup, or PHP built-in server mode.');
}

require generatedRoot() . '/backend/vendor/autoload.php';
dispatch();
