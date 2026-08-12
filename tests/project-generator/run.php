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

/** @param list<string> $command @return array{code: int, stdout: string, stderr: string} */
function runFixtureCommand(array $command, string $workingDirectory): array
{
    $pipes = [];
    $process = proc_open($command, [
        0 => ['pipe', 'r'],
        1 => ['pipe', 'w'],
        2 => ['pipe', 'w'],
    ], $pipes, $workingDirectory);
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

function gitValue(string $root, string $revision): string
{
    $pipes = [];
    $process = proc_open(['git', '-C', $root, 'rev-parse', $revision], [
        0 => ['pipe', 'r'],
        1 => ['pipe', 'w'],
        2 => ['pipe', 'w'],
    ], $pipes, $root);
    if (!is_resource($process)) {
        throw new RuntimeException('Could not inspect generator source identity.');
    }
    fclose($pipes[0]);
    $stdout = stream_get_contents($pipes[1]);
    $stderr = stream_get_contents($pipes[2]);
    fclose($pipes[1]);
    fclose($pipes[2]);
    if (proc_close($process) !== 0 || !is_string($stdout)) {
        throw new RuntimeException('Could not inspect generator source identity: ' . $stderr);
    }

    return trim($stdout);
}

/** @return array{file_count: int, digest: string} */
function generatorManifest(string $root, string $revision): array
{
    $paths = [
        'scripts/create-project',
        'tools/project-generator/create-project.php',
        'tools/project-generator/src',
        'starter',
        'packages/php/composer.json',
        'packages/php/LICENSE',
        'packages/web/package.json',
        'packages/web/LICENSE',
    ];
    foreach ([
        'packages/php' => [
            'kernel', 'data-permission', 'testing', 'settings', 'reference-codes', 'file-media',
            'task-job', 'notification-sms', 'import-export', 'ops-console', 'integration-security',
        ],
        'packages/web' => [
            'admin-core', 'admin-shell', 'testing', 'settings', 'reference-codes', 'file-media',
            'task-job', 'notification-sms', 'import-export', 'ops-console', 'integration-security',
        ],
    ] as $package => $modules) {
        foreach ($modules as $module) {
            $paths[] = "{$package}/{$module}/src";
            if ($package === 'packages/php' && $module === 'kernel') {
                $paths[] = "{$package}/{$module}/database";
                $paths[] = "{$package}/{$module}/resources";
            } elseif ($package === 'packages/php' && $module === 'data-permission') {
                $paths[] = "{$package}/{$module}/database";
            }
        }
    }
    $command = ['git', '-C', $root, 'ls-tree', '-r', '-z', '--full-tree', $revision, '--', ...$paths];
    $pipes = [];
    $process = proc_open($command, [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes, $root);
    if (!is_resource($process)) {
        throw new RuntimeException('Could not compute the independent Generator manifest.');
    }
    fclose($pipes[0]);
    $stdout = stream_get_contents($pipes[1]);
    $stderr = stream_get_contents($pipes[2]);
    fclose($pipes[1]);
    fclose($pipes[2]);
    if (proc_close($process) !== 0 || !is_string($stdout)) {
        throw new RuntimeException('Could not compute the independent Generator manifest: ' . $stderr);
    }
    $entries = [];
    foreach (explode("\0", $stdout) as $record) {
        if ($record === '') {
            continue;
        }
        if (preg_match('/^(100644|100755) blob ([0-9a-f]{40})\t(.+)$/D', $record, $matches) !== 1) {
            throw new RuntimeException('Independent Generator manifest contains an unsupported Git entry.');
        }
        $entries[$matches[3]] = $matches[1] . ' ' . $matches[2];
    }
    ksort($entries, SORT_STRING);
    $manifest = '';
    foreach ($entries as $path => $identity) {
        $manifest .= $identity . "\t" . $path . "\n";
    }

    return ['file_count' => count($entries), 'digest' => hash('sha256', $manifest)];
}

/** @return list<string> */
function validArguments(string $target): array
{
    return [
        '--target', $target,
        '--slug', 'north-star-admin',
        '--display-name', 'North {{ 7 * 7 }} <script>alert(1)</script>',
        '--php-namespace', 'NorthStar\\Admin',
        '--brand', 'Brand {{ ADMIN_CORE_PACKAGE }} <img src=x onerror=alert(1)>',
        '--profile', 'standard-admin',
        '--tenant-client', 'field-console=/api/field/v1/',
        '--tenant-client', 'audit-console=/api/audit/v1/',
        '--admin-client', 'field-console',
        '--feature', 'file-media',
        '--feature', 'settings',
    ];
}

/** @return list<string> */
function withoutExampleArguments(string $target): array
{
    return [...validArguments($target), '--example-module', 'remove'];
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

    $invalidExampleArgs = validArguments($temporaryRoot . '/invalid-example-mode');
    array_push($invalidExampleArgs, '--example-module', 'omit');
    $result = runGenerator($root, $invalidExampleArgs);
    assertTrue($result['code'] !== 0, 'Unknown example Module mode must fail.');
    assertTrue(
        str_contains($result['stderr'], 'PROJECT_EXAMPLE_MODULE_INVALID'),
        'Example Module mode error is unstable.',
    );
    assertTrue(!file_exists($temporaryRoot . '/invalid-example-mode'), 'Invalid example mode created a target.');

    $reservedNamespaceArgs = validArguments($temporaryRoot . '/reserved-namespace');
    $namespaceIndex = array_search('--php-namespace', $reservedNamespaceArgs, true);
    assertTrue(is_int($namespaceIndex), 'Namespace argument fixture is invalid.');
    $reservedNamespaceArgs[$namespaceIndex + 1] = 'PeanutAdmin\\Product';
    $result = runGenerator($root, $reservedNamespaceArgs);
    assertTrue($result['code'] !== 0, 'Reserved package namespace must fail.');
    assertTrue(str_contains($result['stderr'], 'PROJECT_NAMESPACE_INVALID'), 'Namespace error is unstable.');
    assertTrue(!file_exists($temporaryRoot . '/reserved-namespace'), 'Reserved namespace created a target.');

    foreach ([
        'invalid-namespace' => ['--php-namespace', 'single'],
        'invalid-client-key' => ['--tenant-client', 'Invalid=/api/field/v1/'],
        'invalid-client-prefix' => ['--tenant-client', 'field-console=api/field/v1/'],
        'invalid-admin-client' => ['--admin-client', 'missing-console'],
    ] as $fixture => [$option, $value]) {
        $arguments = validArguments($temporaryRoot . '/' . $fixture);
        $optionIndex = array_search($option, $arguments, true);
        assertTrue(is_int($optionIndex), "{$fixture} argument fixture is invalid.");
        $arguments[$optionIndex + 1] = $value;
        $result = runGenerator($root, $arguments);
        assertTrue($result['code'] !== 0, "{$fixture} must fail.");
        assertTrue(!file_exists($temporaryRoot . '/' . $fixture), "{$fixture} created a target.");
    }

    $dependencyArgs = validArguments($temporaryRoot . '/missing-feature-dependency');
    array_push($dependencyArgs, '--feature', 'notification-sms');
    $result = runGenerator($root, $dependencyArgs);
    assertTrue($result['code'] !== 0, 'Notification/SMS without Task/Job must fail.');
    assertTrue(
        str_contains($result['stderr'], 'PROJECT_FEATURE_DEPENDENCY_MISSING'),
        'Feature dependency error is unstable.',
    );
    assertTrue(!file_exists($temporaryRoot . '/missing-feature-dependency'), 'Missing feature dependency created a target.');

    $importDependencyArgs = validArguments($temporaryRoot . '/missing-import-export-dependency');
    array_push($importDependencyArgs, '--feature', 'import-export');
    $result = runGenerator($root, $importDependencyArgs);
    assertTrue($result['code'] !== 0, 'Import/Export without Task/Job must fail.');
    assertTrue(
        str_contains($result['stderr'], 'PROJECT_FEATURE_DEPENDENCY_MISSING'),
        'Import/Export dependency error is unstable.',
    );
    assertTrue(!file_exists($temporaryRoot . '/missing-import-export-dependency'), 'Missing Import/Export dependency created a target.');

    $missingAdminArgs = validArguments($temporaryRoot . '/missing-admin');
    $adminIndex = array_search('--admin-client', $missingAdminArgs, true);
    assertTrue(is_int($adminIndex), 'Admin Client argument fixture is invalid.');
    array_splice($missingAdminArgs, $adminIndex, 2);
    $result = runGenerator($root, $missingAdminArgs);
    assertTrue($result['code'] !== 0, 'Multiple Tenant Clients without an explicit admin Client must fail.');
    assertTrue(str_contains($result['stderr'], 'PROJECT_ADMIN_CLIENT_MISSING'), 'Admin Client error is unstable.');
    assertTrue(!file_exists($temporaryRoot . '/missing-admin'), 'Missing admin Client created a target.');

    $dirtySentinel = $root . '/.project-generator-dirty-fixture';
    file_put_contents($dirtySentinel, "fixture\n");
    try {
        $dirtyTarget = $temporaryRoot . '/dirty-source-target';
        $result = runGenerator($root, validArguments($dirtyTarget));
        assertTrue($result['code'] !== 0, 'Dirty Git source must fail.');
        assertTrue(str_contains($result['stderr'], 'PROJECT_SOURCE_DIRTY'), 'Dirty source error is unstable.');
        assertTrue(!file_exists($dirtyTarget), 'Dirty source claimed the target.');
    } finally {
        unlink($dirtySentinel);
    }

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
        'operations-web',
        ['settings'],
    );
    try {
        (new ProjectGenerator($brokenSource))->generate($request);
        throw new RuntimeException('Broken source must fail generation.');
    } catch (Throwable $exception) {
        assertTrue(str_contains($exception->getMessage(), 'PROJECT_SOURCE_INVALID'), 'Template source error is unstable.');
    }
    assertTrue(is_dir($emptyTarget), 'Pre-existing empty target was removed on failure.');
    assertTrue(scandir($emptyTarget) === ['.', '..'], 'Failed generation left partial output.');

    $partialExampleTarget = $temporaryRoot . '/partial-example-output';
    mkdir($partialExampleTarget, 0700);
    file_put_contents($partialExampleTarget . '/README.md', "Greeting fixture remained.\n");
    $removeGuard = new ReflectionMethod(ProjectGenerator::class, 'assertExampleRemoved');
    try {
        $removeGuard->invoke(new ProjectGenerator($root), $partialExampleTarget);
        throw new RuntimeException('Partial example removal must fail.');
    } catch (Throwable $exception) {
        assertTrue(
            str_contains($exception->getMessage(), 'PROJECT_TEMPLATE_INVALID'),
            'Partial example removal error is unstable.',
        );
    }

    $packageSource = $temporaryRoot . '/package-source';
    $archive = $temporaryRoot . '/candidate.tar';
    $archiveResult = runFixtureCommand(
        ['git', 'archive', '--format=tar', '--output=' . $archive, 'HEAD'],
        $root,
    );
    assertTrue($archiveResult['code'] === 0, 'Could not create candidate archive: ' . $archiveResult['stderr']);
    mkdir($packageSource, 0700);
    $extractResult = runFixtureCommand(['tar', '-xf', $archive, '-C', $packageSource], $root);
    assertTrue($extractResult['code'] === 0, 'Could not extract candidate archive: ' . $extractResult['stderr']);
    assertTrue(!file_exists($packageSource . '/.git'), 'Candidate archive contains Git state.');
    $packageIdentity = json_decode(
        (string) file_get_contents($packageSource . '/tools/project-generator/package-identity.json'),
        true,
        512,
        JSON_THROW_ON_ERROR,
    );
    assertTrue(($packageIdentity['commit'] ?? null) === gitValue($root, 'HEAD^{commit}'), 'Archive commit identity was not expanded.');
    assertTrue(($packageIdentity['tree'] ?? null) === gitValue($root, 'HEAD^{tree}'), 'Archive tree identity was not expanded.');

    $invalidArchiveSource = $temporaryRoot . '/invalid-archive-source';
    mkdir($invalidArchiveSource, 0700);
    $invalidArchiveExtract = runFixtureCommand(['tar', '-xf', $archive, '-C', $invalidArchiveSource], $root);
    assertTrue($invalidArchiveExtract['code'] === 0, 'Could not extract invalid archive fixture.');
    $invalidArchiveIdentityPath = $invalidArchiveSource . '/tools/project-generator/package-identity.json';
    $invalidArchiveIdentity = json_decode((string) file_get_contents($invalidArchiveIdentityPath), true, 512, JSON_THROW_ON_ERROR);
    $invalidArchiveIdentity['tree'] = str_repeat('0', 40);
    file_put_contents(
        $invalidArchiveIdentityPath,
        json_encode($invalidArchiveIdentity, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n",
    );
    $invalidArchiveTarget = $temporaryRoot . '/invalid-archive-output';
    $invalidArchiveResult = runGenerator($invalidArchiveSource, validArguments($invalidArchiveTarget));
    assertTrue($invalidArchiveResult['code'] !== 0, 'Edited archive identity must fail.');
    assertTrue(!file_exists($invalidArchiveTarget), 'Edited archive identity claimed a target.');

    $invalidDigestSource = $temporaryRoot . '/invalid-digest-source';
    mkdir($invalidDigestSource, 0700);
    $invalidDigestExtract = runFixtureCommand(['tar', '-xf', $archive, '-C', $invalidDigestSource], $root);
    assertTrue($invalidDigestExtract['code'] === 0, 'Could not extract invalid digest fixture.');
    $invalidDigestBaselinePath = $invalidDigestSource . '/tools/project-generator/source-baseline.json';
    $invalidDigestBaseline = json_decode((string) file_get_contents($invalidDigestBaselinePath), true, 512, JSON_THROW_ON_ERROR);
    $invalidDigestBaseline['controlled_content']['digest'] = str_repeat('0', 64);
    file_put_contents(
        $invalidDigestBaselinePath,
        json_encode($invalidDigestBaseline, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n",
    );
    $invalidDigestTarget = $temporaryRoot . '/invalid-digest-output';
    $invalidDigestResult = runGenerator($invalidDigestSource, validArguments($invalidDigestTarget));
    assertTrue($invalidDigestResult['code'] !== 0, 'Edited Generator digest must fail.');
    assertTrue(!file_exists($invalidDigestTarget), 'Edited Generator digest claimed a target.');

    $missingControlledSource = $temporaryRoot . '/missing-controlled-source';
    mkdir($missingControlledSource, 0700);
    $missingControlledExtract = runFixtureCommand(['tar', '-xf', $archive, '-C', $missingControlledSource], $root);
    assertTrue($missingControlledExtract['code'] === 0, 'Could not extract missing controlled entry fixture.');
    unlink($missingControlledSource . '/packages/php/composer.json');
    $missingControlledTarget = $temporaryRoot . '/missing-controlled-output';
    $missingControlledResult = runGenerator($missingControlledSource, validArguments($missingControlledTarget));
    assertTrue($missingControlledResult['code'] !== 0, 'Missing controlled source entry must fail.');
    assertTrue(!file_exists($missingControlledTarget), 'Missing controlled source entry claimed a target.');
    $packageTarget = $temporaryRoot . '/package-output';
    $packageResult = runGenerator($packageSource, validArguments($packageTarget));
    assertTrue($packageResult['code'] === 0, 'Archived generator failed: ' . $packageResult['stderr']);
    assertTrue(is_file($packageTarget . '/peanut-project.json'), 'Validated candidate archive did not generate.');
    $packageMetadata = json_decode(
        (string) file_get_contents($packageTarget . '/peanut-project.json'),
        true,
        512,
        JSON_THROW_ON_ERROR,
    );
    assertTrue(
        ($packageMetadata['peanut_admin']['input_commit'] ?? null) === ($packageIdentity['commit'] ?? null),
        'No-Git package commit identity drifted.',
    );
    assertTrue(
        ($packageMetadata['peanut_admin']['input_tree'] ?? null) === ($packageIdentity['tree'] ?? null),
        'No-Git package tree identity drifted.',
    );

    file_put_contents($packageSource . '/starter/frontend/src/App.vue', "\nsource drift\n", FILE_APPEND);
    $driftTarget = $temporaryRoot . '/drift-output';
    $driftResult = runGenerator($packageSource, validArguments($driftTarget));
    assertTrue($driftResult['code'] !== 0, 'Drifted no-Git source must fail generation.');
    assertTrue(str_contains($driftResult['stderr'], 'PROJECT_SOURCE_DRIFT'), 'Packaged source drift error is unstable.');
    assertTrue(!file_exists($driftTarget), 'Drifted no-Git source claimed the target.');

    $first = $temporaryRoot . '/first';
    $second = $temporaryRoot . '/second';
    $firstResult = runGenerator($root, validArguments($first));
    $secondResult = runGenerator($root, validArguments($second));
    assertTrue($firstResult['code'] === 0, 'First generation failed: ' . $firstResult['stderr']);
    assertTrue($secondResult['code'] === 0, 'Second generation failed: ' . $secondResult['stderr']);
    assertTrue(contentInventory($first) === contentInventory($second), 'Same inputs are not byte deterministic.');
    $metadata = json_decode((string) file_get_contents($first . '/peanut-project.json'), true, 512, JSON_THROW_ON_ERROR);
    assertTrue(is_file($first . '/backend/src/Modules/Example/Greeting/module.json'), 'Default example manifest is absent.');
    assertTrue(is_file($first . '/frontend/src/modules/example-greeting/index.ts'), 'Default example frontend Module is absent.');

    $withoutExampleFirst = $temporaryRoot . '/without-example-first';
    $withoutExampleSecond = $temporaryRoot . '/without-example-second';
    $withoutExampleFirstResult = runGenerator($root, withoutExampleArguments($withoutExampleFirst));
    $withoutExampleSecondResult = runGenerator($root, withoutExampleArguments($withoutExampleSecond));
    assertTrue($withoutExampleFirstResult['code'] === 0, 'Example-free generation failed.');
    assertTrue($withoutExampleSecondResult['code'] === 0, 'Second example-free generation failed.');
    assertTrue(
        contentInventory($withoutExampleFirst) === contentInventory($withoutExampleSecond),
        'Example-free generation is not byte deterministic.',
    );
    $withoutExampleInventory = contentInventory($withoutExampleFirst);
    foreach ($withoutExampleInventory as $relative => $digest) {
        assertTrue(!str_contains(strtolower($relative), 'example-greeting'), 'Example frontend path was retained.');
        assertTrue(!str_contains($relative, 'Modules/Example/Greeting'), 'Example backend path was retained.');
        assertTrue($digest !== '', 'Example-free output contains an unreadable file.');
        $contents = (string) file_get_contents($withoutExampleFirst . '/' . $relative);
        assertTrue(!str_contains($contents, 'example.greeting'), 'Example Module key was retained.');
        assertTrue(!str_contains($contents, 'ExampleGreeting'), 'Example provider or import was retained.');
        assertTrue(!str_contains($contents, 'api/example/greeting'), 'Example route was retained.');
    }
    $withoutExampleMetadata = json_decode(
        (string) file_get_contents($withoutExampleFirst . '/peanut-project.json'),
        true,
        512,
        JSON_THROW_ON_ERROR,
    );
    assertTrue(
        ($withoutExampleMetadata['project']['example_module'] ?? null) === 'removed',
        'Example-free metadata did not record removal.',
    );
    foreach (['input_commit', 'input_tree', 'generator_digest_algorithm', 'generator_digest'] as $identityKey) {
        assertTrue(
            ($withoutExampleMetadata['peanut_admin'][$identityKey] ?? null)
                === ($metadata['peanut_admin'][$identityKey] ?? null),
            "Example modes disagree on {$identityKey}.",
        );
    }
    $canonicalProject = [
        'slug' => 'north-star-admin',
        'display_name' => 'North {{ 7 * 7 }} <script>alert(1)</script>',
        'php_namespace' => 'NorthStar\\Admin',
        'brand' => 'Brand {{ ADMIN_CORE_PACKAGE }} <img src=x onerror=alert(1)>',
        'profile' => 'standard-admin',
        'tenant_clients' => [
            ['key' => 'field-console', 'api_prefix' => '/api/field/v1/'],
            ['key' => 'audit-console', 'api_prefix' => '/api/audit/v1/'],
        ],
        'admin_client_key' => 'field-console',
        'features' => ['settings', 'file-media'],
    ];
    assertTrue(
        array_intersect_key($withoutExampleMetadata['project'] ?? [], $canonicalProject) === $canonicalProject,
        'Example-free canonical parameters drifted.',
    );
    assertTrue(($withoutExampleMetadata['secrets']['embedded'] ?? null) === false, 'Example-free metadata claims embedded secrets.');

    $collisionTarget = $temporaryRoot . '/legacy-key-collision';
    $collisionArguments = validArguments($collisionTarget);
    foreach ($collisionArguments as $index => $argument) {
        $collisionArguments[$index] = match ($argument) {
            'field-console=/api/field/v1/' => 'reporting-web=/api/reporting/v1/',
            'audit-console=/api/audit/v1/' => 'operations-web=/api/operations/v1/',
            'field-console' => 'reporting-web',
            default => $argument,
        };
    }
    $collisionResult = runGenerator($root, $collisionArguments);
    assertTrue($collisionResult['code'] === 0, 'Legacy Client key collision generation failed.');
    $collisionFixture = (string) file_get_contents($collisionTarget . '/backend/tests/auth-clients.php');
    assertTrue(str_contains($collisionFixture, "create('reporting-web')"), 'Admin legacy Client key collided.');
    assertTrue(str_contains($collisionFixture, "create('operations-web')"), 'Secondary legacy Client key collided.');

    assertTrue(($metadata['schema_version'] ?? null) === 1, 'Generator schema is missing.');
    $headCommit = gitValue($root, 'HEAD^{commit}');
    $headTree = gitValue($root, 'HEAD^{tree}');
    $independentManifest = generatorManifest($root, 'HEAD^{commit}');
    assertTrue(preg_match('/^[0-9a-f]{40}$/D', $headCommit) === 1, 'Git HEAD commit identity is unavailable.');
    assertTrue(preg_match('/^[0-9a-f]{40}$/D', $headTree) === 1, 'Git HEAD tree identity is unavailable.');
    assertTrue(($metadata['peanut_admin']['input_commit'] ?? null) === $headCommit, 'Input commit drifted.');
    assertTrue(($metadata['peanut_admin']['input_tree'] ?? null) === $headTree, 'Input tree drifted.');
    assertTrue(
        ($metadata['peanut_admin']['generator_digest_algorithm'] ?? null) === 'sha256-git-blob-manifest-v1',
        'Generator digest algorithm drifted.',
    );
    assertTrue(
        ($metadata['peanut_admin']['generator_digest'] ?? null) === $independentManifest['digest'],
        'Generator digest does not match the independent Git blob manifest.',
    );
    $baseline = json_decode((string) file_get_contents($root . '/tools/project-generator/source-baseline.json'), true, 512, JSON_THROW_ON_ERROR);
    assertTrue(
        ($baseline['controlled_content']['file_count'] ?? null) === $independentManifest['file_count']
            && ($baseline['controlled_content']['digest'] ?? null) === $independentManifest['digest'],
        'Generator source baseline does not match the independent Git blob manifest.',
    );
    assertTrue(
        preg_match('/^[0-9a-f]{40}$/D', (string) ($baseline['archive_tree_without_baseline'] ?? '')) === 1,
        'Generator archive tree seal is missing.',
    );
    assertTrue(($metadata['project']['features'] ?? null) === ['settings', 'file-media'], 'Features are not canonical.');
    assertTrue(($metadata['project']['example_module'] ?? null) === 'retained', 'Default example mode changed.');
    assertTrue(($metadata['secrets']['embedded'] ?? null) === false, 'Metadata claims embedded secrets.');
    assertTrue(!file_exists($first . '/.peanut-project-generation'), 'Ownership marker leaked into output.');

    $all = implode("\n", array_map(
        static fn(string $path): string => (string) file_get_contents($first . '/' . $path),
        array_keys(contentInventory($first)),
    ));
    assertTrue(!str_contains($all, $root), 'Generated output contains the source absolute path.');
    $app = (string) file_get_contents($first . '/frontend/src/App.vue');
    assertTrue(str_contains($app, 'const projectBrand = "Brand {{ ADMIN_CORE_PACKAGE }} \\u003Cimg'), 'Brand constant was not encoded.');
    assertTrue(str_contains($app, 'v-text="projectBrand"'), 'Brand is not bound as text.');
    assertTrue(str_contains($app, 'v-text="projectDisplayName"'), 'Display name is not bound as text.');
    assertTrue(!str_contains($app, '<img src=x') && !str_contains($app, '<script>'), 'HTML-like project labels reached the Vue template.');
    $authConfig = require $first . '/backend/config/auth.php';
    assertTrue(($authConfig['admin_client_key'] ?? null) === 'field-console', 'Admin Client was not rendered.');
    assertTrue(($authConfig['tenant_clients'][0]['api_prefix'] ?? null) === '/api/field/v1/', 'Tenant Client structure was lost.');
    assertTrue(str_contains((string) file_get_contents($first . '/backend/config/modules.php'), 'peanut.settings'), 'Selected Settings Module is absent.');
    $moduleConfig = require $first . '/backend/config/modules.php';
    assertTrue(($moduleConfig['kernel_version'] ?? null) === '1.0.0', 'Kernel compatibility version was not rendered.');
    assertTrue(!str_contains((string) file_get_contents($first . '/backend/config/modules.php'), 'peanut.reference-codes'), 'Unselected Module was enabled.');
    assertTrue(str_contains((string) file_get_contents($first . '/backend/config/modules.php'), 'peanut.ops-console.page'), 'Always-on Ops Console was removed.');
    assertTrue(is_file($first . '/frontend/src/modules/peanut-ops-console.ts'), 'Always-on Ops Console Host is absent.');
    assertTrue(!is_dir($first . '/backend/src/Modules/Peanut/IntegrationSecurity'), 'Unselected Integration Security Module was retained.');
    assertTrue(!is_file($first . '/frontend/src/modules/peanut-integration-security.ts'), 'Unselected Integration Security Host was retained.');
    $fileMenus = json_decode(
        (string) file_get_contents($first . '/backend/src/Modules/Peanut/FileMedia/Resources/menus.json'),
        true,
        512,
        JSON_THROW_ON_ERROR,
    );
    assertTrue(($fileMenus[0]['client_keys'] ?? null) === ['field-console'], 'Selected Module menu did not use the admin Client.');
    $authFixture = (string) file_get_contents($first . '/backend/tests/auth-clients.php');
    assertTrue(str_contains($authFixture, "create('field-console')"), 'Primary auth fixture Client was not adapted.');
    assertTrue(str_contains($authFixture, "create('audit-console')"), 'Secondary auth fixture Client was not adapted.');
    assertTrue(!str_contains($authFixture, "create('operations-web')") && !str_contains($authFixture, "create('reporting-web')"), 'Legacy auth fixture Client leaked.');

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
