<?php

declare(strict_types=1);

namespace PeanutAdmin\App\controller\api\platform\v1;

use PeanutAdmin\App\controller\api\v1\MemberAdminRuntime;
use PeanutAdmin\Kernel\Authorization\Application\AdminAccessException;
use PeanutAdmin\Kernel\Authorization\RevisionPermissionCache;
use PeanutAdmin\Kernel\Context\PlatformContext;
use PeanutAdmin\Kernel\Menu\MenuDefinition;
use PeanutAdmin\Kernel\Menu\MenuRegistry;
use PeanutAdmin\Kernel\Menu\PdoMenuCatalogRepository;
use PeanutAdmin\Kernel\Platform\Application\PlatformWorkspaceQueryService;
use PeanutAdmin\Kernel\Platform\Authorization\PdoPlatformAuthorizationRepository;
use PeanutAdmin\Kernel\Platform\Authorization\PlatformAuthorizationEvaluator;
use think\Request;
use think\Response;

final class PlatformWorkspaceController
{
    public function tenants(Request $request): Response
    {
        return MemberAdminRuntime::run($request, function () use ($request): array {
            $this->context($request);

            return $this->pageResult($request, $this->service()->tenants(MemberAdminRuntime::page($request)));
        });
    }

    public function tenant(Request $request, string $tenantId): Response
    {
        return MemberAdminRuntime::run($request, function () use ($request, $tenantId): array {
            $this->context($request);

            return ['data' => $this->service()->tenant((int) $tenantId)];
        });
    }

    public function operators(Request $request): Response
    {
        return MemberAdminRuntime::run($request, function () use ($request): array {
            $this->context($request);

            return $this->pageResult($request, $this->service()->operators(MemberAdminRuntime::page($request)));
        });
    }

    public function operator(Request $request, string $operatorId): Response
    {
        return MemberAdminRuntime::run($request, function () use ($request, $operatorId): array {
            $this->context($request);

            return ['data' => $this->service()->operator((int) $operatorId)];
        });
    }

    public function roles(Request $request): Response
    {
        return MemberAdminRuntime::run($request, function () use ($request): array {
            $this->context($request);

            return $this->pageResult($request, $this->service()->roles(MemberAdminRuntime::page($request)));
        });
    }

    public function role(Request $request, string $roleId): Response
    {
        return MemberAdminRuntime::run($request, function () use ($request, $roleId): array {
            $this->context($request);

            return ['data' => $this->service()->role((int) $roleId)];
        });
    }

    public function permissions(Request $request): Response
    {
        return MemberAdminRuntime::run($request, function () use ($request): array {
            $this->context($request);

            return ['data' => $this->service()->permissions()];
        });
    }

    public function auditEvents(Request $request): Response
    {
        return MemberAdminRuntime::run($request, function () use ($request): array {
            $this->context($request);

            return $this->pageResult($request, $this->service()->auditEvents(MemberAdminRuntime::page($request)));
        });
    }

    public function menus(Request $request): Response
    {
        return MemberAdminRuntime::run($request, function () use ($request): array {
            $context = $this->context($request);
            $pdo = MemberAdminRuntime::pdo();
            $repository = new PdoMenuCatalogRepository($pdo);
            $deployment = array_fill_keys($repository->activeDeploymentModules(), true);
            $permissions = new PlatformAuthorizationEvaluator(
                new PdoPlatformAuthorizationRepository($pdo),
                new RevisionPermissionCache(),
            );
            $available = static fn(string $module): bool => in_array($module, ['core', 'platform'], true)
                || isset($deployment[$module]);
            $visible = (new MenuRegistry($repository->activeDefinitions('platform')))->visible(
                $context->clientKey,
                $available,
                $available,
                static fn(string $permission): bool => $permissions->allows($context, $permission),
            );

            return ['data' => $this->tree($visible)];
        });
    }

    private function context(Request $request): PlatformContext
    {
        $route = $request->route();
        $context = is_array($route) ? ($route['platform_context'] ?? null) : null;
        if (!$context instanceof PlatformContext) {
            throw new AdminAccessException('CONTEXT_PLATFORM_REQUIRED', 403, 'A platform context is required.');
        }

        return $context;
    }

    private function service(): PlatformWorkspaceQueryService
    {
        return new PlatformWorkspaceQueryService(MemberAdminRuntime::pdo());
    }

    /**
     * @param array{items: list<array<string, mixed>>, total: int} $result
     * @return array{data: list<array<string, mixed>>, meta: array<string, int>}
     */
    private function pageResult(Request $request, array $result): array
    {
        $page = MemberAdminRuntime::page($request);

        return [
            'data' => $result['items'],
            'meta' => [
                'page' => $page->page,
                'page_size' => $page->pageSize,
                'total' => $result['total'],
                'total_pages' => (int) ceil($result['total'] / $page->pageSize),
            ],
        ];
    }

    /**
     * @param list<MenuDefinition> $definitions
     * @return list<array<string, mixed>>
     */
    private function tree(array $definitions): array
    {
        $children = [];
        $roots = [];
        foreach ($definitions as $definition) {
            if ($definition->parentKey === null) {
                $roots[] = $definition;
            } else {
                $children[$definition->parentKey][] = $definition;
            }
        }

        $render = function (MenuDefinition $definition) use (&$render, $children): array {
            return [
                'key' => $definition->key,
                'type' => $definition->type,
                'name' => $definition->name,
                'route_name' => $definition->routeName,
                'route_path' => $definition->routePath,
                'icon' => $definition->icon,
                'children' => array_map($render, $children[$definition->key] ?? []),
            ];
        };

        return array_map($render, $roots);
    }
}
