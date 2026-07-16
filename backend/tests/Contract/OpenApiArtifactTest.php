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

        self::assertCount(75, $routes);
        self::assertCount(75, array_unique($operationIds));
        self::assertArrayHasKey('GET /api/v1/authorization/target-candidates', $routes);
        self::assertArrayHasKey('GET /api/v1/example/reference-items/candidates', $routes);
        self::assertArrayHasKey('PUT /api/platform/v1/tenants/{tenant_id}/modules/{module_key}', $routes);

        $types = (string) file_get_contents($root . '/packages/web/admin-core/src/generated/api.d.ts');
        self::assertStringContainsString('listExampleWorkItems', $types);
        self::assertStringContainsString('TargetSet', $types);
        self::assertStringContainsString('SelectTenantRequest', $types);
        self::assertStringNotContainsString('tenant_id?: number', $types);
    }

    public function testGeneratedRoutesKeepAudienceAndPermissionContractsSeparate(): void
    {
        $routes = require dirname(__DIR__, 3) . '/backend/route/openapi-generated.php';

        foreach ($routes as $route => $binding) {
            [$class, $method, $permission, $operationId, $audience, $requiresAuth, $idempotent] = $binding;
            self::assertMatchesRegularExpression('/^[a-z][A-Za-z0-9]+$/', $operationId, $route);
            self::assertNotSame('', $class, $route);
            self::assertNotSame('', $method, $route);
            self::assertContains($audience, ['tenant', 'platform'], $route);
            self::assertIsBool($requiresAuth, $route);
            self::assertIsBool($idempotent, $route);
            self::assertTrue($permission === null || $requiresAuth, $route);

            if (str_starts_with($route, 'GET /api/platform/') || str_starts_with($route, 'POST /api/platform/')
                || str_starts_with($route, 'PUT /api/platform/') || str_starts_with($route, 'PATCH /api/platform/')
                || str_starts_with($route, 'DELETE /api/platform/')) {
                self::assertTrue($permission === null || str_starts_with($permission, 'platform.'), $route);

                continue;
            }

            self::assertFalse(is_string($permission) && str_starts_with($permission, 'platform.'), $route);
        }
    }
}
