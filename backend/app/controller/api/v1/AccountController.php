<?php

declare(strict_types=1);

namespace PeanutAdmin\App\controller\api\v1;

use PeanutAdmin\App\controller\api\AuthHttpRuntime;
use PeanutAdmin\App\middleware\TenantAccountRuntimeFactory;
use PeanutAdmin\App\middleware\TenantAuthRuntimeFactory;
use PeanutAdmin\Kernel\Api\OpenApiHandlerContract;
use PeanutAdmin\Kernel\Authorization\Application\AdminAccessException;
use PeanutAdmin\Kernel\Http\TenantRefreshCookie;
use PeanutAdmin\Kernel\Identity\SelfService\AccountSelfService;
use think\Request;
use think\Response;

final class AccountController
{
    #[OpenApiHandlerContract]
    public function show(Request $request): Response
    {
        return MemberAdminRuntime::run($request, function () use ($request): array {
            $context = MemberAdminRuntime::context($request);

            return ['data' => $this->service()->profile(
                $context->tenantId,
                $context->memberId,
                $context->accountId,
            )];
        });
    }

    #[OpenApiHandlerContract]
    public function update(Request $request): Response
    {
        return MemberAdminRuntime::run($request, function () use ($request): array {
            $context = MemberAdminRuntime::context($request);
            $body = MemberAdminRuntime::body($request);
            $displayName = $body['display_name'] ?? null;
            $avatarUri = $body['avatar_uri'] ?? null;
            if (!is_string($displayName)) {
                throw AdminAccessException::invalid(
                    'ACCOUNT_PROFILE_INVALID',
                    'The display name must be a string.',
                );
            }
            if ($avatarUri !== null && !is_string($avatarUri)) {
                throw AdminAccessException::invalid(
                    'AVATAR_URI_INVALID',
                    'The avatar URI must be a string or null.',
                );
            }

            return ['data' => $this->service()->updateProfile(
                $context->tenantId,
                $context->memberId,
                $context->accountId,
                $displayName,
                $avatarUri,
                $context->requestId,
            )];
        });
    }

    #[OpenApiHandlerContract(
        successStatus: 204,
        hasJsonBody: false,
        headers: OpenApiHandlerContract::SESSION_CLEARED_HEADERS,
    )]
    public function changePassword(Request $request): Response
    {
        $context = MemberAdminRuntime::context($request);
        $body = MemberAdminRuntime::body($request);
        $currentPassword = $body['current_password'] ?? null;
        $newPassword = $body['new_password'] ?? null;
        if (!is_string($currentPassword)) {
            throw AdminAccessException::invalid(
                'CURRENT_PASSWORD_INVALID',
                'The current password is invalid.',
            );
        }
        if (!is_string($newPassword)) {
            throw AdminAccessException::invalid(
                'NEW_PASSWORD_INVALID',
                'The new password is invalid.',
            );
        }

        try {
            $this->service()->changePassword(
                $context->tenantId,
                $context->memberId,
                $context->accountId,
                $context->sessionKey,
                $currentPassword,
                $newPassword,
                AuthHttpRuntime::ipAddress($request),
                AuthHttpRuntime::userAgent($request),
                $context->requestId,
            );
        } catch (AdminAccessException $exception) {
            if ($exception->errorCode !== 'PASSWORD_CHANGE_RATE_LIMITED') {
                throw $exception;
            }

            return AuthHttpRuntime::response(429, [
                'type' => '/docs/problems/password-change-rate-limited',
                'title' => 'Request rejected',
                'status' => 429,
                'detail' => $exception->getMessage(),
                'instance' => 'urn:request:' . $context->requestId,
                'code' => $exception->errorCode,
                'request_id' => $context->requestId,
            ], [
                'Content-Type' => 'application/problem+json',
                'X-Request-Id' => $context->requestId,
                'Retry-After' => (string) AccountSelfService::PASSWORD_CHANGE_RETRY_AFTER_SECONDS,
            ]);
        }
        $client = TenantAuthRuntimeFactory::create($context->clientKey)->client();

        return AuthHttpRuntime::response(204, null, [
            'Set-Cookie' => TenantRefreshCookie::clear($client),
        ]);
    }

    private function service(): AccountSelfService
    {
        return TenantAccountRuntimeFactory::create();
    }
}
