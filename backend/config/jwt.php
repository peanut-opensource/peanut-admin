<?php
declare(strict_types=1);

// JWT 配置
return [
    'secret' => env('JWT_SECRET', 'peanut-admin-change-this-in-production'),
    'expire' => env('JWT_EXPIRE', 7200), // 2小时
];
