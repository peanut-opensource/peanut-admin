<?php

declare(strict_types=1);

return array_merge(
    require __DIR__ . '/tenant-auth.php',
    require __DIR__ . '/platform-auth.php',
);
