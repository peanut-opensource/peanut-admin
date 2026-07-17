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
    'backend/public/index.php',
    'backend/src/ExampleGreetingModuleProvider.php',
    'backend/src/StarterExceptionHandler.php',
    'backend/tests/smoke.php',
    'frontend/package.json',
    'frontend/src/App.vue',
    'frontend/src/modules/example-greeting/index.ts',
    'package.json',
    'packages/php/data-permission/composer.json',
    'packages/php/kernel/composer.json',
    'packages/web/admin-core/package.json',
    'packages/web/admin-shell/package.json',
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
    'peanut-admin/kernel' => '0.1.0',
    'peanut-admin/data-permission' => '0.1.0',
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
] as $package => $version) {
    if (($frontend['dependencies'][$package] ?? null) !== $version) {
        fwrite(STDERR, "ERROR: starter must lock {$package} to {$version}\n");
        exit(1);
    }
}

$hostFiles = array_merge(
    glob($root . '/backend/{public,src,tests}/*', GLOB_BRACE) ?: [],
    glob($root . '/frontend/src/**/*', GLOB_BRACE) ?: [],
);
foreach ($hostFiles as $path) {
    if (!is_file($path)) {
        continue;
    }
    $contents = (string) file_get_contents($path);
    if (preg_match('~@peanut-admin/[^\'\"]+/src/|packages/(?:php|web)/|PeanutAdmin\\\\App\\\\~', $contents) === 1) {
        fwrite(STDERR, "ERROR: starter host deep-imports package internals: {$path}\n");
        exit(1);
    }
}

fwrite(STDOUT, "Generated starter contract: OK\n");
