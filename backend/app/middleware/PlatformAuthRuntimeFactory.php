<?php

declare(strict_types=1);

namespace PeanutAdmin\App\middleware;

use PeanutAdmin\App\database\PdoProvider;
use PeanutAdmin\Kernel\Auth\Persistence\PdoPlatformAuthRepository;
use PeanutAdmin\Kernel\Auth\PlatformAuthService;
use PeanutAdmin\Kernel\Auth\SystemClock;
use PeanutAdmin\Kernel\Auth\TokenIssuer;
use PeanutAdmin\Kernel\Identity\PasswordHasher;
use PeanutAdmin\Kernel\Persistence\Pdo\PdoTransactionManager;
use RuntimeException;

final class PlatformAuthRuntimeFactory
{
    private function __construct() {}

    public static function create(): PlatformAuthService
    {
        $hmacKey = getenv('AUTH_IDENTIFIER_HMAC_KEY');
        if (!is_string($hmacKey) || strlen($hmacKey) < 32) {
            throw new RuntimeException('AUTH_IDENTIFIER_HMAC_KEY must contain at least 32 bytes.');
        }

        $pdo = PdoProvider::get();

        return new PlatformAuthService(
            new PdoTransactionManager($pdo),
            new PdoPlatformAuthRepository($pdo),
            new PasswordHasher(),
            new SystemClock(),
            new TokenIssuer(),
            $hmacKey,
        );
    }
}
