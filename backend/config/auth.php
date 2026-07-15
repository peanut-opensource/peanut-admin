<?php

declare(strict_types=1);

return [
    'tenant' => [
        'access_lifetime_seconds' => 900,
        'refresh_lifetime_seconds' => 1_209_600,
        'idle_lifetime_seconds' => 28_800,
        'challenge_lifetime_seconds' => 300,
        'refresh_cookie' => '__Host-pa_tenant_refresh',
        'identifier_hmac_key' => getenv('AUTH_IDENTIFIER_HMAC_KEY') ?: null,
    ],
];
