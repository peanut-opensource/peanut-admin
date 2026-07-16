<?php

declare(strict_types=1);

namespace PeanutAdmin\App\Modules\Example\WorkItem\Application;

use PDO;
use PeanutAdmin\App\Modules\Example\Reference\Contracts\ReferenceScope;
use PeanutAdmin\DataPermission\Context\AuthorizationContext;
use PeanutAdmin\DataPermission\Engine\DataPermissionEngine;
use PeanutAdmin\DataPermission\Target\TypedResourceTargetCollection;
use PeanutAdmin\Kernel\Auth\TenantContext;
use PeanutAdmin\Kernel\Module\ModuleException;

final readonly class WorkItemCommandService
{
    public function __construct(
        private PDO $pdo,
        private ReferenceScope $referenceScope,
        private DataPermissionEngine $authorization,
    ) {}

    public function create(
        TenantContext $context,
        TypedResourceTargetCollection $targets,
        CreateWorkItem $command,
    ): string {
        $decision = $this->authorization->decideCreate(
            $context,
            'example.work-item',
            'create',
            $targets,
        );
        if (!$decision->allowed) {
            throw new ModuleException($decision->reasonCode, 'Create targets are outside the effective data policy.');
        }
        if ($targets->countForRole('primary') !== 1 || !$this->contains($targets, 'example.project', $command->projectId)) {
            throw new ModuleException('AUTHZ_TARGET_CARDINALITY_INVALID', 'Create requires exactly the authorized Project.');
        }
        if ($command->queueId !== null && !$this->contains($targets, 'example.queue', $command->queueId)) {
            throw new ModuleException('AUTHZ_TARGET_TYPE_MISMATCH', 'Queue must be explicitly authorized as Queue.');
        }
        $authorizationContext = new AuthorizationContext(
            $context,
            $command->departmentId,
        );
        if (!$this->referenceScope->canUse($authorizationContext, $command->referenceItemId, $targets)) {
            throw new ModuleException('AUTHZ_SHARED_MASTER_SCOPE_DENIED', 'Reference item is outside the selected target scope.');
        }
        $statement = $this->pdo->prepare(<<<'SQL'
INSERT INTO pa_example_work_item (
    tenant_id, project_id, queue_id, reference_item_id, owner_member_id,
    department_id, title, status, revision, created_by_member_id, created_at, updated_at
) VALUES (
    :tenant_id, :project_id, :queue_id, :reference_item_id, :owner_member_id,
    :department_id, :title, 'open', 1, :created_by_member_id, UTC_TIMESTAMP(3), UTC_TIMESTAMP(3)
)
SQL);
        $statement->execute([
            'tenant_id' => $context->tenantId,
            'project_id' => $command->projectId,
            'queue_id' => $command->queueId,
            'reference_item_id' => $command->referenceItemId,
            'owner_member_id' => $context->memberId,
            'department_id' => $command->departmentId,
            'title' => $command->title,
            'created_by_member_id' => $context->memberId,
        ]);

        return (string) $this->pdo->lastInsertId();
    }

    public function bulkWrite(): never
    {
        throw new ModuleException('AUTHZ_BULK_WRITE_DISABLED', 'Ordinary bulk write is disabled in the P0 example.');
    }

    private function contains(TypedResourceTargetCollection $targets, string $resourceKey, string $id): bool
    {
        foreach ($targets->sets as $set) {
            if ($set->targetResourceKey === $resourceKey && in_array($id, $set->targetIds, true)) {
                return true;
            }
        }
        return false;
    }
}
