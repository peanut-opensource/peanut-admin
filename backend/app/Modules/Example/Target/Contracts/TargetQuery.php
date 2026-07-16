<?php

declare(strict_types=1);

namespace PeanutAdmin\App\Modules\Example\Target\Contracts;

interface TargetQuery
{
    public function find(int $tenantId, string $resourceKey, string $id): ?TargetOption;

    /** @return list<TargetOption> */
    public function list(int $tenantId, string $resourceKey): array;
}
