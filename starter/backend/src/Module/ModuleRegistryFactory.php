<?php

declare(strict_types=1);

namespace PeanutAdmin\InternalStarter\Module;

use Composer\InstalledVersions;
use PeanutAdmin\DataPermission\Persistence\Schema\DataPermissionSchema;
use PeanutAdmin\Kernel\Authorization\Persistence\Schema\AuthorizationSchema;
use PeanutAdmin\Kernel\Idempotency\IdempotencySchema;
use PeanutAdmin\Kernel\Migration\ModuleSchema;
use PeanutAdmin\Kernel\Module\CompiledModuleRegistry;
use PeanutAdmin\Kernel\Module\ManifestLoader;
use PeanutAdmin\Kernel\Module\ModuleBoundaryChecker;
use PeanutAdmin\Kernel\Module\ModuleHostLayout;
use PeanutAdmin\Kernel\Module\ModuleRegistryCompiler;
use PeanutAdmin\Kernel\Persistence\Schema\KernelSchema;
use PeanutAdmin\Kernel\Package as KernelPackage;
use RuntimeException;

final readonly class ModuleRegistryFactory
{
    public function __construct(private string $root) {}

    public function compile(): CompiledModuleRegistry
    {
        $layout = new ModuleHostLayout(
            'backend/src/Modules',
            'ExampleHost\\App\\Modules',
            'frontend/src/modules',
        );
        $moduleRoot = $this->root . '/backend/src/Modules/Example/Greeting';
        $kernelRoot = InstalledVersions::getInstallPath(KernelPackage::NAME);
        if (!is_string($kernelRoot) || $kernelRoot === '') {
            throw new RuntimeException('Installed Kernel package path is unavailable.');
        }
        $document = (new ManifestLoader())->load($moduleRoot);
        $registry = (new ModuleRegistryCompiler(
            new OpisManifestSchemaValidator($kernelRoot . '/resources/schemas/module-manifest.schema.json'),
            new ComposerVersionConstraintMatcher(),
            new ReflectionContractInspector(),
            KernelPackage::VERSION,
            [],
            $layout,
            [
                ...KernelSchema::tableNames(),
                ...AuthorizationSchema::tableNames(),
                ...ModuleSchema::tableNames(),
                ...IdempotencySchema::tableNames(),
                ...DataPermissionSchema::tableNames(),
            ],
            ['operations-web', 'reporting-web', 'platform-web'],
        ))->compile([$document]);
        (new ModuleBoundaryChecker($registry, $layout, ['pa_', 'starter_']))->check();

        return $registry;
    }
}
