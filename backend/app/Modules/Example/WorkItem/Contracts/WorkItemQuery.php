<?php

declare(strict_types=1);

namespace PeanutAdmin\App\Modules\Example\WorkItem\Contracts;

use PeanutAdmin\Kernel\Context\AuthorizedOperationContext;

interface WorkItemQuery
{
    public function list(AuthorizedOperationContext $context, int $page = 1, int $pageSize = 20): WorkItemPage;
}
