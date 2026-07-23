<?php

declare(strict_types=1);

namespace PeanutAdmin\App\upgrade;

use DateTimeImmutable;

final readonly class BackupManifest
{
    /** @param array{commit: string, tree: string} $source */
    private function __construct(
        public string $backupId,
        public string $environment,
        public array $source,
        public string $artifactSha256,
    ) {}

    /** @param array<string, mixed> $data */
    public static function fromArray(array $data): self
    {
        if (($data['schema_version'] ?? null) !== 1
            || !is_string($data['backup_id'] ?? null)
            || preg_match('/^[a-zA-Z0-9][a-zA-Z0-9._:-]{0,127}$/D', $data['backup_id']) !== 1
            || !is_string($data['environment'] ?? null)
            || preg_match('/^[a-z][a-z0-9_-]{0,31}$/D', $data['environment']) !== 1
            || !is_string($data['artifact_sha256'] ?? null)
            || preg_match('/^[a-f0-9]{64}$/D', $data['artifact_sha256']) !== 1) {
            throw new UpgradeFailure('UPGRADE_BACKUP_MANIFEST_INVALID');
        }
        $source = $data['source'] ?? null;
        if (!is_array($source)
            || !is_string($source['commit'] ?? null) || preg_match('/^[a-f0-9]{40}$/D', $source['commit']) !== 1
            || !is_string($source['tree'] ?? null) || preg_match('/^[a-f0-9]{40}$/D', $source['tree']) !== 1) {
            throw new UpgradeFailure('UPGRADE_BACKUP_MANIFEST_INVALID');
        }
        $timestamps = [];
        foreach (['created_at', 'verified_at', 'restore_tested_at'] as $field) {
            if (!is_string($data[$field] ?? null)) {
                throw new UpgradeFailure('UPGRADE_BACKUP_MANIFEST_INVALID');
            }
            try {
                $timestamps[] = new DateTimeImmutable($data[$field]);
            } catch (\Exception) {
                throw new UpgradeFailure('UPGRADE_BACKUP_MANIFEST_INVALID');
            }
        }
        if ($timestamps[0] > $timestamps[1] || $timestamps[1] > $timestamps[2]) {
            throw new UpgradeFailure('UPGRADE_BACKUP_MANIFEST_INVALID');
        }

        return new self(
            $data['backup_id'],
            $data['environment'],
            ['commit' => $source['commit'], 'tree' => $source['tree']],
            $data['artifact_sha256'],
        );
    }

    public static function fromFile(string $path): self
    {
        $contents = is_readable($path) ? file_get_contents($path) : false;
        if (!is_string($contents)) {
            throw new UpgradeFailure('UPGRADE_BACKUP_MANIFEST_UNREADABLE');
        }
        try {
            $data = json_decode($contents, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException) {
            throw new UpgradeFailure('UPGRADE_BACKUP_MANIFEST_INVALID');
        }
        if (!is_array($data)) {
            throw new UpgradeFailure('UPGRADE_BACKUP_MANIFEST_INVALID');
        }

        return self::fromArray($data);
    }
}
