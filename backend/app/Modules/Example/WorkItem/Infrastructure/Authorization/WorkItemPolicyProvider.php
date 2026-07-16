<?php

declare(strict_types=1);

namespace PeanutAdmin\App\Modules\Example\WorkItem\Infrastructure\Authorization;

use PeanutAdmin\DataPermission\Catalog\ResourceOperation;
use PeanutAdmin\DataPermission\Constraint\QueryConstraint;
use PeanutAdmin\DataPermission\Context\AuthorizationContext;
use PeanutAdmin\DataPermission\Policy\EffectivePolicySet;
use PeanutAdmin\DataPermission\Provider\ResourceQueryPolicyProvider;
use PeanutAdmin\DataPermission\Provider\StandardResourcePolicyProvider;
use PeanutAdmin\DataPermission\Target\TypedResourceTargetCollection;

final readonly class WorkItemPolicyProvider implements ResourceQueryPolicyProvider
{
    public function __construct(private StandardResourcePolicyProvider $delegate) {}

    public function tenantConstraint(AuthorizationContext $context, ResourceOperation $operation): QueryConstraint
    {
        return $this->delegate->tenantConstraint($context, $operation);
    }

    public function requestedTargetConstraint(
        AuthorizationContext $context,
        ResourceOperation $operation,
        TypedResourceTargetCollection $targets,
    ): QueryConstraint {
        return $this->delegate->requestedTargetConstraint($context, $operation, $targets);
    }

    public function compilePredicate(
        AuthorizationContext $context,
        ResourceOperation $operation,
        EffectivePolicySet $policies,
    ): QueryConstraint {
        return $this->delegate->compilePredicate($context, $operation, $policies);
    }
}
