<?php

declare(strict_types=1);

namespace PeanutAdmin\App\controller\api\v1;

use PeanutAdmin\Kernel\Authorization\Application\Etag;
use PeanutAdmin\Kernel\Authorization\Application\RoleAdminService;
use think\Request;
use think\Response;

final class RoleController
{
    public function index(Request $request): Response
    {
        return MemberAdminRuntime::run($request, function () use ($request): array {
            $context = MemberAdminRuntime::context($request);
            $page = MemberAdminRuntime::page($request);
            $result = $this->service()->list($context->tenantId, $page);

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

    public function show(Request $request, string $roleId): Response
    {
        return MemberAdminRuntime::run($request, function () use ($request, $roleId): array {
            $context = MemberAdminRuntime::context($request);
            $role = $this->service()->get($context->tenantId, (int) $roleId);

            return ['data' => $role, 'etag' => Etag::format((int) $role['revision'])];
        });
    }

    public function create(Request $request): Response
    {
        return MemberAdminRuntime::run($request, function () use ($request): array {
            $context = MemberAdminRuntime::context($request);
            $body = MemberAdminRuntime::body($request);
            $role = $this->service()->create(
                $context->tenantId,
                (string) ($body['key'] ?? ''),
                (string) ($body['name'] ?? ''),
                isset($body['description']) ? (string) $body['description'] : null,
                $context->memberId,
                $context->accountId,
                $context->requestId,
            );

            return [
                'data' => $role,
                'status' => 201,
                'etag' => Etag::format((int) $role['revision']),
                'location' => '/api/v1/roles/' . $role['id'],
            ];
        });
    }

    public function update(Request $request, string $roleId): Response
    {
        return MemberAdminRuntime::run($request, function () use ($request, $roleId): array {
            $context = MemberAdminRuntime::context($request);
            $body = MemberAdminRuntime::body($request);
            $role = $this->service()->update(
                $context->tenantId,
                (int) $roleId,
                (string) ($body['name'] ?? ''),
                isset($body['description']) ? (string) $body['description'] : null,
                Etag::parse(MemberAdminRuntime::header($request, 'if-match')),
                $context->memberId,
                $context->accountId,
                $context->requestId,
            );

            return ['data' => $role, 'etag' => Etag::format((int) $role['revision'])];
        });
    }

    public function replacePermissions(Request $request, string $roleId): Response
    {
        return MemberAdminRuntime::run($request, function () use ($request, $roleId): array {
            $context = MemberAdminRuntime::context($request);
            $body = MemberAdminRuntime::body($request);
            $rawKeys = is_array($body['permission_keys'] ?? null) ? $body['permission_keys'] : [];
            $role = $this->service()->replacePermissions(
                $context->tenantId,
                (int) $roleId,
                array_values(array_map(static fn(mixed $key): string => (string) $key, $rawKeys)),
                Etag::parse(MemberAdminRuntime::header($request, 'if-match')),
                $context->memberId,
                $context->accountId,
                $context->requestId,
            );

            return ['data' => $role, 'etag' => Etag::format((int) $role['revision'])];
        });
    }

    public function archive(Request $request, string $roleId): Response
    {
        return MemberAdminRuntime::run($request, function () use ($request, $roleId): array {
            $context = MemberAdminRuntime::context($request);
            $role = $this->service()->archive(
                $context->tenantId,
                (int) $roleId,
                Etag::parse(MemberAdminRuntime::header($request, 'if-match')),
                $context->memberId,
                $context->accountId,
                $context->requestId,
            );

            return ['data' => $role, 'etag' => Etag::format((int) $role['revision'])];
        });
    }

    private function service(): RoleAdminService
    {
        return new RoleAdminService(MemberAdminRuntime::pdo());
    }
}
