<?php

declare(strict_types=1);

namespace PeanutAdmin\App\Modules\Example\WorkItem\Application;

use PDO;
use PeanutAdmin\App\Modules\Example\Reference\Contracts\ReferenceScope;
use PeanutAdmin\DataPermission\Context\AuthorizationContext;
use PeanutAdmin\DataPermission\Target\TypedResourceTargetCollection;
use PeanutAdmin\DataPermission\Target\TypedResourceTargetSet;
use PeanutAdmin\Kernel\Context\AuthorizedOperationContext;
use PeanutAdmin\Kernel\Module\ModuleException;

final readonly class WorkItemCommandService
{
    public function __construct(private PDO $pdo, private ReferenceScope $referenceScope) {}

    public function create(AuthorizedOperationContext $context, CreateWorkItem $command): string
    {
        if ($context->resourceKey !== 'example.work-item' || $context->operation !== 'create') {
            throw new ModuleException('AUTHZ_OPERATION_MISMATCH', 'Create requires an authorized work-item create context.');
        }
        $targets = $this->targets($context);
        if ($targets->countForRole('primary') !== 1 || !$this->contains($targets, 'example.project', $command->projectId)) {
            throw new ModuleException('AUTHZ_TARGET_CARDINALITY_INVALID', 'Create requires exactly the authorized Project.');
        }
        if ($command->queueId !== null && !$this->contains($targets, 'example.queue', $command->queueId)) {
            throw new ModuleException('AUTHZ_TARGET_TYPE_MISMATCH', 'Queue must be explicitly authorized as Queue.');
        }
        $authorizationContext = new AuthorizationContext(
            $context->tenantContext,
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
            'tenant_id' => $context->tenantContext->tenantId,
            'project_id' => $command->projectId,
            'queue_id' => $command->queueId,
            'reference_item_id' => $command->referenceItemId,
            'owner_member_id' => $context->tenantContext->memberId,
            'department_id' => $command->departmentId,
            'title' => $command->title,
            'created_by_member_id' => $context->tenantContext->memberId,
        ]);

        return (string) $this->pdo->lastInsertId();
    }

    public function bulkWrite(): never
    {
        throw new ModuleException('AUTHZ_BULK_WRITE_DISABLED', 'Ordinary bulk write is disabled in the P0 example.');
    }

    private function targets(AuthorizedOperationContext $context): TypedResourceTargetCollection
    {
        return new TypedResourceTargetCollection(array_map(
            static fn($target): TypedResourceTargetSet => new TypedResourceTargetSet(
                $target->targetResourceKey,
                $target->targetIds,
                $target->targetResourceKey === 'example.project' ? 'primary' : 'related',
            ),
            $context->targets,
        ));
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
