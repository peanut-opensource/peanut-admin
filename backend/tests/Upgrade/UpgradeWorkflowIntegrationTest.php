<?php

declare(strict_types=1);

namespace PeanutAdmin\App\Tests\Upgrade;

use PDO;
use PeanutAdmin\App\command\UpgradeWorkflow;
use PeanutAdmin\App\upgrade\BackupManifest;
use PeanutAdmin\App\upgrade\MigrationInventory;
use PeanutAdmin\App\upgrade\ReleaseManifest;
use PeanutAdmin\App\upgrade\RepositoryState;
use PeanutAdmin\App\upgrade\TargetMigrationInventory;
use PeanutAdmin\App\upgrade\UpgradePlan;
use PeanutAdmin\App\upgrade\UpgradePreflight;
use PeanutAdmin\App\upgrade\UpgradeTargetVerifier;
use PeanutAdmin\Kernel\Menu\PdoMenuCatalogRepository;
use PeanutAdmin\Kernel\Module\ModuleException;
use Phinx\Config\Config;
use Phinx\Migration\Manager;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use think\console\Input;
use think\migration\NullOutput;

final class UpgradeWorkflowIntegrationTest extends TestCase
{
    private const DATABASE = 'peanut_admin_ops_upgrade_test';

    private PDO $admin;
    private PDO $database;

    protected function setUp(): void
    {
        if (getenv('PEANUT_INTEGRATION') !== '1') {
            self::markTestSkipped('Run through the D01 integration gate.');
        }
        $this->requiredPort('MYSQL_PORT');
        $this->requiredPort('DB_PORT');

        $this->admin = $this->connect('MYSQL_PORT');
        $this->admin->exec('DROP DATABASE IF EXISTS `' . self::DATABASE . '`');
        $this->admin->exec(
            'CREATE DATABASE `' . self::DATABASE
            . '` CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci',
        );
        $this->database = $this->connect('DB_PORT', self::DATABASE);
    }

    protected function tearDown(): void
    {
        if (isset($this->admin)) {
            $this->admin->exec('DROP DATABASE IF EXISTS `' . self::DATABASE . '`');
        }
    }

    public function testUpgradeRunsKernelDataAndModulesInDependencyOrderAndIsIdempotent(): void
    {
        $root = dirname(__DIR__, 3);
        $workflow = new UpgradeWorkflow($root, $this->database);

        $first = $workflow->installEmptyDatabase();
        $second = $workflow->assertCurrentReleaseNoop();

        self::assertSame([
            'example.target',
            'example.reference',
            'example.work-item',
            'peanut.file-media',
            'peanut.reference-codes',
            'peanut.settings',
        ], $first['modules']);
        self::assertSame(11, $first['applied_module_migrations']);
        self::assertSame(0, $second['applied_module_migrations']);
        self::assertSame(6, $this->scalar("SELECT COUNT(*) FROM pa_module_installation WHERE status = 'active'"));
        self::assertSame(11, $this->scalar("SELECT COUNT(*) FROM pa_module_migration WHERE status = 'applied'"));
        self::assertSame(60, $this->scalar("SELECT COUNT(*) FROM pa_permission WHERE status = 'active'"));
        self::assertSame(1, $this->scalar(<<<'SQL'
SELECT COUNT(*) FROM pa_permission
WHERE `key` = 'core.member.effective-access.read'
  AND module_key = 'core' AND type = 'api' AND risk_level = 'sensitive' AND status = 'active'
SQL));
        self::assertSame(6, $this->scalar("SELECT COUNT(*) FROM pa_protected_resource WHERE status = 'active'"));
        self::assertSame(2, $this->scalar("SELECT COUNT(*) FROM pa_target_type WHERE status = 'active'"));
        self::assertSame(20, $this->scalar("SELECT COUNT(*) FROM pa_resource_operation WHERE status = 'active'"));
        self::assertSame(17, $this->scalar("SELECT COUNT(*) FROM pa_resource_operation_target_type WHERE status = 'active'"));
        self::assertSame(6, $this->scalar("SELECT COUNT(*) FROM pa_data_condition_definition WHERE status = 'active'"));
        self::assertSame(40, $this->scalar("SELECT COUNT(*) FROM pa_resource_operation_condition WHERE status = 'active'"));
        self::assertSame(19, $this->scalar("SELECT COUNT(*) FROM pa_menu_definition WHERE status = 'active'"));
        $menus = new PdoMenuCatalogRepository($this->database);
        self::assertCount(14, $menus->activeDefinitions('tenant'));
        self::assertCount(5, $menus->activeDefinitions('platform'));
        self::assertSame([
            'peanut.settings:20260719030101_create_setting_definitions',
            'peanut.settings:20260719030102_create_setting_deployment_values',
            'peanut.settings:20260719030103_create_setting_tenant_values',
            'peanut.settings:20260719030104_create_setting_target_values',
        ], $this->columnValues(<<<'SQL'
SELECT migration_key FROM pa_module_migration
WHERE module_key = 'peanut.settings' AND status = 'applied'
ORDER BY id
SQL));
        self::assertSame(2, $this->scalar(<<<'SQL'
SELECT COUNT(*) FROM pa_setting_definition
WHERE module_key = 'example.target' AND status = 'active' AND revision = 1
SQL));
        self::assertSame(1, $this->scalar(
            "SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = '"
            . self::DATABASE . "' AND table_name = 'pa_data_permission_policy'",
        ));
    }

    public function testEvidenceBoundUpgradeRejectsTheWrongSourceDatabaseBeforeMutation(): void
    {
        $root = dirname(__DIR__, 3);
        $target = (new TargetMigrationInventory())->scan($root);
        $sourceEntry = array_values(array_filter(
            $target->entries,
            static fn(array $entry): bool => $entry['owner'] === 'kernel'
                && $entry['key'] === '20260716010101_create_pa_account',
        ));
        self::assertCount(1, $sourceEntry);
        $source = new MigrationInventory($sourceEntry);

        try {
            $this->upgradeWorkflow($root)->run($this->plan($root, $source));
        } catch (ModuleException $exception) {
            self::assertSame('UPGRADE_SOURCE_DATABASE_MISMATCH', $exception->errorCode);
            self::assertSame(0, $this->scalar(<<<'SQL'
SELECT COUNT(*) FROM information_schema.tables
WHERE table_schema = 'peanut_admin_ops_upgrade_test' AND table_name = 'pa_account'
SQL));

            return;
        }

        self::fail('An evidence-bound upgrade must reject a database that is not at the declared source.');
    }

    public function testAppliedMigrationChecksumDriftStopsBeforeFurtherChanges(): void
    {
        $workflow = new UpgradeWorkflow(dirname(__DIR__, 3), $this->database);
        $workflow->installEmptyDatabase();
        $this->database->exec(
            "UPDATE pa_module_migration SET checksum = REPEAT('0', 64)"
            . " WHERE module_key = 'example.target'",
        );

        try {
            $workflow->assertCurrentReleaseNoop();
        } catch (ModuleException $exception) {
            self::assertSame('MODULE_MIGRATION_CHECKSUM_MISMATCH', $exception->errorCode);
            self::assertSame(11, $this->scalar(
                "SELECT COUNT(*) FROM pa_module_migration WHERE status = 'applied'",
            ));

            return;
        }

        self::fail('Checksum drift must stop the upgrade.');
    }

    public function testOldKernelSchemaUpgradesToTheCurrentRelease(): void
    {
        $root = dirname(__DIR__, 3);
        $manager = new Manager(new Config([
            'paths' => ['migrations' => $root . '/packages/php/kernel/database/migrations'],
            'environments' => [
                'default_environment' => 'kernel',
                'kernel' => [
                    'adapter' => 'mysql',
                    'connection' => $this->database,
                    'name' => self::DATABASE,
                    'migration_table' => 'pa_kernel_migration',
                ],
            ],
            'version_order' => Config::VERSION_ORDER_CREATION_TIME,
        ]), new Input([]), new NullOutput());
        $manager->migrate('kernel', 20260716010110);

        $target = (new TargetMigrationInventory())->scan($root);
        $source = new MigrationInventory(array_values(array_filter(
            $target->entries,
            static fn(array $entry): bool => $entry['owner'] === 'kernel'
                && strcmp($entry['key'], '20260716010110_create_pa_tenant_member') <= 0,
        )));
        $result = $this->upgradeWorkflow($root)->run($this->plan($root, $source));

        self::assertSame(11, $result['applied_module_migrations']);
        self::assertSame(38, $this->scalar('SELECT COUNT(*) FROM pa_kernel_migration'));
        self::assertSame(1, $this->scalar(
            "SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = '"
            . self::DATABASE . "' AND table_name = 'pa_example_work_item'",
        ));
    }

    public function testConcurrentUpgradeLockFailsBeforeChangingSchema(): void
    {
        $lockKey = 'pa:upgrade:' . substr(hash('sha256', self::DATABASE), 0, 48);
        $lock = $this->database->prepare('SELECT GET_LOCK(:lock_key, 0)');
        $lock->execute(['lock_key' => $lockKey]);
        self::assertSame(1, (int) $lock->fetchColumn());

        try {
            (new UpgradeWorkflow(
                dirname(__DIR__, 3),
                $this->connect('DB_PORT', self::DATABASE),
            ))->installEmptyDatabase();
        } catch (ModuleException $exception) {
            self::assertSame('MODULE_UPGRADE_LOCKED', $exception->errorCode);
            self::assertSame(0, $this->scalar(<<<'SQL'
SELECT COUNT(*) FROM information_schema.tables
WHERE table_schema = 'peanut_admin_ops_upgrade_test' AND table_name = 'pa_account'
SQL));

            return;
        } finally {
            $release = $this->database->prepare('SELECT RELEASE_LOCK(:lock_key)');
            $release->execute(['lock_key' => $lockKey]);
        }

        self::fail('A concurrent upgrade lock must fail closed.');
    }

    public function testCurrentReleaseNoopRejectsDefinitionDigestDrift(): void
    {
        $workflow = new UpgradeWorkflow(dirname(__DIR__, 3), $this->database);
        $workflow->installEmptyDatabase();
        $this->database->exec("UPDATE pa_setting_definition SET definition_digest = REPEAT('0', 64) LIMIT 1");

        try {
            $workflow->assertCurrentReleaseNoop();
        } catch (ModuleException $exception) {
            self::assertSame('UPGRADE_EVIDENCE_REQUIRED', $exception->errorCode);

            return;
        }

        self::fail('Definition drift must not be accepted as a current-release no-op.');
    }

    public function testCurrentReleaseNoopRejectsAnExtraModuleInstallation(): void
    {
        $workflow = new UpgradeWorkflow(dirname(__DIR__, 3), $this->database);
        $workflow->installEmptyDatabase();
        $this->database->exec(<<<'SQL'
INSERT INTO pa_module_installation (
  module_key, installed_version, manifest_schema_version, manifest_digest,
  status, revision, created_at, updated_at
) VALUES ('unexpected.module', '1.0.0', 1, REPEAT('a', 64), 'active', 1, NOW(3), NOW(3))
SQL);

        try {
            $workflow->assertCurrentReleaseNoop();
        } catch (ModuleException $exception) {
            self::assertSame('UPGRADE_EVIDENCE_REQUIRED', $exception->errorCode);

            return;
        }

        self::fail('An extra Module installation must not be accepted as current.');
    }

    private function upgradeWorkflow(string $root): UpgradeWorkflow
    {
        $verifier = new class implements UpgradeTargetVerifier {
            public function verify(string $root, UpgradePlan $plan): void
            {
            }
        };

        return new UpgradeWorkflow($root, $this->database, $verifier);
    }

    private function plan(string $root, MigrationInventory $source): UpgradePlan
    {
        $target = (new TargetMigrationInventory())->scan($root);
        $release = ReleaseManifest::fromArray([
            'schema_version' => 1,
            'release_id' => 'integration-upgrade',
            'source' => ['commit' => str_repeat('1', 40), 'tree' => str_repeat('2', 40)],
            'target' => ['commit' => str_repeat('3', 40), 'tree' => str_repeat('4', 40)],
            'migrations' => ['source' => $source->entries, 'target' => $target->entries],
        ]);
        $backup = BackupManifest::fromArray([
            'schema_version' => 1,
            'backup_id' => 'integration-backup',
            'environment' => 'test',
            'source' => $release->source,
            'artifact_sha256' => str_repeat('5', 64),
            'created_at' => '2026-07-24T00:00:00Z',
            'verified_at' => '2026-07-24T00:01:00Z',
            'restore_tested_at' => '2026-07-24T00:02:00Z',
        ]);

        return (new UpgradePreflight())->run(
            $release,
            $backup,
            new RepositoryState($release->target['commit'], $release->target['tree'], true),
            $target,
            'test',
        );
    }

    private function connect(string $portEnvironment, ?string $database = null): PDO
    {
        return new PDO(
            sprintf(
                'mysql:host=127.0.0.1;port=%d%s;charset=utf8mb4',
                $this->requiredPort($portEnvironment),
                $database === null ? '' : ';dbname=' . $database,
            ),
            'root',
            getenv('MYSQL_ROOT_PASSWORD') ?: 'peanut_admin_root_dev',
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION],
        );
    }

    private function requiredPort(string $name): int
    {
        $value = getenv($name);
        if (!is_string($value) || preg_match('/^[0-9]+$/D', $value) !== 1) {
            throw new RuntimeException("Missing required environment variable: {$name}.");
        }
        $port = (int) $value;
        if ($port < 1 || $port > 65535) {
            throw new RuntimeException("Invalid port in environment variable: {$name}.");
        }

        return $port;
    }

    private function scalar(string $sql): int
    {
        $statement = $this->database->query($sql);
        self::assertNotFalse($statement);

        return (int) $statement->fetchColumn();
    }

    /** @return list<string> */
    private function columnValues(string $sql): array
    {
        $statement = $this->database->query($sql);
        self::assertNotFalse($statement);

        return array_values(array_map('strval', $statement->fetchAll(PDO::FETCH_COLUMN)));
    }
}
