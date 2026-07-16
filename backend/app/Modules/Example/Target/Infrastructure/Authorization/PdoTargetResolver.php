<?php

declare(strict_types=1);

namespace PeanutAdmin\App\Modules\Example\Target\Infrastructure\Authorization;

use PDO;
use PeanutAdmin\DataPermission\Target\ResolvedResourceTargets;
use PeanutAdmin\DataPermission\Target\ResourceTargetResolver;
use PeanutAdmin\DataPermission\Target\TypedResourceTargetCollection;
use PeanutAdmin\DataPermission\Target\TypedResourceTargetSet;
use PeanutAdmin\Kernel\Auth\TenantContext;
use PeanutAdmin\Kernel\Module\ModuleException;

final readonly class PdoTargetResolver implements ResourceTargetResolver
{
    public function __construct(private PDO $pdo) {}

    public function resolveAndValidate(TenantContext $context, TypedResourceTargetSet $targets): ResolvedResourceTargets
    {
        if ($targets->targetIds === []) {
            return new ResolvedResourceTargets(new TypedResourceTargetCollection([$targets]));
        }
        $table = match ($targets->targetResourceKey) {
            'example.project' => 'pa_example_project',
            'example.queue' => 'pa_example_queue',
            default => throw new ModuleException('AUTHZ_TARGET_TYPE_MISMATCH', 'Unknown example target type.'),
        };
        $placeholders = implode(', ', array_fill(0, count($targets->targetIds), '?'));
        $statement = $this->pdo->prepare(
            "SELECT COUNT(*) FROM {$table} WHERE tenant_id = ? AND status = 'active' AND CAST(id AS CHAR) IN ({$placeholders})",
        );
        $statement->execute([$context->tenantId, ...$targets->targetIds]);
        if ((int) $statement->fetchColumn() !== count($targets->targetIds)) {
            throw new ModuleException('AUTHZ_TARGET_NOT_FOUND', 'Target does not exist in the trusted tenant context.');
        }

        return new ResolvedResourceTargets(new TypedResourceTargetCollection([$targets]));
    }
}
