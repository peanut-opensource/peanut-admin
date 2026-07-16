<?php

declare(strict_types=1);

namespace PeanutAdmin\App\Modules\Example\WorkItem\Contracts;

use PeanutAdmin\DataPermission\Target\TypedResourceTargetCollection;
use PeanutAdmin\Kernel\Auth\TenantContext;

interface WorkItemQuery
{
    public function list(
        TenantContext $context,
        TypedResourceTargetCollection $targets,
        int $page = 1,
        int $pageSize = 20,
    ): WorkItemPage;
}
