<?php

declare(strict_types=1);

namespace GeneratedHost\Admin\Modules\Fixture\Record;

use DateTimeImmutable;
use PDO;
use PeanutAdmin\Kernel\Api\RequestId;
use PeanutAdmin\Kernel\Auth\TenantContext;
use PeanutAdmin\Kernel\Authorization\PermissionRequirement;
use PeanutAdmin\Kernel\Host\AuthorizedExternalOperation;
use PeanutAdmin\Kernel\Host\ExternalOperationDefinition;
use PeanutAdmin\Kernel\Host\ExternalOperationRequest;
use PeanutAdmin\Kernel\Host\ExternalOperationResult;
use PeanutAdmin\Kernel\Module\ModuleProvider;
use RuntimeException;

final class FixtureRecordModuleProvider implements ModuleProvider
{
    public function moduleKey(): string
    {
        return 'fixture.record';
    }
}

final class FixtureRecordHost
{
    public static function operation(): ExternalOperationDefinition
    {
        return new ExternalOperationDefinition(
            'fixtureRecordCreate',
            'POST',
            '/api/fixture/v1/records',
            'tenant',
            'fixture.record',
            new PermissionRequirement('tenant', ['fixture.record.create']),
            'fixture.record',
            'targets',
            'one_required',
            true,
            true,
        );
    }

    /** @param list<array<string, mixed>> $targets */
    public static function request(
        TenantContext $context,
        string $requestId,
        array $body,
        array $targets,
        string $idempotencyKey,
    ): ExternalOperationRequest {
        $now = new DateTimeImmutable('2026-08-12T00:00:00Z');

        return new ExternalOperationRequest(
            RequestId::fromHeader($requestId),
            $context,
            'POST',
            '/api/fixture/v1/records',
            $body,
            $targets,
            $idempotencyKey,
            $now,
            $now->modify('+1 hour'),
        );
    }

    public static function handler(int &$calls): callable
    {
        return static function (
            AuthorizedExternalOperation $authorized,
            ExternalOperationRequest $request,
            PDO $pdo,
        ) use (&$calls): ExternalOperationResult {
            ++$calls;
            if (!$authorized->context instanceof TenantContext) {
                throw new RuntimeException('Trusted Tenant context is required.');
            }
            $statement = $pdo->prepare(
                'INSERT INTO fixture_record (tenant_id, scope_id, name, revision) VALUES (:tenant, :scope, :name, 1)',
            );
            $statement->execute([
                'tenant' => $authorized->context->tenantId,
                'scope' => (int) $authorized->targets[0]->targetIds[0],
                'name' => (string) ($request->body['name'] ?? ''),
            ]);
            $id = (string) $pdo->lastInsertId();

            return new ExternalOperationResult(
                201,
                ['data' => ['id' => $id]],
                'fixture.record.created',
                'fixture.record.create',
                ['revision' => 1],
                'fixture.record',
                $id,
            );
        };
    }

    public static function outbox(int &$calls): callable
    {
        return static function (PDO $pdo, ExternalOperationResult $result) use (&$calls): void {
            ++$calls;
            $statement = $pdo->prepare(
                'INSERT INTO fixture_outbox (tenant_id, event_key, resource_id) VALUES (:tenant, :event, :resource)',
            );
            $statement->execute([
                'tenant' => 1,
                'event' => $result->auditEventType,
                'resource' => $result->resourceId,
            ]);
        };
    }
}
