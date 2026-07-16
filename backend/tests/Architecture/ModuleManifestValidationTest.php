<?php

declare(strict_types=1);

namespace PeanutAdmin\App\Tests\Architecture;

use PeanutAdmin\App\module\OpisManifestSchemaValidator;
use PeanutAdmin\Kernel\Module\ModuleException;
use PHPUnit\Framework\TestCase;

final class ModuleManifestValidationTest extends TestCase
{
    public function testOpisValidatorAcceptsTheMinimalP0ManifestAndCatalog(): void
    {
        $validator = new OpisManifestSchemaValidator(
            dirname(__DIR__, 3) . '/packages/php/kernel/resources/schemas/module-manifest.schema.json',
        );
        $validator->assertValid(json_decode((string) json_encode([
            'schema_version' => 1,
            'key' => 'example.target',
            'name' => 'Example Target',
            'description' => 'Fixture module',
            'version' => '1.0.0',
            'kernel_constraint' => '^1.0',
            'license' => 'Apache-2.0',
            'backend' => ['provider' => 'PeanutAdmin\\App\\Modules\\Example\\Target\\ModuleProvider'],
            'frontend' => (object) [],
            'database' => ['owned_tables' => []],
            'contracts' => ['exports' => [], 'events' => []],
            'tenant' => ['enableable' => true, 'requires' => []],
            'catalog' => [
                'menus' => [],
                'permissions' => [],
                'protected_resources' => [],
                'target_types' => [],
                'data_conditions' => [],
                'system_actors' => [],
            ],
        ], JSON_THROW_ON_ERROR), false, 512, JSON_THROW_ON_ERROR));

        self::expectNotToPerformAssertions();
    }

    public function testOpisValidatorRejectsUnknownSchemaVersionAndProperties(): void
    {
        $validator = new OpisManifestSchemaValidator(
            dirname(__DIR__, 3) . '/packages/php/kernel/resources/schemas/module-manifest.schema.json',
        );

        $this->expectException(ModuleException::class);
        $validator->assertValid((object) [
            'schema_version' => 2,
            'unexpected' => true,
        ]);
    }
}
