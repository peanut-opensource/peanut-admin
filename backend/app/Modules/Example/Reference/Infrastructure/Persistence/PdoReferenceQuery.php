<?php

declare(strict_types=1);

namespace PeanutAdmin\App\Modules\Example\Reference\Infrastructure\Persistence;

use PDO;
use PeanutAdmin\App\Modules\Example\Reference\Contracts\ReferenceOption;
use PeanutAdmin\App\Modules\Example\Reference\Contracts\ReferenceQuery;
use PeanutAdmin\App\Modules\Example\Reference\Infrastructure\Authorization\PdoReferenceScopeProvider;
use PeanutAdmin\DataPermission\Context\AuthorizationContext;
use PeanutAdmin\DataPermission\Target\TypedResourceTargetCollection;

final readonly class PdoReferenceQuery implements ReferenceQuery
{
    public function __construct(
        private PDO $pdo,
        private PdoReferenceScopeProvider $scope,
    ) {}

    public function candidates(
        AuthorizationContext $context,
        TypedResourceTargetCollection $targets,
        string $capability,
    ): array {
        $ids = $this->scope->allowedIds($context, $targets, $capability);
        if ($ids === []) {
            return [];
        }
        $options = [];
        foreach (array_chunk($ids, 500) as $chunk) {
            $placeholders = implode(', ', array_fill(0, count($chunk), '?'));
            $statement = $this->pdo->prepare(<<<SQL
SELECT id, code, name, owner_type, owner_tenant_id
FROM pa_example_reference_item
WHERE status = 'active' AND CAST(id AS CHAR) IN ({$placeholders})
ORDER BY code, id
SQL);
            $statement->execute($chunk);
            foreach ($statement->fetchAll(PDO::FETCH_ASSOC) as $row) {
                $options[] = new ReferenceOption(
                    (string) $row['id'],
                    (string) $row['code'],
                    (string) $row['name'],
                    (string) $row['owner_type'],
                    $row['owner_tenant_id'] === null ? null : (int) $row['owner_tenant_id'],
                );
            }
        }
        usort($options, static fn(ReferenceOption $left, ReferenceOption $right): int => [
            $left->code,
            $left->id,
        ] <=> [
            $right->code,
            $right->id,
        ]);

        return $options;
    }
}
