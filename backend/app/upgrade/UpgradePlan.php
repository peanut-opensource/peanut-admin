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
        public array $pendingMigrationKeys,
        public array $migrationPlan,
    ) {}
}
