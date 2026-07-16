<?php

declare(strict_types=1);

namespace PeanutAdmin\App\Modules\Example\WorkItem;

use PeanutAdmin\Kernel\Module\ModuleProvider as ModuleProviderContract;

final class ModuleProvider implements ModuleProviderContract
{
    public function moduleKey(): string
    {
        return 'example.work-item';
    }
}
