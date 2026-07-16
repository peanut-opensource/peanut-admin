<?php

declare(strict_types=1);

namespace PeanutAdmin\App\module;

use PeanutAdmin\Kernel\Module\CompiledModuleRegistry;
use PeanutAdmin\Kernel\Module\ManifestLoader;
use PeanutAdmin\Kernel\Module\ModuleRegistryCompiler;

final readonly class ModuleRegistryFactory
{
    /**
     * @param list<string> $moduleRoots
     * @param list<string> $frontendComponents
     */
    public function __construct(
        private array $moduleRoots,
        private array $frontendComponents,
        private string $kernelVersion,
        private string $schemaPath,
    ) {}

    public function compile(): CompiledModuleRegistry
    {
        $loader = new ManifestLoader();

        return (new ModuleRegistryCompiler(
            new OpisManifestSchemaValidator($this->schemaPath),
            new ComposerVersionConstraintMatcher(),
            new ReflectionContractInspector(),
            $this->kernelVersion,
            $this->frontendComponents,
        ))->compile(array_map($loader->load(...), $this->moduleRoots));
    }

    public function compileAndCheckBoundaries(): CompiledModuleRegistry
    {
        $registry = $this->compile();
        (new ModuleBoundaryChecker($registry))->check();

        return $registry;
    }
}
