<?php

declare(strict_types=1);

return [
    'roots' => [
        'backend/src/Modules/Example/Greeting',
        'backend/src/Modules/Peanut/Settings',
        'backend/src/Modules/Peanut/ReferenceCodes',
        'backend/src/Modules/Peanut/FileMedia',
        'backend/src/Modules/Peanut/TaskJob',
        'backend/src/Modules/Peanut/NotificationSms',
    ],
    'frontend_components' => [
        'example.greeting.page',
        'peanut.settings.page',
        'peanut.reference-codes.page',
        'peanut.file-media.page',
        'peanut.task-job.page',
        'peanut.notification-sms.page',
    ],
    'registered_client_keys' => [
        'operations-web',
        'reporting-web',
        'platform-web',
    ],
];
