<?php

declare(strict_types=1);

namespace PeanutAdmin\App\Modules\Example\WorkItem;

use PDO;
use PeanutAdmin\App\Modules\Example\WorkItem\Infrastructure\Authorization\WorkItemPolicyProvider;
use PeanutAdmin\DataPermission\Constraint\ColumnReference;
use PeanutAdmin\DataPermission\Provider\ConditionProviderRegistry;
use PeanutAdmin\DataPermission\Provider\PdoDepartmentHierarchyProvider;
use PeanutAdmin\DataPermission\Provider\PdoTargetSetMembershipProvider;
use PeanutAdmin\DataPermission\Provider\ProviderColumnMap;
use PeanutAdmin\DataPermission\Provider\StandardResourcePolicyProvider;
use PeanutAdmin\DataPermission\Runtime\DataPermissionModuleProvider;
use PeanutAdmin\DataPermission\Runtime\DataPermissionRuntimeRegistry;
use PeanutAdmin\Kernel\Module\ModuleProvider as ModuleProviderContract;

final class ModuleProvider implements ModuleProviderContract, DataPermissionModuleProvider
{
    public function moduleKey(): string
    {
        return 'example.work-item';
    }

    public function registerDataPermission(DataPermissionRuntimeRegistry $registry, PDO $pdo): void
    {
        $provider = new WorkItemPolicyProvider(new StandardResourcePolicyProvider(
            new ProviderColumnMap(
                new ColumnReference('work_item.tenant_id'),
                new ColumnReference('work_item.owner_member_id'),
                new ColumnReference('work_item.department_id'),
                [
                    'example.project' => new ColumnReference('work_item.project_id'),
                    'example.queue' => new ColumnReference('work_item.queue_id'),
                ],
            ),
            new PdoDepartmentHierarchyProvider($pdo),
            new PdoTargetSetMembershipProvider($pdo),
            new ConditionProviderRegistry(),
        ));
        $registry->registerResourceProvider(WorkItemPolicyProvider::class, $provider);
    }
}
