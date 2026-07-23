<?php

declare(strict_types=1);

return [
    'roots' => [
        'backend/src/Modules/Example/Greeting',
        'backend/src/Modules/Peanut/Settings',
    ],
    'frontend_components' => [
        'example.greeting.page',
        'peanut.settings.page',
    ],
    'registered_client_keys' => [
        'operations-web',
        'reporting-web',
        'platform-web',
    ],
];
