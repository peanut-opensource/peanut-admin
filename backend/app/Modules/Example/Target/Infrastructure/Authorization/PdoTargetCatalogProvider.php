<?php

declare(strict_types=1);

namespace PeanutAdmin\App\Modules\Example\Target\Infrastructure\Authorization;

use PDO;
use PeanutAdmin\App\Modules\Example\Target\Contracts\TargetIdSet;
use PeanutAdmin\DataPermission\Catalog\ResourceOperation;
use PeanutAdmin\DataPermission\Context\AuthorizationContext;
use PeanutAdmin\DataPermission\Target\ResourceTargetCatalogProvider;
use PeanutAdmin\DataPermission\Target\TargetCatalogQuery;
use PeanutAdmin\DataPermission\Target\TargetOptionPage;
use PeanutAdmin\Kernel\Module\ModuleException;

final readonly class PdoTargetCatalogProvider implements ResourceTargetCatalogProvider
{
    /** @param array<string, list<string>> $allowedTargetIds */
    public function __construct(private PDO $pdo, private array $allowedTargetIds) {}

    public function searchAllowedTargets(
        AuthorizationContext $context,
        ResourceOperation $operation,
        TargetCatalogQuery $query,
    ): TargetOptionPage {
        $table = match ($query->targetResourceKey) {
            'example.project' => 'pa_example_project',
            'example.queue' => 'pa_example_queue',
            default => throw new ModuleException('AUTHZ_TARGET_TYPE_MISMATCH', 'Unknown target catalog type.'),
        };
        $ids = $this->allowedTargetIds[$query->targetResourceKey] ?? [];
        if ($ids === []) {
            return new TargetOptionPage([], 0);
        }
        $allowed = TargetIdSet::fromStrings($ids);
        $search = '%' . $query->search . '%';
        $baseParameters = [$allowed->json(), $context->tenant->tenantId, $search, $search];
        $count = $this->pdo->prepare(
            <<<SQL
SELECT COUNT(*)
FROM JSON_TABLE(?, '$[*]' COLUMNS (target_id BIGINT UNSIGNED PATH '$')) allowed
INNER JOIN {$table} target ON target.id = allowed.target_id
WHERE target.tenant_id = ? AND target.status = 'active'
  AND (target.code LIKE ? OR target.name LIKE ?)
SQL,
        );
        $count->execute($baseParameters);
        $pageSize = min(100, max(1, $query->pageSize));
        $offset = max(0, ($query->page - 1) * $pageSize);
        $list = $this->pdo->prepare(
            <<<SQL
SELECT target.id, target.name
FROM JSON_TABLE(?, '$[*]' COLUMNS (target_id BIGINT UNSIGNED PATH '$')) allowed
INNER JOIN {$table} target ON target.id = allowed.target_id
WHERE target.tenant_id = ? AND target.status = 'active'
  AND (target.code LIKE ? OR target.name LIKE ?)
ORDER BY target.code, target.id
LIMIT {$pageSize} OFFSET {$offset}
SQL,
        );
        $list->execute($baseParameters);

        return new TargetOptionPage(
            array_values(array_map(
                static fn(array $row): array => ['id' => (string) $row['id'], 'label' => (string) $row['name']],
                $list->fetchAll(PDO::FETCH_ASSOC),
            )),
            (int) $count->fetchColumn(),
        );
    }
}
