<?php

declare(strict_types=1);

return [
    'roots' => [
        'backend/src/Modules/Example/Greeting',
        'backend/src/Modules/Peanut/Settings',
        'backend/src/Modules/Peanut/ReferenceCodes',
        'backend/src/Modules/Peanut/FileMedia',
    ],
    'frontend_components' => [
        'example.greeting.page',
        'peanut.settings.page',
        'peanut.reference-codes.page',
        'peanut.file-media.page',
    ],
    'registered_client_keys' => [
        'operations-web',
        'reporting-web',
        'platform-web',
    ],
];
