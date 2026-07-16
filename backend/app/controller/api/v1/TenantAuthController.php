<?php

declare(strict_types=1);

namespace PeanutAdmin\App\controller\api\v1;

use PeanutAdmin\App\controller\api\AuthHttpRuntime;
use PeanutAdmin\App\middleware\TenantAuthRuntimeFactory;
use PeanutAdmin\Kernel\Http\TenantAuthEndpoint;
use PeanutAdmin\Kernel\Http\TenantRefreshCookie;
use think\Request;
use think\Response;

final class TenantAuthController
{
    public function login(Request $request): Response
    {
        $body = AuthHttpRuntime::body($request);
        $email = AuthHttpRuntime::requiredString($body, 'email');
        $password = AuthHttpRuntime::requiredString($body, 'password');
        $tenantCode = AuthHttpRuntime::optionalString($body, 'tenant_code');

        return AuthHttpRuntime::tenantResponse($this->endpoint()->login(
            $email,
            $password,
            $tenantCode,
            AuthHttpRuntime::ipAddress($request),
            AuthHttpRuntime::userAgent($request),
            AuthHttpRuntime::requestId($request),
        ));
    }

    public function selectTenant(Request $request): Response
    {
        $body = AuthHttpRuntime::body($request);
        $challengeToken = AuthHttpRuntime::requiredString($body, 'challenge_token');
        $tenantId = AuthHttpRuntime::positiveInteger($body, 'tenant_id');

        return AuthHttpRuntime::tenantResponse($this->endpoint()->selectTenant(
            $challengeToken,
            $tenantId,
            AuthHttpRuntime::ipAddress($request),
            AuthHttpRuntime::userAgent($request),
            AuthHttpRuntime::requestId($request),
        ));
    }

    public function refresh(Request $request): Response
    {
        $refreshToken = AuthHttpRuntime::requiredCookie($request, TenantRefreshCookie::NAME);

        return AuthHttpRuntime::tenantResponse($this->endpoint()->refresh(
            $refreshToken,
            AuthHttpRuntime::trustedOrigin($request),
            AuthHttpRuntime::ipAddress($request),
            AuthHttpRuntime::userAgent($request),
            AuthHttpRuntime::requestId($request),
        ));
    }

    public function context(Request $request): Response
    {
        return AuthHttpRuntime::tenantResponse($this->endpoint()->context(
            AuthHttpRuntime::bearerToken($request),
            AuthHttpRuntime::requestId($request),
        ));
    }

    public function switchChallenge(Request $request): Response
    {
        return AuthHttpRuntime::tenantResponse($this->endpoint()->switchChallenge(
            AuthHttpRuntime::bearerToken($request),
            AuthHttpRuntime::ipAddress($request),
            AuthHttpRuntime::userAgent($request),
            AuthHttpRuntime::requestId($request),
        ));
    }

    public function logout(Request $request): Response
    {
        return AuthHttpRuntime::tenantResponse($this->endpoint()->logout(
            AuthHttpRuntime::bearerToken($request),
            AuthHttpRuntime::requestId($request),
        ));
    }

    public function logoutAll(Request $request): Response
    {
        return AuthHttpRuntime::tenantResponse($this->endpoint()->logoutAll(
            AuthHttpRuntime::bearerToken($request),
            AuthHttpRuntime::requestId($request),
        ));
    }

    private function endpoint(): TenantAuthEndpoint
    {
        return new TenantAuthEndpoint(TenantAuthRuntimeFactory::create());
    }
}
