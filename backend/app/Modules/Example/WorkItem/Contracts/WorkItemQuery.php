<?php

declare(strict_types=1);

namespace PeanutAdmin\App\Modules\Example\WorkItem\Contracts;

use PeanutAdmin\Kernel\Context\AuthorizedOperationContext;

interface WorkItemQuery
{
    /** @return list<WorkItemView> */
    public function list(AuthorizedOperationContext $context): array;
}
