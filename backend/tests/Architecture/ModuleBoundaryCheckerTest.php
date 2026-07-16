<?php

declare(strict_types=1);

namespace PeanutAdmin\App\Tests\Architecture;

use PeanutAdmin\App\module\ModuleBoundaryChecker;
use PeanutAdmin\Kernel\Module\CompiledModuleRegistry;
use PeanutAdmin\Kernel\Module\ManifestDocument;
use PeanutAdmin\Kernel\Module\ModuleException;
use PHPUnit\Framework\TestCase;

final class ModuleBoundaryCheckerTest extends TestCase
{
    private string $root;

    protected function setUp(): void
    {
        $this->root = sys_get_temp_dir() . '/peanut-module-boundary-' . bin2hex(random_bytes(5));
        mkdir($this->root, 0777, true);
    }

    protected function tearDown(): void
    {
        foreach (glob($this->root . '/*') ?: [] as $file) {
            unlink($file);
        }
        rmdir($this->root);
    }

    public function testOtherModuleInternalsAndTablesFailTheBuild(): void
    {
        file_put_contents($this->root . '/Bad.php', <<<'PHP'
<?php
use PeanutAdmin\App\Modules\Example\Other\Infrastructure\OtherRepository;
$sql = 'SELECT * FROM pa_example_other';
PHP);
        $manifest = ManifestDocument::fromArray($this->root, ['key' => 'example.owner']);
        $registry = new CompiledModuleRegistry(
            [$manifest],
            [],
            ['pa_example_owner' => 'example.owner', 'pa_example_other' => 'example.other'],
            [],
            'revision',
        );

        $this->expectException(ModuleException::class);
        (new ModuleBoundaryChecker($registry))->check();
    }

    public function testPublicContractsAndOwnedTablesPass(): void
    {
        self::expectNotToPerformAssertions();
        file_put_contents($this->root . '/Good.php', <<<'PHP'
<?php
use PeanutAdmin\App\Modules\Example\Other\Contracts\OtherQuery;
$sql = 'SELECT * FROM pa_example_owner';
PHP);
        $manifest = ManifestDocument::fromArray($this->root, ['key' => 'example.owner']);
        $registry = new CompiledModuleRegistry(
            [$manifest],
            [],
            ['pa_example_owner' => 'example.owner'],
            [],
            'revision',
        );

        (new ModuleBoundaryChecker($registry))->check();
    }
}
