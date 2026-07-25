<?php

declare(strict_types=1);

namespace PeanutAdmin\App\middleware;

use PeanutAdmin\App\database\PdoProvider;
use PeanutAdmin\Kernel\Identity\SelfService\AccountSelfService;

final class TenantAccountRuntimeFactory
{
    private function __construct() {}

    public static function create(): AccountSelfService
    {
        return new AccountSelfService(PdoProvider::get());
    }
}
