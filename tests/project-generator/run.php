<?php

declare(strict_types=1);

use PeanutAdmin\ProjectGenerator\GenerationRequest;
use PeanutAdmin\ProjectGenerator\ProjectGenerator;

$root = dirname(__DIR__, 2);
$temporaryRoot = sys_get_temp_dir() . '/peanut-project-generator-' . bin2hex(random_bytes(8));

if (!mkdir($temporaryRoot, 0700, true) && !is_dir($temporaryRoot)) {
    throw new RuntimeException('Could not create fixture root.');
}

/** @param list<string> $arguments @return array{code: int, stdout: string, stderr: string} */
function runGenerator(string $root, array $arguments): array
{
    $command = array_merge([$root . '/scripts/create-project'], $arguments);
    $pipes = [];
    $process = proc_open($command, [
        0 => ['pipe', 'r'],
        1 => ['pipe', 'w'],
        2 => ['pipe', 'w'],
    ], $pipes, $root);
    if (!is_resource($process)) {
        throw new RuntimeException('Could not start generator.');
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

/** @return list<string> */
function validArguments(string $target): array
{
    return [
        '--target', $target,
        '--slug', 'north-star-admin',
        '--display-name', 'North Star Admin',
        '--php-namespace', 'NorthStar\\Admin',
        '--brand', 'North Star',
        '--profile', 'standard-admin',
        '--tenant-client', 'operations-web=/api/operations/v1/',
        '--tenant-client', 'reporting-web=/api/reporting/v1/',
        '--feature', 'file-media',
        '--feature', 'settings',
    ];
}

function assertTrue(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

/** @return array<string, string> */
function contentInventory(string $root): array
{
    $result = [];
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS),
    );
    foreach ($iterator as $file) {
        if (!$file->isFile() || $file->isLink()) {
            throw new RuntimeException('Generated output must contain regular files only.');
        }
        $relative = substr($file->getPathname(), strlen($root) + 1);
        $result[$relative] = hash_file('sha256', $file->getPathname()) ?: '';
    }
    ksort($result);

    return $result;
}

function removeFixture(string $path): void
{
    if (is_link($path) || is_file($path)) {
        unlink($path);

        return;
    }
    if (!is_dir($path)) {
        return;
    }
    $entries = scandir($path);
    if (!is_array($entries)) {
        throw new RuntimeException("Could not scan fixture: {$path}");
    }
    foreach ($entries as $entry) {
        if ($entry !== '.' && $entry !== '..') {
            removeFixture($path . '/' . $entry);
        }
    }
    rmdir($path);
}

try {
    $invalidArgs = validArguments($temporaryRoot . '/invalid-slug');
    $slugIndex = array_search('--slug', $invalidArgs, true);
    assertTrue(is_int($slugIndex), 'Slug argument fixture is invalid.');
    $invalidArgs[$slugIndex + 1] = 'Invalid_Slug';
    $invalid = runGenerator($root, $invalidArgs);
    assertTrue($invalid['code'] !== 0, 'Invalid slug must fail.');
    assertTrue(
        str_contains($invalid['stderr'], 'PROJECT_SLUG_INVALID'),
        'Invalid slug error is unstable: ' . $invalid['stderr'],
    );
    assertTrue(!file_exists($temporaryRoot . '/invalid-slug'), 'Invalid request created a target.');

    $traversal = $temporaryRoot . '/safe/../escaped';
    $result = runGenerator($root, validArguments($traversal));
    assertTrue($result['code'] !== 0, 'Traversal target must fail.');
    assertTrue(str_contains($result['stderr'], 'PROJECT_TARGET_UNSAFE'), 'Traversal error is unstable.');
    assertTrue(!file_exists($temporaryRoot . '/escaped'), 'Traversal target was created.');

    $realTarget = $temporaryRoot . '/real-target';
    mkdir($realTarget, 0700);
    $linkTarget = $temporaryRoot . '/linked-target';
    symlink($realTarget, $linkTarget);
    $result = runGenerator($root, validArguments($linkTarget));
    assertTrue($result['code'] !== 0, 'Symlink target must fail.');
    assertTrue(str_contains($result['stderr'], 'PROJECT_TARGET_UNSAFE'), 'Symlink error is unstable.');
    assertTrue(scandir($realTarget) === ['.', '..'], 'Symlink target content changed.');

    $nonEmpty = $temporaryRoot . '/non-empty';
    mkdir($nonEmpty, 0700);
    file_put_contents($nonEmpty . '/owned.txt', "keep\n");
    $result = runGenerator($root, validArguments($nonEmpty));
    assertTrue($result['code'] !== 0, 'Non-empty target must fail.');
    assertTrue(str_contains($result['stderr'], 'PROJECT_TARGET_NOT_EMPTY'), 'Non-empty error is unstable.');
    assertTrue(file_get_contents($nonEmpty . '/owned.txt') === "keep\n", 'Existing file changed.');

    $sourceTarget = $root . '/generated-inside-source';
    $result = runGenerator($root, validArguments($sourceTarget));
    assertTrue($result['code'] !== 0, 'Source-tree target must fail.');
    assertTrue(str_contains($result['stderr'], 'PROJECT_TARGET_UNSAFE'), 'Source-tree error is unstable.');
    assertTrue(!file_exists($sourceTarget), 'Source-tree target was created.');

    $unknownArgs = validArguments($temporaryRoot . '/unknown-feature');
    array_push($unknownArgs, '--feature', 'remote-marketplace');
    $result = runGenerator($root, $unknownArgs);
    assertTrue($result['code'] !== 0, 'Unknown feature must fail.');
    assertTrue(str_contains($result['stderr'], 'PROJECT_FEATURE_UNKNOWN'), 'Feature error is unstable.');
    assertTrue(!file_exists($temporaryRoot . '/unknown-feature'), 'Unknown feature created a target.');

    require $root . '/tools/project-generator/src/ProjectGenerator.php';
    $brokenSource = $temporaryRoot . '/broken-source';
    mkdir($brokenSource . '/starter', 0700, true);
    mkdir($brokenSource . '/packages/php', 0700, true);
    mkdir($brokenSource . '/packages/web', 0700, true);
    $emptyTarget = $temporaryRoot . '/cleanup-target';
    mkdir($emptyTarget, 0700);
    $request = new GenerationRequest(
        $emptyTarget,
        'cleanup-admin',
        'Cleanup Admin',
        'Cleanup\\Admin',
        'Cleanup',
        'standard-admin',
        [['key' => 'operations-web', 'api_prefix' => '/api/operations/v1/']],
        ['settings'],
    );
    try {
        (new ProjectGenerator($brokenSource))->generate($request);
        throw new RuntimeException('Broken source must fail generation.');
    } catch (Throwable $exception) {
        assertTrue(str_contains($exception->getMessage(), 'PROJECT_TEMPLATE_INVALID'), 'Template error is unstable.');
    }
    assertTrue(is_dir($emptyTarget), 'Pre-existing empty target was removed on failure.');
    assertTrue(scandir($emptyTarget) === ['.', '..'], 'Failed generation left partial output.');

    $first = $temporaryRoot . '/first';
    $second = $temporaryRoot . '/second';
    $firstResult = runGenerator($root, validArguments($first));
    $secondResult = runGenerator($root, validArguments($second));
    assertTrue($firstResult['code'] === 0, 'First generation failed: ' . $firstResult['stderr']);
    assertTrue($secondResult['code'] === 0, 'Second generation failed: ' . $secondResult['stderr']);
    assertTrue(contentInventory($first) === contentInventory($second), 'Same inputs are not byte deterministic.');

    $metadata = json_decode((string) file_get_contents($first . '/peanut-project.json'), true, 512, JSON_THROW_ON_ERROR);
    assertTrue(($metadata['schema_version'] ?? null) === 1, 'Generator schema is missing.');
    assertTrue(($metadata['peanut_admin']['input_commit'] ?? null) === '1865be64048cb0f79ce374e76e8407faca7d21d1', 'Input commit drifted.');
    assertTrue(($metadata['peanut_admin']['input_tree'] ?? null) === 'd7b8c4550604a16f344432a0c0b3c8accb3f451f', 'Input tree drifted.');
    assertTrue(($metadata['project']['features'] ?? null) === ['settings', 'file-media'], 'Features are not canonical.');
    assertTrue(($metadata['secrets']['embedded'] ?? null) === false, 'Metadata claims embedded secrets.');
    assertTrue(!file_exists($first . '/.peanut-project-generation'), 'Ownership marker leaked into output.');

    $all = implode("\n", array_map(
        static fn(string $path): string => (string) file_get_contents($first . '/' . $path),
        array_keys(contentInventory($first)),
    ));
    assertTrue(!str_contains($all, $root), 'Generated output contains the source absolute path.');
    assertTrue(str_contains((string) file_get_contents($first . '/frontend/src/App.vue'), 'North Star'), 'Brand was not rendered.');
    assertTrue(str_contains((string) file_get_contents($first . '/backend/config/auth.php'), 'operations-web'), 'Tenant Clients were not rendered.');
    assertTrue(str_contains((string) file_get_contents($first . '/backend/config/modules.php'), 'peanut.settings'), 'Selected Settings Module is absent.');
    assertTrue(!str_contains((string) file_get_contents($first . '/backend/config/modules.php'), 'peanut.reference-codes'), 'Unselected Module was enabled.');

    $environment = (string) file_get_contents($first . '/.env.example');
    foreach (['PASSWORD', 'SECRET', 'TOKEN', 'KEY'] as $needle) {
        foreach (preg_split('/\R/', $environment) ?: [] as $line) {
            if (str_contains($line, $needle) && !str_ends_with($line, '=')) {
                throw new RuntimeException("Secret template contains a value: {$line}");
            }
        }
    }

    fwrite(STDOUT, "Project generator fixture: OK\n");
} finally {
    removeFixture($temporaryRoot);
}
