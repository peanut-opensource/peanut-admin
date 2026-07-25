<?php
declare(strict_types=1);

namespace app;

use think\Service;

class AppService extends Service
{
    public function register(): void
    {
        // 绑定 PDO 单例到容器
        $this->app->bind(\PDO::class, function (): \PDO {
            $host     = env('DB_HOST', '127.0.0.1');
            $port     = (int) env('DB_PORT', 3306);
            $database = env('DB_DATABASE', 'peanut_admin');
            $username = env('DB_USERNAME', 'peanut_admin');
            $password = env('DB_PASSWORD', '');

            return new \PDO(
                sprintf('mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4', $host, $port, $database),
                $username,
                $password,
                [
                    \PDO::ATTR_ERRMODE            => \PDO::ERRMODE_EXCEPTION,
                    \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
                    \PDO::ATTR_EMULATE_PREPARES   => false,
                ],
            );
        });
    }

    public function boot(): void {}
}
