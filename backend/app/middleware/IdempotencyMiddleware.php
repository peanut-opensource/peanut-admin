<?php

declare(strict_types=1);

namespace PeanutAdmin\App\middleware;

use Closure;
use DateTimeImmutable;
use PDO;
use PeanutAdmin\Kernel\Api\ApiException;
use PeanutAdmin\Kernel\Auth\TenantContext;
use PeanutAdmin\Kernel\Context\PlatformContext;
use PeanutAdmin\Kernel\Idempotency\CanonicalRequestHasher;
use PeanutAdmin\Kernel\Idempotency\IdempotencyKey;
use PeanutAdmin\Kernel\Idempotency\IdempotencyRecord;
use PeanutAdmin\Kernel\Idempotency\PdoIdempotencyRepository;
use think\Request;
use think\Response;

final class IdempotencyMiddleware
{
    public function handle(
        Request $request,
        Closure $next,
        string $operationId,
        string $audience = 'tenant',
    ): Response {
        if (preg_match('/(?:login|refresh|selectTenant)/i', $operationId) === 1) {
            throw new ApiException('IDEMPOTENCY_AUTH_RESPONSE_FORBIDDEN', 500, 'Authentication operations cannot use generic idempotency.');
        }
        $header = $request->header('idempotency-key');
        $key = IdempotencyKey::fromString(is_string($header) ? $header : null);
        $body = $request->post();
        $requestHash = (new CanonicalRequestHasher())->hash(
            $request->method(),
            $request->url(),
            is_array($body) ? $body : [],
        );
        $route = $request->route();
        $routeValues = is_array($route) ? $route : [];
        $repository = new PdoIdempotencyRepository($this->pdo());
        $expires = new DateTimeImmutable('+24 hours');
        $record = $audience === 'tenant'
            ? $this->beginTenant($repository, $routeValues['tenant_context'] ?? null, $operationId, $key, $requestHash, $expires)
            : $this->beginPlatform($repository, $routeValues['platform_context'] ?? null, $operationId, $key, $requestHash, $expires);
        if (!$record->created) {
            if ($record->status === 'completed' && $record->responseStatus !== null && $record->responseBody !== null) {
                return Response::create($record->responseBody, 'json', $record->responseStatus)->header(['X-Idempotent-Replay' => 'true']);
            }
            throw new ApiException('IDEMPOTENCY_REQUEST_PROCESSING', 409, 'The original request is still processing.');
        }
        $response = $next($request);
        $responseBody = $response->getData();
        if ($response->getCode() >= 200 && $response->getCode() < 300 && is_array($responseBody)) {
            $audience === 'tenant'
                ? $repository->completeTenant($record->id, $response->getCode(), $responseBody)
                : $repository->completePlatform($record->id, $response->getCode(), $responseBody);
        }

        return $response;
    }

    private function beginTenant(
        PdoIdempotencyRepository $repository,
        mixed $context,
        string $operationId,
        IdempotencyKey $key,
        string $requestHash,
        DateTimeImmutable $expires,
    ): IdempotencyRecord {
        if (!$context instanceof TenantContext) {
            throw new ApiException('CONTEXT_TENANT_REQUIRED', 403, 'A tenant context is required.');
        }

        return $repository->beginTenant($context->tenantId, $context->memberId, $operationId, $key, $requestHash, $expires);
    }

    private function beginPlatform(
        PdoIdempotencyRepository $repository,
        mixed $context,
        string $operationId,
        IdempotencyKey $key,
        string $requestHash,
        DateTimeImmutable $expires,
    ): IdempotencyRecord {
        if (!$context instanceof PlatformContext) {
            throw new ApiException('CONTEXT_PLATFORM_REQUIRED', 403, 'A platform context is required.');
        }

        return $repository->beginPlatform($context->operatorId, $operationId, $key, $requestHash, $expires);
    }

    private function pdo(): PDO
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
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_EMULATE_PREPARES => false],
        );
    }
}
