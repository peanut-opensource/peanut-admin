<?php

declare(strict_types=1);

namespace PeanutAdmin\App\middleware;

use PDO;
use PeanutAdmin\Kernel\Auth\Persistence\PdoPlatformAuthRepository;
use PeanutAdmin\Kernel\Auth\PlatformAuthService;
use PeanutAdmin\Kernel\Auth\SystemClock;
use PeanutAdmin\Kernel\Auth\TokenIssuer;
use PeanutAdmin\Kernel\Identity\PasswordHasher;
use PeanutAdmin\Kernel\Persistence\Pdo\PdoTransactionManager;

final class PlatformAuthRuntimeFactory
{
    private function __construct() {}

    public static function create(): PlatformAuthService
    {
        $pdo = new PDO(
            sprintf(
                'mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4',
                getenv('DB_HOST') ?: '127.0.0.1',
                (int) (getenv('DB_PORT') ?: 3306),
                getenv('DB_DATABASE') ?: 'peanut_admin',
            ),
            getenv('DB_USERNAME') ?: 'peanut_admin',
            getenv('DB_PASSWORD') ?: 'peanut_admin_dev',
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ],
        );

        return new PlatformAuthService(
            new PdoTransactionManager($pdo),
            new PdoPlatformAuthRepository($pdo),
            new PasswordHasher(),
            new SystemClock(),
            new TokenIssuer(),
        );
    }
}
