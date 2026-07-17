<?php

declare(strict_types=1);

namespace PeanutAdmin\App\controller\api\v1;

use PeanutAdmin\App\authorization\DataPermissionRuntimeFactory;
use PeanutAdmin\DataPermission\Application\DataPolicyAdminService;
use PeanutAdmin\DataPermission\Target\TargetCatalogQuery;
use PeanutAdmin\Kernel\Api\OpenApiHandlerContract;
use PeanutAdmin\Kernel\Authorization\Application\AdminAccessException;
use PeanutAdmin\Kernel\Authorization\Application\Etag;
use think\Request;
use think\Response;

final class DataAuthorizationController
{
    #[OpenApiHandlerContract]
    public function targetCandidates(Request $request): Response
    {
        return MemberAdminRuntime::run($request, function () use ($request): array {
            $context = MemberAdminRuntime::context($request);
            $resourceKey = (string) $request->get('resource_key', '');
            $operation = (string) $request->get('operation', '');
            $targetResourceKey = (string) $request->get('target_resource_key', '');
            $targetRole = (string) $request->get('target_role', 'primary');
            $mode = (string) $request->get('mode', 'runtime');
            $search = (string) $request->get('q', '');
            if (mb_strlen($search) > 100) {
                throw AdminAccessException::invalid('SEARCH_INVALID', 'Search text is limited to 100 characters.');
            }
            if ($resourceKey === '' || $operation === '' || $targetResourceKey === '' || $targetRole === '') {
                throw AdminAccessException::invalid(
                    'TARGET_CATALOG_QUERY_INVALID',
                    'Resource, operation, target type, and target role are required.',
                );
            }
            $page = MemberAdminRuntime::page($request);
            $pdo = MemberAdminRuntime::pdo();
            $runtime = DataPermissionRuntimeFactory::runtime($pdo);
            $result = DataPermissionRuntimeFactory::create($pdo, null, $runtime)->searchAllowedTargets(
                $context,
                $resourceKey,
                $operation,
                new TargetCatalogQuery(
                    $targetResourceKey,
                    $search,
                    $page->page,
                    $page->pageSize,
                    $targetRole,
                    $mode,
                ),
            );
            $service = new DataPolicyAdminService(
                $pdo,
                $runtime->targetResolvers,
            );

            return [
                'data' => array_map(
                    static fn(array $item): array => [
                        'target_resource_key' => $targetResourceKey,
                        'target_id' => $item['id'],
                        'label' => $item['label'],
                    ],
                    $result->items,
                ),
                'meta' => [
                    'page' => $page->page,
                    'page_size' => $page->pageSize,
                    'total' => $result->total,
                    'total_pages' => (int) ceil($result->total / $page->pageSize),
                    'target_cardinality' => $service->targetCardinality(
                        $context->tenantId,
                        $resourceKey,
                        $operation,
                    ),
                    'available_count' => $result->total,
                ],
            ];
        });
    }

    #[OpenApiHandlerContract(headers: OpenApiHandlerContract::VERSIONED_HEADERS)]
    public function getRolePolicy(
        Request $request,
        string $roleId,
        string $resourceKey,
        string $operation,
    ): Response {
        return MemberAdminRuntime::run($request, function () use (
            $request,
            $roleId,
            $resourceKey,
            $operation,
        ): array {
            $context = MemberAdminRuntime::context($request);
            $policy = $this->service()->get(
                $context->tenantId,
                (int) $roleId,
                $resourceKey,
                $operation,
            );

            return ['data' => $policy, 'etag' => Etag::format((int) $policy['revision'])];
        });
    }

    #[OpenApiHandlerContract(headers: OpenApiHandlerContract::VERSIONED_HEADERS)]
    public function replaceRolePolicy(
        Request $request,
        string $roleId,
        string $resourceKey,
        string $operation,
    ): Response {
        return MemberAdminRuntime::run($request, function () use (
            $request,
            $roleId,
            $resourceKey,
            $operation,
        ): array {
            $context = MemberAdminRuntime::context($request);
            $ifMatch = MemberAdminRuntime::header($request, 'if-match');
            $policy = $this->service()->replace(
                $context,
                (int) $roleId,
                $resourceKey,
                $operation,
                MemberAdminRuntime::body($request),
                $ifMatch === null || $ifMatch === '' ? null : Etag::parse($ifMatch),
            );

            return ['data' => $policy, 'etag' => Etag::format((int) $policy['revision'])];
        });
    }

    private function service(): DataPolicyAdminService
    {
        $pdo = MemberAdminRuntime::pdo();

        return new DataPolicyAdminService(
            $pdo,
            DataPermissionRuntimeFactory::runtime($pdo)->targetResolvers,
        );
    }
}
