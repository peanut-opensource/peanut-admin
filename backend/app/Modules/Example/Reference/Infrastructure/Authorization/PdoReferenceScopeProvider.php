<?php

declare(strict_types=1);

namespace PeanutAdmin\App\Modules\Example\Reference\Infrastructure\Authorization;

use PDO;
use PeanutAdmin\App\Modules\Example\Reference\Contracts\ReferenceScope;
use PeanutAdmin\DataPermission\Catalog\ResourceOperation;
use PeanutAdmin\DataPermission\Constraint\AlwaysFalse;
use PeanutAdmin\DataPermission\Constraint\ColumnIn;
use PeanutAdmin\DataPermission\Constraint\ColumnReference;
use PeanutAdmin\DataPermission\Constraint\QueryConstraint;
use PeanutAdmin\DataPermission\Context\AuthorizationContext;
use PeanutAdmin\DataPermission\Decision\AuthorizationDecision;
use PeanutAdmin\DataPermission\Provider\SharedMasterScopeProvider;
use PeanutAdmin\DataPermission\Target\TypedResourceTargetCollection;

final readonly class PdoReferenceScopeProvider implements SharedMasterScopeProvider, ReferenceScope
{
    public function __construct(private PDO $pdo) {}

    public function compileVisiblePredicate(
        AuthorizationContext $context,
        ResourceOperation $operation,
        TypedResourceTargetCollection $targets,
    ): QueryConstraint {
        $ids = $this->allowedIds($context, $targets, $operation->operation === 'use' ? 'use' : 'view');

        return $ids === [] ? new AlwaysFalse() : new ColumnIn(new ColumnReference('id'), $ids);
    }

    public function assertUsageAllowed(
        AuthorizationContext $context,
        ResourceOperation $operation,
        string $resourceId,
        TypedResourceTargetCollection $targets,
    ): AuthorizationDecision {
        return $this->canUse($context, $resourceId, $targets)
            ? AuthorizationDecision::allow()
            : AuthorizationDecision::deny('AUTHZ_SHARED_MASTER_SCOPE_DENIED');
    }

    public function canUse(
        AuthorizationContext $context,
        string $referenceItemId,
        TypedResourceTargetCollection $targets,
    ): bool {
        return in_array($referenceItemId, $this->allowedIds($context, $targets, 'use'), true);
    }

    /** @return list<string> */
    public function allowedIds(
        AuthorizationContext $context,
        TypedResourceTargetCollection $targets,
        string $capability,
    ): array {
        $clauses = [
            "scope.scope_kind = 'all_tenants'",
            "(scope.scope_kind = 'tenant' AND scope.target_tenant_id = ?)",
        ];
        $parameters = [$capability, $context->tenant->tenantId];
        foreach ($targets->sets as $targetSet) {
            foreach ($targetSet->targetIds as $targetId) {
                $clauses[] = "(scope.scope_kind = 'typed_target' AND scope.target_tenant_id = ? AND scope.target_resource_key = ? AND scope.target_id = ?)";
                $parameters[] = $context->tenant->tenantId;
                $parameters[] = $targetSet->targetResourceKey;
                $parameters[] = $targetId;
            }
        }
        $statement = $this->pdo->prepare(sprintf(<<<'SQL'
SELECT DISTINCT CAST(item.id AS CHAR) AS id
FROM pa_example_reference_item item
INNER JOIN pa_example_reference_scope scope ON scope.reference_item_id = item.id
WHERE item.status = 'active' AND scope.status = 'active' AND scope.capability = ?
  AND (%s)
ORDER BY id
SQL, implode(' OR ', $clauses)));
        $statement->execute($parameters);

        return array_values(array_map('strval', $statement->fetchAll(PDO::FETCH_COLUMN)));
    }
}
