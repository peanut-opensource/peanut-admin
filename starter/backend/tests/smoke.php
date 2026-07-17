<?php

declare(strict_types=1);

use PeanutAdmin\DataPermission\Package as DataPermissionPackage;
use PeanutAdmin\InternalStarter\ExampleGreetingModuleProvider;
use PeanutAdmin\Kernel\Package as KernelPackage;

require dirname(__DIR__) . '/vendor/autoload.php';

$module = new ExampleGreetingModuleProvider();
$valid = KernelPackage::VERSION === '0.1.0'
    && DataPermissionPackage::VERSION === '0.1.0'
    && $module->moduleKey() === 'example.greeting';

if (!$valid) {
    fwrite(STDERR, "ERROR: internal starter package smoke failed\n");
    exit(1);
}

fwrite(STDOUT, "Internal starter backend test: OK\n");
