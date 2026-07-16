<?php

declare(strict_types=1);

namespace PeanutAdmin\App\Modules\Example\Reference;

use PDO;
use PeanutAdmin\App\Modules\Example\Reference\Infrastructure\Authorization\PdoReferenceScopeProvider;
use PeanutAdmin\App\Modules\Example\Reference\Infrastructure\Authorization\ReferencePolicyProvider;
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
        return 'example.reference';
    }

    public function registerDataPermission(DataPermissionRuntimeRegistry $registry, PDO $pdo): void
    {
        $provider = new ReferencePolicyProvider(new StandardResourcePolicyProvider(
            new ProviderColumnMap(
                new ColumnReference('item.owner_tenant_id'),
                null,
                null,
                [],
            ),
            new PdoDepartmentHierarchyProvider($pdo),
            new PdoTargetSetMembershipProvider($pdo),
            new ConditionProviderRegistry(),
        ));
        $scope = new PdoReferenceScopeProvider($pdo);
        $registry->registerResourceProvider(ReferencePolicyProvider::class, $provider);
        $registry->registerSharedMasterProvider('example.reference-item', $scope);
    }
}
