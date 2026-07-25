<?php

declare(strict_types=1);

namespace PeanutAdmin\App\database;

use PDO;
use RuntimeException;

final class PdoProvider
{
    private static ?PDO $instance = null;

    public static function get(): PDO
    {
        if (self::$instance === null) {
            self::$instance = self::connect();
        }

        return self::$instance;
    }

    public static function reset(): void
    {
        self::$instance = null;
    }

    private static function connect(): PDO
    {
        $host = getenv('DB_HOST') ?: '127.0.0.1';
        $port = (int) (getenv('DB_PORT') ?: 3306);
        $database = getenv('DB_DATABASE') ?: 'peanut_admin';
        $username = getenv('DB_USERNAME') ?: 'peanut_admin';
        $password = getenv('DB_PASSWORD') ?: 'peanut_admin_dev';

        if ($database === '') {
            throw new RuntimeException('DB_DATABASE environment variable is required.');
        }

        return new PDO(
            sprintf('mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4', $host, $port, $database),
            $username,
            $password,
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ],
        );
    }
}
