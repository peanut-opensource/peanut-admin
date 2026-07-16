<?php

declare(strict_types=1);

namespace PeanutAdmin\App\Modules\Example\Target\Infrastructure\Authorization;

use PDO;
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
        $placeholders = implode(', ', array_fill(0, count($ids), '?'));
        $search = '%' . $query->search . '%';
        $baseParameters = [$context->tenant->tenantId, ...$ids, $search, $search];
        $count = $this->pdo->prepare(
            "SELECT COUNT(*) FROM {$table} WHERE tenant_id = ? AND CAST(id AS CHAR) IN ({$placeholders}) AND status = 'active' AND (code LIKE ? OR name LIKE ?)",
        );
        $count->execute($baseParameters);
        $pageSize = min(100, max(1, $query->pageSize));
        $offset = max(0, ($query->page - 1) * $pageSize);
        $list = $this->pdo->prepare(
            "SELECT id, name FROM {$table} WHERE tenant_id = ? AND CAST(id AS CHAR) IN ({$placeholders}) AND status = 'active' AND (code LIKE ? OR name LIKE ?) ORDER BY code, id LIMIT {$pageSize} OFFSET {$offset}",
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
