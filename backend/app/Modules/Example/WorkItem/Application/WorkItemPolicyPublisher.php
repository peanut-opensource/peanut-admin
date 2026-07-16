<?php

declare(strict_types=1);

namespace PeanutAdmin\App\Modules\Example\WorkItem\Application;

use PDO;
use PeanutAdmin\DataPermission\Engine\DataPermissionEngine;
use PeanutAdmin\DataPermission\Target\TypedResourceTargetCollection;
use PeanutAdmin\Kernel\Auth\TenantContext;
use PeanutAdmin\Kernel\Module\ModuleException;

final readonly class WorkItemPolicyPublisher
{
    public function __construct(private PDO $pdo, private DataPermissionEngine $authorization) {}

    /** @param array<string, mixed> $config */
    public function publish(
        TenantContext $context,
        TypedResourceTargetCollection $targets,
        string $name,
        array $config,
    ): string {
        $decision = $this->authorization->decideTargets(
            $context,
            'example.work-item',
            'policy-publish',
            $targets,
        );
        if (!$decision->allowed) {
            throw new ModuleException($decision->reasonCode, 'Policy targets are outside the effective data policy.');
        }
        $projects = [];
        foreach ($targets->sets as $target) {
            if ($target->targetResourceKey === 'example.project') {
                $projects = [...$projects, ...$target->targetIds];
            }
        }
        if ($projects === []) {
            throw new ModuleException('AUTHZ_TARGET_CARDINALITY_INVALID', 'Policy publication requires Projects.');
        }
        $this->pdo->beginTransaction();
        try {
            $policy = $this->pdo->prepare(<<<'SQL'
INSERT INTO pa_example_work_item_view_policy (
    tenant_id, name, config_json, status, revision, created_by_member_id, created_at, updated_at
) VALUES (:tenant_id, :name, :config_json, 'active', 1, :member_id, UTC_TIMESTAMP(3), UTC_TIMESTAMP(3))
SQL);
            $policy->execute([
                'tenant_id' => $context->tenantId,
                'name' => $name,
                'config_json' => json_encode($config, JSON_THROW_ON_ERROR),
                'member_id' => $context->memberId,
            ]);
            $policyId = (string) $this->pdo->lastInsertId();
            $publication = $this->pdo->prepare(<<<'SQL'
INSERT INTO pa_example_work_item_policy_publication (
    tenant_id, policy_id, project_id, status, error_code, policy_revision, published_at, updated_at
) VALUES (:tenant_id, :policy_id, :project_id, 'published', NULL, 1, UTC_TIMESTAMP(3), UTC_TIMESTAMP(3))
SQL);
            foreach ($projects as $projectId) {
                $publication->execute([
                    'tenant_id' => $context->tenantId,
                    'policy_id' => $policyId,
                    'project_id' => $projectId,
                ]);
            }
            $this->pdo->commit();

            return $policyId;
        } catch (\Throwable $exception) {
            $this->pdo->rollBack();
            throw $exception;
        }
    }
}
