<?php

declare(strict_types=1);

namespace PeanutAdmin\App\Tests\Upgrade;

use PeanutAdmin\App\upgrade\BackupManifest;
use PeanutAdmin\App\upgrade\ExecutionReport;
use PeanutAdmin\App\upgrade\MigrationInventory;
use PeanutAdmin\App\upgrade\ReleaseManifest;
use PeanutAdmin\App\upgrade\RepositoryState;
use PeanutAdmin\App\upgrade\UpgradeFailure;
use PeanutAdmin\App\upgrade\UpgradePreflight;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class UpgradeLifecycleTest extends TestCase
{
    private const SOURCE_COMMIT = '1111111111111111111111111111111111111111';
    private const SOURCE_TREE = '2222222222222222222222222222222222222222';
    private const TARGET_COMMIT = '3333333333333333333333333333333333333333';
    private const TARGET_TREE = '4444444444444444444444444444444444444444';

    public function testPreflightFixesReleaseBackupRepositoryAndAppendOnlyMigrationPlan(): void
    {
        $plan = (new UpgradePreflight())->run(
            $this->release(),
            $this->backup(),
            new RepositoryState(self::TARGET_COMMIT, self::TARGET_TREE, true),
            new MigrationInventory($this->targetMigrations()),
            'staging',
        );

        self::assertSame('release-stage-a', $plan->releaseId);
        self::assertSame('backup-before-stage-a', $plan->backupId);
        self::assertSame(['kernel:002_add_member'], $plan->pendingMigrationKeys);
        self::assertSame(1, $plan->migrationPlan['source_count']);
        self::assertSame(2, $plan->migrationPlan['target_count']);
        self::assertMatchesRegularExpression('/^[a-f0-9]{64}$/D', $plan->migrationPlan['digest']);
    }

    /** @param callable(): array{ReleaseManifest, BackupManifest, RepositoryState, MigrationInventory, string} $case */
    #[DataProvider('failureCases')]
    public function testPreflightFailsClosed(callable $case, string $expectedCode): void
    {
        [$release, $backup, $repository, $inventory, $environment] = $case();

        try {
            (new UpgradePreflight())->run($release, $backup, $repository, $inventory, $environment);
        } catch (UpgradeFailure $failure) {
            self::assertSame($expectedCode, $failure->errorCode);
            self::assertSame('Upgrade preflight failed.', $failure->getMessage());

            return;
        }

        self::fail("Expected {$expectedCode}.");
    }

    /** @return iterable<string, array{callable(): array{ReleaseManifest, BackupManifest, RepositoryState, MigrationInventory, string}, string}> */
    public static function failureCases(): iterable
    {
        yield 'dirty target' => [static function (): array {
            $test = new self('testPreflightFailsClosed');

            return [
                $test->release(),
                $test->backup(),
                new RepositoryState(self::TARGET_COMMIT, self::TARGET_TREE, false),
                new MigrationInventory($test->targetMigrations()),
                'staging',
            ];
        }, 'UPGRADE_WORKTREE_DIRTY'];

        yield 'wrong target commit' => [static function (): array {
            $test = new self('testPreflightFailsClosed');

            return [
                $test->release(),
                $test->backup(),
                new RepositoryState(str_repeat('9', 40), self::TARGET_TREE, true),
                new MigrationInventory($test->targetMigrations()),
                'staging',
            ];
        }, 'UPGRADE_TARGET_MISMATCH'];

        yield 'backup from another source' => [static function (): array {
            $test = new self('testPreflightFailsClosed');

            return [
                $test->release(),
                $test->backup(str_repeat('8', 40)),
                new RepositoryState(self::TARGET_COMMIT, self::TARGET_TREE, true),
                new MigrationInventory($test->targetMigrations()),
                'staging',
            ];
        }, 'UPGRADE_SOURCE_MISMATCH'];

        yield 'backup from another environment' => [static function (): array {
            $test = new self('testPreflightFailsClosed');

            return [
                $test->release(),
                $test->backup(environment: 'production'),
                new RepositoryState(self::TARGET_COMMIT, self::TARGET_TREE, true),
                new MigrationInventory($test->targetMigrations()),
                'staging',
            ];
        }, 'UPGRADE_ENVIRONMENT_MISMATCH'];

        yield 'historical migration missing' => [static function (): array {
            $test = new self('testPreflightFailsClosed');
            $target = [$test->targetMigrations()[1]];

            return [
                $test->release(target: $target),
                $test->backup(),
                new RepositoryState(self::TARGET_COMMIT, self::TARGET_TREE, true),
                new MigrationInventory($target),
                'staging',
            ];
        }, 'UPGRADE_MIGRATION_MISSING'];

        yield 'historical migration rewritten' => [static function (): array {
            $test = new self('testPreflightFailsClosed');
            $target = $test->targetMigrations();
            $target[0]['checksum'] = hash('sha256', 'rewritten');

            return [
                $test->release(target: $target),
                $test->backup(),
                new RepositoryState(self::TARGET_COMMIT, self::TARGET_TREE, true),
                new MigrationInventory($target),
                'staging',
            ];
        }, 'UPGRADE_MIGRATION_REWRITTEN'];

        yield 'release inventory differs from target tree' => [static function (): array {
            $test = new self('testPreflightFailsClosed');

            return [
                $test->release(),
                $test->backup(),
                new RepositoryState(self::TARGET_COMMIT, self::TARGET_TREE, true),
                new MigrationInventory([$test->targetMigrations()[0]]),
                'staging',
            ];
        }, 'UPGRADE_RELEASE_MANIFEST_MISMATCH'];
    }

    public function testFailureReportIsStableAndDoesNotIncludeThrowableDetails(): void
    {
        $report = ExecutionReport::failure(
            releaseId: 'release-stage-a',
            source: ['commit' => self::SOURCE_COMMIT, 'tree' => self::SOURCE_TREE],
            target: ['commit' => self::TARGET_COMMIT, 'tree' => self::TARGET_TREE],
            backupId: 'backup-before-stage-a',
            errorCode: 'MODULE_MIGRATION_FAILED',
            environment: 'staging',
        );
        $json = json_encode($report, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES);

        self::assertSame(1, $report['schema_version']);
        self::assertSame('failed', $report['status']);
        self::assertSame('passed', $report['preflight']['status']);
        self::assertSame(true, $report['execution']['performed']);
        self::assertSame(false, $report['recovery']['automatic_ddl_rollback']);
        self::assertSame('Restore the verified backup with the matching source release.', $report['recovery']['operator_action']);
        self::assertStringNotContainsString('mysql:', $json);
        self::assertStringNotContainsString('/Users/', $json);
        self::assertStringNotContainsString('SELECT ', $json);
    }

    /** @param list<array{owner: string, key: string, checksum: string}>|null $target */
    private function release(?array $target = null): ReleaseManifest
    {
        return ReleaseManifest::fromArray([
            'schema_version' => 1,
            'release_id' => 'release-stage-a',
            'source' => ['commit' => self::SOURCE_COMMIT, 'tree' => self::SOURCE_TREE],
            'target' => ['commit' => self::TARGET_COMMIT, 'tree' => self::TARGET_TREE],
            'migrations' => [
                'source' => [$this->targetMigrations()[0]],
                'target' => $target ?? $this->targetMigrations(),
            ],
        ]);
    }

    private function backup(
        string $sourceCommit = self::SOURCE_COMMIT,
        string $environment = 'staging',
    ): BackupManifest {
        return BackupManifest::fromArray([
            'schema_version' => 1,
            'backup_id' => 'backup-before-stage-a',
            'environment' => $environment,
            'source' => ['commit' => $sourceCommit, 'tree' => self::SOURCE_TREE],
            'artifact_sha256' => hash('sha256', 'backup'),
            'created_at' => '2026-07-24T00:00:00Z',
            'verified_at' => '2026-07-24T00:10:00Z',
            'restore_tested_at' => '2026-07-24T00:20:00Z',
        ]);
    }

    /** @return list<array{owner: string, key: string, checksum: string}> */
    private function targetMigrations(): array
    {
        return [
            ['owner' => 'kernel', 'key' => '001_create_account', 'checksum' => hash('sha256', 'one')],
            ['owner' => 'kernel', 'key' => '002_add_member', 'checksum' => hash('sha256', 'two')],
        ];
    }
}
