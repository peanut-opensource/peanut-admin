<?php

declare(strict_types=1);

namespace PeanutAdmin\App\upgrade;

final readonly class UpgradePlan
{
    /** @param array{commit: string, tree: string} $source
     *  @param array{commit: string, tree: string} $target
     *  @param list<string> $pendingMigrationKeys
     *  @param array{source_count: int, target_count: int, pending_count: int, digest: string} $migrationPlan
     */
    public function __construct(
        public string $releaseId,
        public string $environment,
        public array $source,
        public array $target,
        public string $backupId,
        public string $backupArtifactSha256,
        public string $backupCreatedAt,
        public string $backupVerifiedAt,
        public string $backupRestoreTestedAt,
        public string $releaseManifestDigest,
        public string $backupManifestDigest,
        public ?string $releaseRegistrySha256,
        public MigrationInventory $sourceMigrations,
        public MigrationInventory $targetMigrations,
        public array $pendingMigrationKeys,
        public array $migrationPlan,
    ) {}
}
