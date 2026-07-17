<?php

declare(strict_types=1);

namespace PeanutAdmin\App\controller\api\v1;

use PeanutAdmin\App\module\OpisTenantModuleConfigValidator;
use PeanutAdmin\App\module\RuntimeModuleRegistry;
use PeanutAdmin\Kernel\Authorization\Application\AdminAccessException;
use PeanutAdmin\Kernel\Authorization\Application\Etag;
use PeanutAdmin\Kernel\Authorization\PdoTenantAuthorizationRepository;
use PeanutAdmin\Kernel\Authorization\RevisionPermissionCache;
use PeanutAdmin\Kernel\Authorization\TenantAuthorizationEvaluator;
use PeanutAdmin\Kernel\Menu\MenuDefinition;
use PeanutAdmin\Kernel\Menu\MenuRegistry;
use PeanutAdmin\Kernel\Menu\PdoMenuCatalogRepository;
use PeanutAdmin\Kernel\Module\TenantModuleConfigurationService;
use PeanutAdmin\Kernel\Tenancy\Application\TenantWorkspaceQueryService;
use think\Request;
use think\Response;

final class TenantWorkspaceController
{
    public function show(Request $request): Response
    {
        return MemberAdminRuntime::run($request, function () use ($request): array {
            $context = MemberAdminRuntime::context($request);

            return ['data' => $this->service()->tenant($context->tenantId)];
        });
    }

    public function permissions(Request $request): Response
    {
        return MemberAdminRuntime::run($request, function () use ($request): array {
            $context = MemberAdminRuntime::context($request);

            return ['data' => $this->service()->permissions($context->tenantId)];
        });
    }

    public function modules(Request $request): Response
    {
        return MemberAdminRuntime::run($request, function () use ($request): array {
            $context = MemberAdminRuntime::context($request);

            return ['data' => $this->service()->modules($context->tenantId)];
        });
    }

    public function updateModuleConfig(Request $request, string $moduleKey): Response
    {
        return MemberAdminRuntime::run($request, function () use ($request, $moduleKey): array {
            $context = MemberAdminRuntime::context($request);
            $body = MemberAdminRuntime::body($request);
            $config = $body['config'] ?? null;
            if (!is_array($config) || array_is_list($config)) {
                throw AdminAccessException::invalid(
                    'MODULE_CONFIG_INVALID',
                    'The config field must be a JSON object.',
                );
            }
            $module = (new TenantModuleConfigurationService(
                MemberAdminRuntime::pdo(),
                RuntimeModuleRegistry::compile(),
                new OpisTenantModuleConfigValidator(),
            ))->update(
                $context->tenantId,
                $moduleKey,
                $config,
                Etag::parse(MemberAdminRuntime::header($request, 'if-match')),
                $context->memberId,
                $context->accountId,
                $context->requestId,
            );

            return ['data' => $module, 'etag' => Etag::format((int) $module['revision'])];
        });
    }

    public function auditEvents(Request $request): Response
    {
        return MemberAdminRuntime::run($request, function () use ($request): array {
            $context = MemberAdminRuntime::context($request);
            $page = MemberAdminRuntime::page($request);
            $result = $this->service()->auditEvents($context->tenantId, $page);

            return [
                'data' => $result['items'],
                'meta' => [
                    'page' => $page->page,
                    'page_size' => $page->pageSize,
                    'total' => $result['total'],
                    'total_pages' => (int) ceil($result['total'] / $page->pageSize),
                ],
            ];
        });
    }

    public function menus(Request $request): Response
    {
        return MemberAdminRuntime::run($request, function () use ($request): array {
            $context = MemberAdminRuntime::context($request);
            $pdo = MemberAdminRuntime::pdo();
            $repository = new PdoMenuCatalogRepository($pdo);
            $deployment = array_fill_keys($repository->activeDeploymentModules(), true);
            $tenant = array_fill_keys($repository->activeTenantModules($context->tenantId), true);
            $permissions = new TenantAuthorizationEvaluator(
                new PdoTenantAuthorizationRepository($pdo),
                new RevisionPermissionCache(),
            );
            $visible = (new MenuRegistry($repository->activeDefinitions('tenant')))->visible(
                $context->clientKey,
                static fn(string $module): bool => $module === 'core' || isset($deployment[$module]),
                static fn(string $module): bool => $module === 'core' || isset($tenant[$module]),
                static fn(string $permission): bool => $permissions->allows($context, $permission),
            );

            return [
                'data' => $this->tree($visible),
                'meta' => ['authorization_revision' => (string) $context->authorizationRevision],
            ];
        });
    }

    private function service(): TenantWorkspaceQueryService
    {
        return new TenantWorkspaceQueryService(MemberAdminRuntime::pdo());
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
