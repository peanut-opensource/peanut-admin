<?php

declare(strict_types=1);

$root = dirname(__DIR__, 2);
$trustedRoots = [
    'backend/app/Modules/Peanut/Settings',
    'backend/app/Modules/Example/Target',
    'backend/app/Modules/Example/Reference',
    'backend/app/Modules/Example/WorkItem',
];

return [
    'kernel_version' => '1.0.0',
    'roots' => array_values(array_filter(
        $trustedRoots,
        static fn(string $path): bool => is_dir($root . '/' . $path),
    )),
    'frontend_components' => [
        'peanut.settings.page',
        'example.target.list',
        'example.reference.list',
        'example.work-item.list',
        'example.work-item.policy',
    ],
];
