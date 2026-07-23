<?php

declare(strict_types=1);

namespace PeanutAdmin\App\upgrade;

final class UpgradePreflight
{
    public function run(
        ReleaseManifest $release,
        BackupManifest $backup,
        RepositoryState $repository,
        MigrationInventory $actualTargetMigrations,
        string $environment,
    ): UpgradePlan {
        if (!$repository->clean) {
            throw new UpgradeFailure('UPGRADE_WORKTREE_DIRTY');
        }
        if ($repository->commit !== $release->target['commit'] || $repository->tree !== $release->target['tree']) {
            throw new UpgradeFailure('UPGRADE_TARGET_MISMATCH');
        }
        if ($backup->source !== $release->source) {
            throw new UpgradeFailure('UPGRADE_SOURCE_MISMATCH');
        }
        if ($environment === '' || $backup->environment !== $environment) {
            throw new UpgradeFailure('UPGRADE_ENVIRONMENT_MISMATCH');
        }
        $pending = $release->sourceMigrations->assertAppendOnlyTo($release->targetMigrations);
        if (!hash_equals($release->targetMigrations->digest(), $actualTargetMigrations->digest())) {
            throw new UpgradeFailure('UPGRADE_RELEASE_MANIFEST_MISMATCH');
        }

        return new UpgradePlan(
            $release->releaseId,
            $environment,
            $release->source,
            $release->target,
            $backup->backupId,
            $backup->artifactSha256,
            $backup->createdAt,
            $backup->verifiedAt,
            $backup->restoreTestedAt,
            $release->manifestDigest,
            $backup->manifestDigest,
            $release->releaseRegistrySha256,
            $release->sourceMigrations,
            $release->targetMigrations,
            $pending,
            [
                'source_count' => count($release->sourceMigrations->entries),
                'target_count' => count($release->targetMigrations->entries),
                'pending_count' => count($pending),
                'digest' => $release->targetMigrations->digest(),
            ],
        );
    }
}
