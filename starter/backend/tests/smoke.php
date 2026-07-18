<?php

declare(strict_types=1);

use Composer\InstalledVersions;
use PeanutAdmin\DataPermission\Package as DataPermissionPackage;
use PeanutAdmin\InternalStarter\Module\ModuleRegistryFactory;
use PeanutAdmin\Kernel\Package as KernelPackage;

require dirname(__DIR__) . '/vendor/autoload.php';

$root = dirname(__DIR__, 2);
$registry = (new ModuleRegistryFactory($root))->compile();
$kernelRoot = InstalledVersions::getInstallPath(KernelPackage::NAME);
$dataPermissionRoot = InstalledVersions::getInstallPath(DataPermissionPackage::NAME);
$valid = KernelPackage::VERSION === '0.1.0'
    && DataPermissionPackage::VERSION === '0.1.0'
    && $registry->moduleKeys() === ['example.greeting']
    && $registry->ownedTableOwners === []
    && is_string($kernelRoot)
    && is_dir($kernelRoot . '/database/migrations')
    && is_file($kernelRoot . '/resources/schemas/module-manifest.schema.json')
    && is_string($dataPermissionRoot)
    && is_dir($dataPermissionRoot . '/database/migrations');

if (!$valid) {
    fwrite(STDERR, "ERROR: internal starter package smoke failed\n");
    exit(1);
}

fwrite(STDOUT, "Internal starter backend test: OK\n");
