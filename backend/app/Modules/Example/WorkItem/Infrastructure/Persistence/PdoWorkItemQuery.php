<?php

declare(strict_types=1);

namespace PeanutAdmin\App\Modules\Example\WorkItem\Infrastructure\Persistence;

use PDO;
use PeanutAdmin\App\Modules\Example\WorkItem\Contracts\WorkItemQuery;
use PeanutAdmin\App\Modules\Example\WorkItem\Contracts\WorkItemView;
use PeanutAdmin\Kernel\Context\AuthorizedOperationContext;

final readonly class PdoWorkItemQuery implements WorkItemQuery
{
    public function __construct(private PDO $pdo) {}

    public function list(AuthorizedOperationContext $context): array
    {
        $projectIds = [];
        foreach ($context->targets as $target) {
            if ($target->targetResourceKey === 'example.project') {
                $projectIds = [...$projectIds, ...$target->targetIds];
            }
        }
        if ($projectIds === []) {
            return [];
        }
        $placeholders = implode(', ', array_fill(0, count($projectIds), '?'));
        $statement = $this->pdo->prepare(<<<SQL
SELECT id, tenant_id, project_id, queue_id, reference_item_id, title, status, revision
FROM pa_example_work_item
WHERE tenant_id = ? AND CAST(project_id AS CHAR) IN ({$placeholders})
ORDER BY id
SQL);
        $statement->execute([$context->tenantContext->tenantId, ...$projectIds]);

        return array_values(array_map(
            static fn(array $row): WorkItemView => new WorkItemView(
                (string) $row['id'],
                (int) $row['tenant_id'],
                (string) $row['project_id'],
                $row['queue_id'] === null ? null : (string) $row['queue_id'],
                (string) $row['reference_item_id'],
                (string) $row['title'],
                (string) $row['status'],
                (int) $row['revision'],
            ),
            $statement->fetchAll(PDO::FETCH_ASSOC),
        ));
    }
}
