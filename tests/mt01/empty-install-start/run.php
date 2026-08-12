<?php

declare(strict_types=1);

use Composer\InstalledVersions;
use Phinx\Config\Config;
use Phinx\Migration\Manager;
use PeanutAdmin\DataPermission\Package as DataPermissionPackage;
use PeanutAdmin\Kernel\Identity\PasswordHasher;
use PeanutAdmin\Kernel\Migration\OwnedMigration;
use PeanutAdmin\Kernel\Persistence\Pdo\PdoAuditRepository;
use PeanutAdmin\Kernel\Persistence\Pdo\PdoIdentityRepository;
use PeanutAdmin\Kernel\Persistence\Pdo\PdoMembershipRepository;
use PeanutAdmin\Kernel\Persistence\Pdo\PdoPlatformRepository;
use PeanutAdmin\Kernel\Persistence\Pdo\PdoTenantRepository;
use PeanutAdmin\Kernel\Persistence\Pdo\PdoTransactionManager;
use PeanutAdmin\Kernel\Platform\Bootstrap\BootstrapService;
use PeanutAdmin\Kernel\Package as KernelPackage;
use think\console\Input;
use think\migration\NullOutput;

const DATABASE_PREFIX = 'peanut_admin_mt01_empty_install_';

/** @param list<string> $arguments @return array{repository-root: string, source-commit: string, generated-host-root?: string} */
function options(array $arguments): array
{
    $allowed = ['repository-root', 'source-commit', 'generated-host-root'];
    $options = [];
    for ($index = 0; $index < count($arguments); ++$index) {
        $argument = $arguments[$index];
        if (!str_starts_with($argument, '--') || !in_array(substr($argument, 2), $allowed, true)) {
            throw new RuntimeException("Unknown argument: {$argument}");
        }
        $key = substr($argument, 2);
        if (isset($options[$key])) {
            throw new RuntimeException("Duplicate argument: --{$key}");
        }
        $value = $arguments[++$index] ?? null;
        if (!is_string($value) || $value === '' || str_starts_with($value, '--')) {
            throw new RuntimeException("Missing value for --{$key}.");
        }
        $options[$key] = $value;
    }
    foreach (['repository-root', 'source-commit'] as $required) {
        if (!isset($options[$required])) {
            throw new RuntimeException("Missing argument: --{$required}");
        }
    }

    /** @var array{repository-root: string, source-commit: string, generated-host-root?: string} */
    return $options;
}

/**
 * @param list<string> $command
 * @param array<string, string> $environment
 * @return array{code: int, stdout: string, stderr: string}
 */
function command(array $command, string $workingDirectory, array $environment = []): array
{
    $pipes = [];
    $process = proc_open($command, [
        0 => ['pipe', 'r'],
        1 => ['pipe', 'w'],
        2 => ['pipe', 'w'],
    ], $pipes, $workingDirectory, $environment === [] ? null : [...getenv(), ...$environment]);
    if (!is_resource($process)) {
        throw new RuntimeException('Could not start fixture command.');
    }
    fclose($pipes[0]);
    $stdout = stream_get_contents($pipes[1]);
    $stderr = stream_get_contents($pipes[2]);
    fclose($pipes[1]);
    fclose($pipes[2]);

    return [
        'code' => proc_close($process),
        'stdout' => is_string($stdout) ? $stdout : '',
        'stderr' => is_string($stderr) ? $stderr : '',
    ];
}

/** @param list<string> $command @param array<string, string> $environment */
function requireCommand(array $command, string $workingDirectory, array $environment = []): string
{
    $result = command($command, $workingDirectory, $environment);
    if ($result['code'] !== 0) {
        throw new RuntimeException(sprintf(
            "Command failed (%s):\n%s%s",
            implode(' ', $command),
            $result['stdout'],
            $result['stderr'],
        ));
    }

    return $result['stdout'];
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

/** @return array<string, string> */
function inventory(string $root): array
{
    $inventory = [];
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS),
    );
    foreach ($iterator as $file) {
        if ($file->isLink() || !$file->isFile()) {
            throw new RuntimeException('Generated Host must contain regular files only.');
        }
        $relative = substr($file->getPathname(), strlen($root) + 1);
        $digest = hash_file('sha256', $file->getPathname());
        if (!is_string($digest)) {
            throw new RuntimeException("Could not hash generated file: {$relative}");
        }
        $inventory[$relative] = $digest;
    }
    ksort($inventory, SORT_STRING);

    return $inventory;
}

function copyGeneratedHost(string $source, string $target): void
{
    foreach (['peanut-project.json', 'backend/composer.json', 'package.json'] as $required) {
        if (!is_file($source . '/' . $required)) {
            throw new RuntimeException("Explicit generated Host is incomplete: {$required}");
        }
    }
    foreach (['vendor', 'node_modules', '.peanut-project-generation'] as $forbidden) {
        if (file_exists($source . '/' . $forbidden)) {
            throw new RuntimeException("Explicit generated Host is not a clean artifact: {$forbidden}");
        }
    }
    if (!mkdir($target, 0700, true)) {
        throw new RuntimeException('Could not create independent generated Host target.');
    }
    requireCommand(['cp', '-R', $source . '/.', $target], dirname($target));
    inventory($target);
}

/** @return list<string> */
function generationArguments(string $target): array
{
    return [
        '--target', $target,
        '--slug', 'mt01-empty-install',
        '--display-name', 'MT01 Empty Install Fixture',
        '--php-namespace', 'Mt01\EmptyInstall',
        '--brand', 'MT01 Fixture',
        '--profile', 'standard-admin',
        '--tenant-client', 'admin-web=/api/admin/v1/',
        '--admin-client', 'admin-web',
        '--feature', 'settings',
        '--feature', 'reference-codes',
        '--example-module', 'remove',
    ];
}

function projectMetadata(string $project): array
{
    $metadata = json_decode(
        (string) file_get_contents($project . '/peanut-project.json'),
        true,
        512,
        JSON_THROW_ON_ERROR,
    );
    if (!is_array($metadata)) {
        throw new RuntimeException('Generated Host metadata is invalid.');
    }

    return $metadata;
}

function assertProjectIdentity(string $project, string $sourceCommit, bool $fixedFixtureParameters): void
{
    $metadata = projectMetadata($project);
    if (($metadata['peanut_admin']['input_commit'] ?? null) !== $sourceCommit
        || ($metadata['project']['example_module'] ?? null) !== 'removed'
        || ($metadata['secrets']['embedded'] ?? null) !== false) {
        throw new RuntimeException('Generated Host identity does not match the MT01 empty-install fixture.');
    }
    if ($fixedFixtureParameters
        && (($metadata['project']['php_namespace'] ?? null) !== 'Mt01\EmptyInstall'
            || ($metadata['project']['tenant_clients'] ?? null) !== [[
                'key' => 'admin-web',
                'api_prefix' => '/api/admin/v1/',
            ]])) {
        throw new RuntimeException('Generated Host parameters drifted from the independent MT01 fixture.');
    }
}

function assertNonOverwriteBoundary(string $source, string $project, bool $fixedFixtureParameters): void
{
    $before = inventory($project);
    $arguments = generationArguments($project);
    if (!$fixedFixtureParameters) {
        $metadata = projectMetadata($project);
        $projectIdentity = $metadata['project'] ?? null;
        if (!is_array($projectIdentity)) {
            throw new RuntimeException('Explicit generated Host project identity is invalid.');
        }
        $arguments = ['--target', $project];
        foreach (['slug' => '--slug', 'display_name' => '--display-name', 'php_namespace' => '--php-namespace', 'brand' => '--brand', 'profile' => '--profile'] as $key => $option) {
            $value = $projectIdentity[$key] ?? null;
            if (!is_string($value) || $value === '') {
                throw new RuntimeException("Explicit generated Host project identity is missing: {$key}");
            }
            array_push($arguments, $option, $value);
        }
        $tenantClients = $projectIdentity['tenant_clients'] ?? null;
        if (!is_array($tenantClients) || !array_is_list($tenantClients) || $tenantClients === []) {
            throw new RuntimeException('Explicit generated Host Tenant Clients are invalid.');
        }
        foreach ($tenantClients as $client) {
            if (!is_array($client) || !is_string($client['key'] ?? null) || !is_string($client['api_prefix'] ?? null)) {
                throw new RuntimeException('Explicit generated Host Tenant Client is invalid.');
            }
            array_push($arguments, '--tenant-client', $client['key'] . '=' . $client['api_prefix']);
        }
        $adminClient = $projectIdentity['admin_client_key'] ?? null;
        if (!is_string($adminClient) || $adminClient === '') {
            throw new RuntimeException('Explicit generated Host admin Client is invalid.');
        }
        array_push($arguments, '--admin-client', $adminClient);
        $features = $projectIdentity['features'] ?? null;
        if (!is_array($features) || !array_is_list($features)) {
            throw new RuntimeException('Explicit generated Host feature identity is invalid.');
        }
        foreach ($features as $feature) {
            if (!is_string($feature) || $feature === '') {
                throw new RuntimeException('Explicit generated Host feature identity is invalid.');
            }
            array_push($arguments, '--feature', $feature);
        }
        array_push($arguments, '--example-module', 'remove');
    }
    $result = command(
        ['/bin/bash', $source . '/scripts/create-project', ...$arguments],
        $source,
    );
    if ($result['code'] === 0 || !str_contains($result['stderr'], 'PROJECT_TARGET_NOT_EMPTY')) {
        throw new RuntimeException('Generator did not fail closed on its existing project update boundary.');
    }
    if (inventory($project) !== $before) {
        throw new RuntimeException('Rejected project update changed existing generated bytes.');
    }
}

/** @return Manager */
function migrationManager(PDO $pdo, string $database, string $path, string $environment, string $ledger): Manager
{
    return new Manager(new Config([
        'paths' => ['migrations' => $path],
        'environments' => [
            'default_environment' => $environment,
            'default_migration_table' => $ledger,
            $environment => [
                'adapter' => 'mysql',
                'connection' => $pdo,
                'name' => $database,
                'migration_table' => $ledger,
            ],
        ],
        'version_order' => Config::VERSION_ORDER_CREATION_TIME,
    ]), new Input([]), new NullOutput());
}

/** @return int */
function ledgerRowCount(PDO $pdo, string $table): int
{
    if (preg_match('/^[a-z][a-z0-9_]{0,63}$/D', $table) !== 1) {
        throw new RuntimeException('Unsafe table identifier.');
    }
    $statement = $pdo->query("SELECT COUNT(*) FROM `{$table}`");
    if ($statement === false) {
        throw new RuntimeException("Could not count table: {$table}");
    }

    return (int) $statement->fetchColumn();
}

function databaseIsEmpty(PDO $pdo): bool
{
    $statement = $pdo->query(
        'SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE()',
    );
    if ($statement === false) {
        throw new RuntimeException('Could not inspect the fresh database.');
    }

    return (int) $statement->fetchColumn() === 0;
}

function assertOwnedMigrations(string $moduleKey, string $path, array $ownedTableOwners): void
{
    $files = glob($path . '/*.php');
    if (!is_array($files) || $files === []) {
        throw new RuntimeException("Module migration set is empty: {$moduleKey}");
    }
    sort($files, SORT_STRING);
    foreach ($files as $file) {
        $before = get_declared_classes();
        require_once $file;
        $classes = array_values(array_diff(get_declared_classes(), $before));
        $owned = array_values(array_filter(
            $classes,
            static fn(string $class): bool => is_subclass_of($class, OwnedMigration::class),
        ));
        if (count($owned) !== 1 || $owned[0]::moduleKey() !== $moduleKey || !$owned[0]::reversible()) {
            throw new RuntimeException("Migration ownership contract is invalid: {$file}");
        }
        foreach ($owned[0]::ownedTables() as $table) {
            if (($ownedTableOwners[$table] ?? null) !== $moduleKey) {
                throw new RuntimeException("Migration table owner does not match its Module: {$table}");
            }
        }
    }
}

function freePort(): int
{
    $socket = stream_socket_server('tcp://127.0.0.1:0', $errorCode, $errorMessage);
    if ($socket === false) {
        throw new RuntimeException("Could not allocate Host port: {$errorMessage}");
    }
    $name = stream_socket_get_name($socket, false);
    fclose($socket);
    if (!is_string($name) || preg_match('/:(\d+)$/D', $name, $matches) !== 1) {
        throw new RuntimeException('Could not resolve allocated Host port.');
    }

    return (int) $matches[1];
}

/** @param resource $process */
function waitForHealth($process, int $port, string $log): void
{
    $context = stream_context_create(['http' => ['timeout' => 1, 'ignore_errors' => true]]);
    for ($attempt = 0; $attempt < 60; ++$attempt) {
        $status = proc_get_status($process);
        if (!($status['running'] ?? false)) {
            throw new RuntimeException('Generated Host stopped before health readiness: ' . (string) @file_get_contents($log));
        }
        $body = @file_get_contents("http://127.0.0.1:{$port}/health", false, $context);
        if (is_string($body)) {
            $payload = json_decode($body, true, 512, JSON_THROW_ON_ERROR);
            if (($payload['status'] ?? null) === 'ok') {
                return;
            }
        }
        usleep(250_000);
    }
    throw new RuntimeException('Generated Host health endpoint did not become ready: ' . (string) @file_get_contents($log));
}

$options = options(array_slice($argv, 1));
$repository = realpath($options['repository-root']);
$sourceCommit = $options['source-commit'];
if (!is_string($repository) || !file_exists($repository . '/.git')
    || preg_match('/^[0-9a-f]{40}$/D', $sourceCommit) !== 1) {
    throw new RuntimeException('Repository or source commit input is invalid.');
}
$resolved = trim(requireCommand(['git', 'rev-parse', $sourceCommit . '^{commit}'], $repository));
if (!hash_equals($sourceCommit, $resolved)) {
    throw new RuntimeException('Source commit did not resolve to its exact immutable identity.');
}
requireCommand(['git', 'merge-base', '--is-ancestor', $sourceCommit, 'HEAD'], $repository);

$work = sys_get_temp_dir() . '/peanut-admin-mt01-empty-install-' . bin2hex(random_bytes(8));
$source = $work . '/source';
$project = $work . '/project';
$archive = $work . '/source.tar';
$database = DATABASE_PREFIX . bin2hex(random_bytes(6));
$admin = null;
$hostProcess = null;
$hostLogHandle = null;
$hostLog = $work . '/host.log';

try {
    if (!mkdir($source, 0700, true)) {
        throw new RuntimeException('Could not create isolated source directory.');
    }
    requireCommand(
        ['git', 'archive', '--format=tar', '--output=' . $archive, $sourceCommit],
        $repository,
    );
    requireCommand(['tar', '-xf', $archive, '-C', $source], $repository);

    $explicitHost = $options['generated-host-root'] ?? null;
    if (is_string($explicitHost)) {
        $physicalHost = realpath($explicitHost);
        if (!is_string($physicalHost) || $physicalHost !== rtrim($explicitHost, '/')) {
            throw new RuntimeException('Explicit generated Host path is missing, relative, or non-canonical.');
        }
        copyGeneratedHost($physicalHost, $project);
    } else {
        requireCommand(
            ['/bin/bash', $source . '/scripts/create-project', ...generationArguments($project)],
            $source,
        );
    }
    assertProjectIdentity($project, $sourceCommit, !is_string($explicitHost));
    assertNonOverwriteBoundary($source, $project, !is_string($explicitHost));

    requireCommand([
        'composer', 'install', '--working-dir', $project . '/backend',
        '--no-interaction', '--prefer-dist', '--no-progress',
    ], $project);
    requireCommand(['pnpm', '--dir', $project, 'install', '--frozen-lockfile'], $project);

    require $project . '/backend/vendor/autoload.php';
    $mysqlPort = (int) getenv('MYSQL_PORT');
    $rootPassword = getenv('MYSQL_ROOT_PASSWORD') ?: 'peanut_admin_root_dev';
    $dsn = "mysql:host=127.0.0.1;port={$mysqlPort};charset=utf8mb4";
    $pdoOptions = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ];
    $admin = new PDO($dsn, 'root', $rootPassword, $pdoOptions);
    $admin->exec("CREATE DATABASE `{$database}` CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci");
    $pdo = new PDO($dsn . ";dbname={$database}", 'root', $rootPassword, $pdoOptions);
    if (!databaseIsEmpty($pdo)) {
        throw new RuntimeException('Fresh MT01 database is not empty.');
    }

    $kernelRoot = InstalledVersions::getInstallPath(KernelPackage::NAME);
    $dataPermissionRoot = InstalledVersions::getInstallPath(DataPermissionPackage::NAME);
    if (!is_string($kernelRoot) || !is_string($dataPermissionRoot)) {
        throw new RuntimeException('Generated Host package installation paths are unavailable.');
    }
    $kernelLedger = 'pa_kernel_migration';
    $dataPermissionLedger = 'pa_data_permission_migration';
    migrationManager($pdo, $database, $kernelRoot . '/kernel/database/migrations', 'kernel', $kernelLedger)
        ->migrate('kernel');
    migrationManager($pdo, $database, $dataPermissionRoot . '/data-permission/database/migrations', 'data_permission', $dataPermissionLedger)
        ->migrate('data_permission');

    $metadata = projectMetadata($project);
    $namespace = $metadata['project']['php_namespace'] ?? null;
    if (!is_string($namespace) || preg_match('/^[A-Z][A-Za-z0-9]*(?:\\\\[A-Z][A-Za-z0-9]*)+$/D', $namespace) !== 1) {
        throw new RuntimeException('Generated Host PHP namespace identity is invalid.');
    }
    $registryFactory = $namespace . '\\Module\\ModuleRegistryFactory';
    if (!class_exists($registryFactory)) {
        throw new RuntimeException('Generated Host Module registry factory is unavailable.');
    }
    $registry = (new $registryFactory($project))->compile();
    $moduleMigrations = [];
    foreach ($registry->modules as $module) {
        $moduleKey = $module->data['key'] ?? null;
        $relative = $module->data['backend']['migrations'] ?? null;
        if (!is_string($moduleKey) || ($relative !== null && !is_string($relative))) {
            throw new RuntimeException('Generated Module migration declaration is invalid.');
        }
        if ($relative === null) {
            continue;
        }
        $path = $module->root . '/' . $relative;
        assertOwnedMigrations($moduleKey, $path, $registry->ownedTableOwners);
        $ledger = 'mt01_' . str_replace(['.', '-'], '_', $moduleKey) . '_migration';
        $environment = str_replace('.', '_', $moduleKey);
        migrationManager($pdo, $database, $path, $environment, $ledger)->migrate($environment);
        $moduleMigrations[] = [
            'path' => $path,
            'environment' => $environment,
            'ledger' => $ledger,
        ];
    }

    $bootstrap = new BootstrapService(
        new PdoTransactionManager($pdo),
        new PdoIdentityRepository($pdo),
        new PdoTenantRepository($pdo),
        new PdoMembershipRepository($pdo),
        new PdoPlatformRepository($pdo),
        new PdoAuditRepository($pdo),
        new PasswordHasher(),
    );
    $password = bin2hex(random_bytes(24));
    $platform = $bootstrap->bootstrapPlatformOwner(
        'mt01-owner@example.test',
        $password,
        'MT01 Owner',
        'mt01-platform-bootstrap',
    );
    $candidate = $bootstrap->provisionTenantOwnerCandidate(
        $platform->operatorId,
        'mt01-tenant',
        'MT01 Tenant',
        'mt01-owner@example.test',
        null,
        'MT01 Tenant Owner',
        'mt01-tenant-bootstrap',
    );
    $bootstrap->activateTenantOwner(
        $platform->operatorId,
        $candidate->tenantId,
        $candidate->memberId,
        'mt01-owner-activate',
    );
    $bootstrap->activateTenant($platform->operatorId, $candidate->tenantId, 'mt01-tenant-activate');

    foreach ([$kernelLedger, $dataPermissionLedger] as $ledger) {
        $before = ledgerRowCount($pdo, $ledger);
        $path = $ledger === $kernelLedger
            ? $kernelRoot . '/kernel/database/migrations'
            : $dataPermissionRoot . '/data-permission/database/migrations';
        $environment = $ledger === $kernelLedger ? 'kernel' : 'data_permission';
        migrationManager($pdo, $database, $path, $environment, $ledger)->migrate($environment);
        if (ledgerRowCount($pdo, $ledger) !== $before) {
            throw new RuntimeException("Migration rerun was not idempotent: {$ledger}");
        }
    }
    foreach ($moduleMigrations as $migration) {
        $before = ledgerRowCount($pdo, $migration['ledger']);
        migrationManager(
            $pdo,
            $database,
            $migration['path'],
            $migration['environment'],
            $migration['ledger'],
        )->migrate($migration['environment']);
        if (ledgerRowCount($pdo, $migration['ledger']) !== $before) {
            throw new RuntimeException("Module migration rerun was not idempotent: {$migration['ledger']}");
        }
    }

    $port = freePort();
    $hostLogHandle = fopen($hostLog, 'wb');
    if (!is_resource($hostLogHandle)) {
        throw new RuntimeException('Could not open generated Host log.');
    }
    $hostProcess = proc_open([
        PHP_BINARY,
        '-S',
        "127.0.0.1:{$port}",
        '-t',
        $project . '/backend/public',
        $project . '/backend/public/router.php',
    ], [
        0 => ['pipe', 'r'],
        1 => $hostLogHandle,
        2 => $hostLogHandle,
    ], $pipes, $project . '/backend', [
        ...getenv(),
        'DB_DATABASE' => $database,
        'DB_USERNAME' => 'root',
        'DB_PASSWORD' => $rootPassword,
        'DB_PORT' => (string) getenv('DB_PORT'),
    ]);
    if (!is_resource($hostProcess)) {
        throw new RuntimeException('Could not start generated Host.');
    }
    fclose($pipes[0]);
    waitForHealth($hostProcess, $port, $hostLog);

    fwrite(STDOUT, "MT01 generated Host empty install, migration, start, and no-update boundary: OK\n");
} finally {
    if (is_resource($hostProcess)) {
        proc_terminate($hostProcess);
        proc_close($hostProcess);
    }
    if (is_resource($hostLogHandle)) {
        fclose($hostLogHandle);
    }
    if ($admin instanceof PDO && preg_match('/^' . DATABASE_PREFIX . '[0-9a-f]{12}$/D', $database) === 1) {
        $admin->exec("DROP DATABASE IF EXISTS `{$database}`");
    }
    removeFixture($work);
}
