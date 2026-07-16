<?php

declare(strict_types=1);

namespace PeanutAdmin\App\Modules\Example\WorkItem\Infrastructure\Persistence;

use PDO;
use PeanutAdmin\App\Modules\Example\WorkItem\Contracts\WorkItemPage;
use PeanutAdmin\App\Modules\Example\WorkItem\Contracts\WorkItemQuery;
use PeanutAdmin\App\Modules\Example\WorkItem\Contracts\WorkItemView;
use PeanutAdmin\DataPermission\Constraint\PdoQueryConstraintCompiler;
use PeanutAdmin\DataPermission\Engine\DataPermissionEngine;
use PeanutAdmin\DataPermission\Target\TypedResourceTargetCollection;
use PeanutAdmin\Kernel\Auth\TenantContext;

final readonly class PdoWorkItemQuery implements WorkItemQuery
{
    public function __construct(private PDO $pdo, private DataPermissionEngine $authorization) {}

    public function list(
        TenantContext $context,
        TypedResourceTargetCollection $targets,
        int $page = 1,
        int $pageSize = 20,
    ): WorkItemPage {
        $page = max(1, $page);
        $pageSize = min(100, max(1, $pageSize));
        $offset = ($page - 1) * $pageSize;
        $constraint = (new PdoQueryConstraintCompiler())->compile(
            $this->authorization->queryConstraint($context, 'example.work-item', 'list', $targets),
        );
        $count = $this->pdo->prepare(<<<SQL
SELECT COUNT(*)
FROM pa_example_work_item work_item
WHERE {$constraint->sql}
SQL);
        $count->execute($constraint->parameters);
        $statement = $this->pdo->prepare(<<<SQL
SELECT work_item.id, work_item.tenant_id, work_item.project_id, work_item.queue_id,
       work_item.reference_item_id, work_item.title, work_item.status, work_item.revision
FROM pa_example_work_item work_item
WHERE {$constraint->sql}
ORDER BY work_item.id
LIMIT {$pageSize} OFFSET {$offset}
SQL);
        $statement->execute($constraint->parameters);

        $items = array_values(array_map(
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

        return new WorkItemPage($items, (int) $count->fetchColumn(), $page, $pageSize);
    }
}
