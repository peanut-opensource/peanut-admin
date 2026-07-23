<?php

declare(strict_types=1);

$root = $argv[1] ?? null;
if (!is_string($root) || !is_dir($root)) {
    fwrite(STDERR, "ERROR: generated starter directory is required\n");
    exit(64);
}

$required = [
    '.env.example',
    'README.md',
    'backend/composer.json',
    'backend/composer.lock',
    'backend/app/provider.php',
    'backend/config/cache.php',
    'backend/config/auth.php',
    'backend/public/index.php',
    'backend/src/Module/ModuleRegistryFactory.php',
    'backend/src/Auth/TenantAuthRuntimeFactory.php',
    'backend/src/FileMedia/FileMediaStorageFactory.php',
    'backend/src/FileMedia/LocalPrivateStorageProvider.php',
    'backend/src/Modules/Example/Greeting/ExampleGreetingModuleProvider.php',
    'backend/src/Modules/Example/Greeting/module.json',
    'backend/src/StarterExceptionHandler.php',
    'backend/tests/auth-clients.php',
    'backend/tests/file-media.php',
    'backend/config/notification-sms.php',
    'backend/tests/smoke.php',
    'frontend/package.json',
    'frontend/src/App.vue',
    'frontend/src/clients.ts',
    'frontend/src/modules/example-greeting/index.ts',
    'package.json',
    'packages/php/data-permission/composer.json',
    'packages/php/data-permission/database/migrations/20260716020101_create_pa_data_permission_policy.php',
    'packages/php/kernel/composer.json',
    'packages/php/file-media/composer.json',
    'packages/php/task-job/composer.json',
    'packages/php/notification-sms/composer.json',
    'packages/php/kernel/database/migrations/20260718010101_generalize_pa_tenant_clients.php',
    'packages/php/kernel/resources/schemas/module-manifest.schema.json',
    'packages/web/admin-core/package.json',
    'packages/web/admin-shell/package.json',
    'packages/web/file-media/package.json',
    'packages/web/task-job/package.json',
    'packages/web/notification-sms/package.json',
    'backend/src/Modules/Peanut/TaskJob/module.json',
    'backend/src/Modules/Peanut/NotificationSms/module.json',
    'frontend/src/modules/peanut-task-job.ts',
    'frontend/src/modules/peanut-notification-sms.ts',
    'frontend/tests/task-job.spec.ts',
    'frontend/tests/notification-sms.spec.ts',
    'pnpm-workspace.yaml',
    'pnpm-lock.yaml',
];
foreach ($required as $path) {
    if (!is_file($root . '/' . $path)) {
        fwrite(STDERR, "ERROR: generated starter file is missing: {$path}\n");
        exit(1);
    }
}

$composer = json_decode(
    (string) file_get_contents($root . '/backend/composer.json'),
    true,
    512,
    JSON_THROW_ON_ERROR,
);
foreach ([
    'composer/semver' => '3.4.4',
    'opis/json-schema' => '2.6.0',
    'peanut-admin/kernel' => '0.1.0',
    'peanut-admin/file-media' => '0.1.0',
    'peanut-admin/task-job' => '0.1.0',
    'peanut-admin/notification-sms' => '0.1.0',
    'peanut-admin/data-permission' => '0.1.0',
    'peanut-admin/reference-codes' => '0.1.0',
    'peanut-admin/settings' => '0.1.0',
] as $package => $version) {
    if (($composer['require'][$package] ?? null) !== $version) {
        fwrite(STDERR, "ERROR: starter must lock {$package} to {$version}\n");
        exit(1);
    }
}

$frontend = json_decode(
    (string) file_get_contents($root . '/frontend/package.json'),
    true,
    512,
    JSON_THROW_ON_ERROR,
);
foreach ([
    '@peanut-admin/admin-core' => 'workspace:0.1.0',
    '@peanut-admin/admin-shell' => 'workspace:0.1.0',
    '@peanut-admin/file-media' => 'workspace:0.1.0',
    '@peanut-admin/task-job' => 'workspace:0.1.0',
    '@peanut-admin/notification-sms' => 'workspace:0.1.0',
    '@peanut-admin/reference-codes' => 'workspace:0.1.0',
    '@peanut-admin/settings' => 'workspace:0.1.0',
] as $package => $version) {
    if (($frontend['dependencies'][$package] ?? null) !== $version) {
        fwrite(STDERR, "ERROR: starter must lock {$package} to {$version}\n");
        exit(1);
    }
}

$hostRoots = [$root . '/backend/public', $root . '/backend/src', $root . '/backend/tests', $root . '/frontend/src'];
foreach ($hostRoots as $hostRoot) {
    $files = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($hostRoot, RecursiveDirectoryIterator::SKIP_DOTS),
    );
    foreach ($files as $file) {
        if (!$file->isFile()) {
            continue;
        }
        $path = $file->getPathname();
        $contents = (string) file_get_contents($path);
        if (preg_match('~@peanut-admin/[^\'\"]+/src/|packages/(?:php|web)/|PeanutAdmin\\\\App\\\\~', $contents) === 1) {
            fwrite(STDERR, "ERROR: starter host deep-imports package internals: {$path}\n");
            exit(1);
        }
    }
}

$manifest = json_decode(
    (string) file_get_contents($root . '/backend/src/Modules/Example/Greeting/module.json'),
    true,
    512,
    JSON_THROW_ON_ERROR,
);
if (($manifest['backend']['provider'] ?? null)
    !== 'ExampleHost\\App\\Modules\\Example\\Greeting\\ExampleGreetingModuleProvider') {
    fwrite(STDERR, "ERROR: starter manifest does not use its external host namespace\n");
    exit(1);
}

$fileMediaManifest = json_decode(
    (string) file_get_contents($root . '/backend/src/Modules/Peanut/FileMedia/module.json'),
    true,
    512,
    JSON_THROW_ON_ERROR,
);
if (($fileMediaManifest['backend']['provider'] ?? null)
    !== 'ExampleHost\\App\\Modules\\Peanut\\FileMedia\\ModuleProvider') {
    fwrite(STDERR, "ERROR: starter File/Media manifest does not use its external host namespace\n");
    exit(1);
}
if (($fileMediaManifest['kernel_constraint'] ?? null) !== '^0.1') {
    fwrite(STDERR, "ERROR: starter File/Media manifest is incompatible with the fixed Kernel package\n");
    exit(1);
}

fwrite(STDOUT, "Generated starter contract: OK\n");
