<?php

declare(strict_types=1);

namespace PeanutAdmin\App\middleware;

use Closure;
use DateTimeImmutable;
use DateTimeZone;
use PDO;
use PeanutAdmin\Kernel\Auth\TenantContext;
use PeanutAdmin\Kernel\Authorization\AuthorizationException;
use PeanutAdmin\Kernel\Module\ModuleGuard as KernelModuleGuard;
use PeanutAdmin\Kernel\Module\ModuleRuntimeRepository;
use PeanutAdmin\Kernel\Module\Persistence\PdoModuleRuntimeRepository;
use think\Request;
use think\Response;

final class ModuleGuard
{
    public function __construct(private readonly ?ModuleRuntimeRepository $repository = null) {}

    public function handle(Request $request, Closure $next, string $moduleKey): Response
    {
        $route = $request->route();
        $routeValues = is_array($route) ? $route : [];
        $context = $routeValues['tenant_context'] ?? null;
        if (!$context instanceof TenantContext) {
            throw new AuthorizationException('CONTEXT_TENANT_REQUIRED');
        }

        $guard = new KernelModuleGuard($this->repository ?? new PdoModuleRuntimeRepository(self::pdo()));
        $guard->assertDeployment($moduleKey);
        $guard->assertTenant($context->tenantId, $moduleKey, new DateTimeImmutable('now', new DateTimeZone('UTC')));

        return $next($request);
    }

    private static function pdo(): PDO
    {
        return new PDO(
            sprintf(
                'mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4',
                getenv('DB_HOST') ?: '127.0.0.1',
                (int) (getenv('DB_PORT') ?: 3306),
                getenv('DB_DATABASE') ?: 'peanut_admin',
            ),
            getenv('DB_USERNAME') ?: 'peanut_admin',
            getenv('DB_PASSWORD') ?: 'peanut_admin_dev',
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ],
        );
    }
}
