<?php

declare(strict_types=1);

return [
    'app_namespace'    => 'app',
    'with_route'       => true,
    'app_debug'        => env('APP_DEBUG', false),
    'default_timezone' => 'Asia/Shanghai',
    'exception_handle' => \app\ExceptionHandle::class,
];

