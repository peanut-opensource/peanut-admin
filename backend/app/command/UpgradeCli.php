<?php

declare(strict_types=1);

namespace PeanutAdmin\App\command;

use Throwable;

final class UpgradeCli
{
    private function __construct() {}

    public static function run(string $root): int
    {
        try {
            (new InstallEnvironmentChecker($root))->assertReady();
            $result = UpgradeWorkflow::fromEnvironment($root)->run();
            fwrite(STDOUT, json_encode($result, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES) . "\n");

            return 0;
        } catch (Throwable $exception) {
            fwrite(STDERR, 'ERROR: ' . $exception->getMessage() . "\n");

            return 1;
        }
    }
}
