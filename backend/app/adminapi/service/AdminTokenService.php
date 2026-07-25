<?php
declare(strict_types=1);

namespace app\adminapi\service;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use think\facade\Config;

class AdminTokenService
{
    /**
     * 生成 JWT token
     */
    public static function createToken(int $adminId): string
    {
        $secret  = Config::get('jwt.secret', 'peanut-admin-secret-key');
        $expire  = Config::get('jwt.expire', 7200);
        $payload = [
            'iss'      => 'peanut-admin',
            'iat'      => time(),
            'exp'      => time() + $expire,
            'admin_id' => $adminId,
        ];
        return JWT::encode($payload, $secret, 'HS256');
    }

    /**
     * 解析 JWT token，返回 admin_id，失败返回 false
     */
    public static function parseToken(string $token): int|false
    {
        try {
            $secret  = Config::get('jwt.secret', 'peanut-admin-secret-key');
            $decoded = JWT::decode($token, new Key($secret, 'HS256'));
            return (int) $decoded->admin_id;
        } catch (\Throwable) {
            return false;
        }
    }
}
