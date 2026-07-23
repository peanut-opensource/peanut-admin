<?php

declare(strict_types=1);

namespace PeanutAdmin\App\upgrade;

use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

final class TargetMigrationInventory
{
    public function scan(string $root): MigrationInventory
    {
        $entries = [];
        $this->scanDirectory($entries, 'kernel', $root . '/packages/php/kernel/database/migrations');
        $this->scanDirectory($entries, 'data-permission', $root . '/packages/php/data-permission/database/migrations');

        $config = is_readable($root . '/backend/config/modules.php')
            ? require $root . '/backend/config/modules.php'
            : null;
        if (!is_array($config) || !is_array($config['roots'] ?? null)) {
            throw new UpgradeFailure('UPGRADE_TARGET_INVENTORY_UNAVAILABLE');
        }
        foreach ($config['roots'] as $relativeRoot) {
            if (!is_string($relativeRoot)) {
                throw new UpgradeFailure('UPGRADE_TARGET_INVENTORY_UNAVAILABLE');
            }
            $moduleRoot = $root . '/' . ltrim($relativeRoot, '/');
            if (!is_dir($moduleRoot)) {
                throw new UpgradeFailure('UPGRADE_TARGET_INVENTORY_UNAVAILABLE');
            }
            $iterator = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($moduleRoot, RecursiveDirectoryIterator::SKIP_DOTS),
            );
            foreach ($iterator as $file) {
                if (!$file->isFile() || $file->getFilename() !== 'module.json') {
                    continue;
                }
                $manifest = $this->json($file->getPathname());
                $moduleKey = $manifest['key'] ?? null;
                $backend = $manifest['backend'] ?? null;
                $migrations = is_array($backend) ? ($backend['migrations'] ?? null) : null;
                if ($migrations === null) {
                    continue;
                }
                if (!is_string($moduleKey) || !is_string($migrations) || $migrations === '') {
                    throw new UpgradeFailure('UPGRADE_TARGET_INVENTORY_UNAVAILABLE');
                }
                $this->scanDirectory(
                    $entries,
                    'module:' . $moduleKey,
                    $file->getPath() . '/' . ltrim($migrations, '/'),
                );
            }
        }

        return new MigrationInventory($entries);
    }

    /** @param list<array{owner: string, key: string, checksum: string}> $entries */
    private function scanDirectory(array &$entries, string $owner, string $directory): void
    {
        if (!is_dir($directory)) {
            throw new UpgradeFailure('UPGRADE_TARGET_INVENTORY_UNAVAILABLE');
        }
        $files = glob($directory . '/*.php');
        if ($files === false) {
            throw new UpgradeFailure('UPGRADE_TARGET_INVENTORY_UNAVAILABLE');
        }
        sort($files, SORT_STRING);
        foreach ($files as $file) {
            $key = pathinfo($file, PATHINFO_FILENAME);
            if (preg_match('/^\d{14}_[a-z0-9_]+$/D', $key) !== 1) {
                throw new UpgradeFailure('UPGRADE_TARGET_INVENTORY_UNAVAILABLE');
            }
            $checksum = hash_file('sha256', $file);
            if (!is_string($checksum)) {
                throw new UpgradeFailure('UPGRADE_TARGET_INVENTORY_UNAVAILABLE');
            }
            $entries[] = ['owner' => $owner, 'key' => $key, 'checksum' => $checksum];
        }
    }

    /** @return array<string, mixed> */
    private function json(string $path): array
    {
        $contents = file_get_contents($path);
        if (!is_string($contents)) {
            throw new UpgradeFailure('UPGRADE_TARGET_INVENTORY_UNAVAILABLE');
        }
        try {
            $value = json_decode($contents, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException) {
            throw new UpgradeFailure('UPGRADE_TARGET_INVENTORY_UNAVAILABLE');
        }
        if (!is_array($value)) {
            throw new UpgradeFailure('UPGRADE_TARGET_INVENTORY_UNAVAILABLE');
        }

        return $value;
    }
}
