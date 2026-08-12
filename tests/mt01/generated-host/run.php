<?php

declare(strict_types=1);

$root = dirname(__DIR__, 3);
$command = $argv[1] ?? 'run';
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
if ($command === 'cleanup') {
    $admin->exec("DROP DATABASE IF EXISTS `{$database}`");
    exit(0);
}
if ($command !== 'run') {
    throw new RuntimeException('Expected run or cleanup.');
}

$temporaryRoot = getenv('MT01_TEMP_ROOT');
if (!is_string($temporaryRoot) || !is_dir($temporaryRoot)) {
    throw new RuntimeException('MT01_TEMP_ROOT must be an existing isolated directory.');
}
$generated = $temporaryRoot . '/generated-host';
$arguments = [
    $root . '/scripts/create-project', '--target', $generated,
    '--slug', 'generated-host-admin', '--display-name', 'Generated Host Admin',
    '--php-namespace', 'GeneratedHost\\Admin', '--brand', 'Generated Host',
    '--profile', 'standard-admin', '--tenant-client', 'fixture-web=/api/fixture/v1/',
    '--admin-client', 'fixture-web', '--example-module', 'remove',
];
$process = proc_open($arguments, [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes, $root);
if (!is_resource($process)) {
    throw new RuntimeException('Could not start project Generator.');
}
fclose($pipes[0]);
$stdout = stream_get_contents($pipes[1]);
$stderr = stream_get_contents($pipes[2]);
fclose($pipes[1]);
fclose($pipes[2]);
if (proc_close($process) !== 0) {
    throw new RuntimeException("Generated Host project failed: {$stdout}{$stderr}");
}

$inventory = [];
$iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($generated, FilesystemIterator::SKIP_DOTS));
foreach ($iterator as $file) {
    if ($file->isFile()) {
        $inventory[] = substr($file->getPathname(), strlen($generated) + 1);
    }
}
$joined = implode("\n", $inventory);
if (str_contains($joined, 'example-greeting') || str_contains($joined, 'Example/Greeting')) {
    throw new RuntimeException('Removed fictional example remains in generated output.');
}

$fixtureSource = __DIR__ . '/fixture';
$moduleRoot = $generated . '/backend/src/Modules/Fixture/Record';
$migrationRoot = $moduleRoot . '/Database/Migrations';
if (!mkdir($migrationRoot, 0700, true) && !is_dir($migrationRoot)) {
    throw new RuntimeException('Could not mount fixture Module.');
}
copy($fixtureSource . '/module.json', $moduleRoot . '/module.json');
copy($fixtureSource . '/FixtureRecordHost.php', $moduleRoot . '/FixtureRecordHost.php');
copy($fixtureSource . '/CreateFixtureRecord.php', $migrationRoot . '/20260812000101_create_fixture_record.php');

$manifest = json_decode((string) file_get_contents($moduleRoot . '/module.json'), true, 512, JSON_THROW_ON_ERROR);
if (($manifest['key'] ?? null) !== 'fixture.record'
    || ($manifest['database']['owned_tables'] ?? null) !== ['fixture_scope', 'fixture_record', 'fixture_outbox']) {
    throw new RuntimeException('Fixture Module ownership manifest is invalid.');
}

$admin->exec("CREATE DATABASE `{$database}` CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci");
$pdo = new PDO(
    "mysql:host=127.0.0.1;port={$port};dbname={$database};charset=utf8mb4",
    getenv('DB_USERNAME') ?: 'root',
    $password,
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC],
);

// The integration owner continues from this fail-closed boundary after the
// generated project has installed its own dependencies and Kernel migrations.
if (!is_file($generated . '/backend/vendor/autoload.php')) {
    throw new RuntimeException('Generated Host dependency installation is required before the MySQL Gate.');
}

fwrite(STDOUT, json_encode([
    'status' => 'mounted',
    'database' => $database,
    'generated_root' => $generated,
    'module' => 'fixture.record',
], JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES) . "\n");
