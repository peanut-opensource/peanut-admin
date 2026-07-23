<?php

declare(strict_types=1);

return [
    'roots' => [
        'backend/src/Modules/Example/Greeting',
        'backend/src/Modules/Peanut/Settings',
        'backend/src/Modules/Peanut/ReferenceCodes',
    ],
    'frontend_components' => [
        'example.greeting.page',
        'peanut.settings.page',
        'peanut.reference-codes.page',
    ],
    'registered_client_keys' => [
        'operations-web',
        'reporting-web',
        'platform-web',
    ],
];
