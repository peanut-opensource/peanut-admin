<?php

declare(strict_types=1);

namespace PeanutAdmin\App\Modules\Example\Reference\Contracts;

use PeanutAdmin\DataPermission\Context\AuthorizationContext;
use PeanutAdmin\DataPermission\Target\TypedResourceTargetCollection;

interface ReferenceQuery
{
    /** @return list<ReferenceOption> */
    public function candidates(
        AuthorizationContext $context,
        TypedResourceTargetCollection $targets,
        string $capability,
    ): array;
}
