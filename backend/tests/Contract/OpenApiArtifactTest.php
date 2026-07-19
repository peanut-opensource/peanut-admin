<?php

declare(strict_types=1);

namespace PeanutAdmin\App\Tests\Contract;

use PHPUnit\Framework\TestCase;

final class OpenApiArtifactTest extends TestCase
{
    public function testGeneratedRouteAndTypeArtifactsAreCompleteAndUnique(): void
    {
        $root = dirname(__DIR__, 3);
        $routes = require $root . '/backend/route/openapi-generated.php';
        $operationIds = array_map(static fn(array $binding): string => $binding[3], $routes);

        self::assertCount(78, $routes);
        self::assertCount(78, array_unique($operationIds));
        self::assertArrayHasKey('GET /api/v1/account', $routes);
        self::assertArrayHasKey('PATCH /api/v1/account', $routes);
        self::assertArrayHasKey('POST /api/v1/account/password', $routes);
        self::assertArrayHasKey('GET /api/v1/authorization/target-candidates', $routes);
        self::assertArrayHasKey('GET /api/v1/example/reference-items/candidates', $routes);
        self::assertArrayHasKey('PUT /api/platform/v1/tenants/{tenant_id}/modules/{module_key}', $routes);

        $types = (string) file_get_contents($root . '/packages/web/admin-core/src/generated/api.d.ts');
        self::assertStringContainsString('listExampleWorkItems', $types);
        self::assertStringContainsString('TargetSet', $types);
        self::assertStringContainsString('SelectTenantRequest', $types);
        self::assertStringNotContainsString('tenant_id?: number', $types);
        self::assertStringNotContainsString('data: unknown', $types);
        self::assertDoesNotMatchRegularExpression('/(?:\| unknown|unknown \|)/', $types);
    }

    public function testGeneratedRoutesCarryTypedSuccessContracts(): void
    {
        $routes = require dirname(__DIR__, 3) . '/backend/route/openapi-generated.php';

        foreach ($routes as $route => $binding) {
            self::assertCount(12, $binding, $route);
            $status = $binding[8];
            $mediaType = $binding[9];
            $headers = $binding[10];
            $schema = $binding[11];

            self::assertContains($status, [200, 201, 204], $route);
            self::assertSame($status === 204 ? null : 'application/json', $mediaType, $route);
            self::assertContains('X-Request-Id', $headers, $route);
            self::assertContains('Cache-Control', $headers, $route);
            self::assertSame($status === 204 ? null : true, $schema === null ? null : true, $route);
        }
    }

    public function testGeneratedRoutesKeepAudienceAndPermissionContractsSeparate(): void
    {
        $routes = require dirname(__DIR__, 3) . '/backend/route/openapi-generated.php';

        foreach ($routes as $route => $binding) {
            [$class, $method, $permission, $operationId, $audience, $requiresAuth, $idempotent, $moduleKey] = $binding;
            self::assertMatchesRegularExpression('/^[a-z][A-Za-z0-9]+$/', $operationId, $route);
            self::assertNotSame('', $class, $route);
            self::assertNotSame('', $method, $route);
            self::assertContains($audience, ['tenant', 'platform'], $route);
            self::assertIsBool($requiresAuth, $route);
            self::assertIsBool($idempotent, $route);
            self::assertTrue($permission === null || $requiresAuth, $route);
            self::assertTrue($moduleKey === null || ($audience === 'tenant' && $requiresAuth && $permission !== null), $route);

            if (str_starts_with($route, 'GET /api/platform/') || str_starts_with($route, 'POST /api/platform/')
                || str_starts_with($route, 'PUT /api/platform/') || str_starts_with($route, 'PATCH /api/platform/')
                || str_starts_with($route, 'DELETE /api/platform/')) {
                self::assertTrue($permission === null || str_starts_with($permission, 'platform.'), $route);

                continue;
            }

            self::assertFalse(is_string($permission) && str_starts_with($permission, 'platform.'), $route);
        }
    }

    public function testOptionalModuleRoutesDeclareTheirRuntimeModule(): void
    {
        $routes = require dirname(__DIR__, 3) . '/backend/route/openapi-generated.php';
        $expected = [
            'GET /api/v1/example/reference-items/candidates' => 'example.reference',
            'GET /api/v1/example/work-items' => 'example.work-item',
            'GET /api/v1/example/work-items/aggregate' => 'example.work-item',
            'GET /api/v1/example/work-items/{work_item_id}' => 'example.work-item',
            'PATCH /api/v1/example/work-items/{work_item_id}' => 'example.work-item',
            'POST /api/v1/example/work-item-view-policies' => 'example.work-item',
            'POST /api/v1/example/work-items' => 'example.work-item',
        ];
        $actual = [];
        foreach ($routes as $route => $binding) {
            if ($binding[7] !== null) {
                $actual[$route] = $binding[7];
            }
        }

        self::assertSame($expected, $actual);
    }

    public function testExampleOperationsUseConcreteHandlers(): void
    {
        $routes = require dirname(__DIR__, 3) . '/backend/route/openapi-generated.php';

        foreach ($routes as $route => $binding) {
            if (!str_contains($route, '/api/v1/example/')) {
                continue;
            }

            self::assertNotSame(
                'PeanutAdmin\\App\\controller\\api\\v1\\ContractController',
                $binding[0],
                $route,
            );
        }
    }

    public function testStaticRoutePrecedesParameterRouteAtTheSamePathLevel(): void
    {
        $routes = require dirname(__DIR__, 3) . '/backend/route/openapi-generated.php';
        $orderedRoutes = array_keys($routes);
        $aggregate = array_search('GET /api/v1/example/work-items/aggregate', $orderedRoutes, true);
        $detail = array_search('GET /api/v1/example/work-items/{work_item_id}', $orderedRoutes, true);

        self::assertIsInt($aggregate);
        self::assertIsInt($detail);
        self::assertLessThan($detail, $aggregate);
    }
}
