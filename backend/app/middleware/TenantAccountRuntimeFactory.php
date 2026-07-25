<?php

declare(strict_types=1);

namespace PeanutAdmin\App\middleware;

use PDO;
use PeanutAdmin\Kernel\Identity\SelfService\AccountSelfService;

final class TenantAccountRuntimeFactory
{
    private function __construct() {}

    public static function create(): AccountSelfService
    {
        return new AccountSelfService(app(PDO::class));
    }
}
