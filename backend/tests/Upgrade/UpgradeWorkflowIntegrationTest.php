<?php

declare(strict_types=1);

namespace PeanutAdmin\App\Tests\Upgrade;

use PDO;
use PeanutAdmin\App\command\UpgradeWorkflow;
use PeanutAdmin\Kernel\Module\ModuleException;
use Phinx\Config\Config;
use Phinx\Migration\Manager;
use PHPUnit\Framework\TestCase;
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

        $this->admin = $this->connect();
        $this->admin->exec('DROP DATABASE IF EXISTS `' . self::DATABASE . '`');
        $this->admin->exec(
            'CREATE DATABASE `' . self::DATABASE
            . '` CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci',
        );
        $this->database = $this->connect(self::DATABASE);
    }

    protected function tearDown(): void
    {
        if (isset($this->admin)) {
            $this->admin->exec('DROP DATABASE IF EXISTS `' . self::DATABASE . '`');
        }
    }

    public function testUpgradeRunsKernelDataAndModulesInDependencyOrderAndIsIdempotent(): void
    {
        $workflow = new UpgradeWorkflow(dirname(__DIR__, 3), $this->database);

        $first = $workflow->run();
        $second = $workflow->run();

        self::assertSame([
            'example.target',
            'example.reference',
            'example.work-item',
        ], $first['modules']);
        self::assertSame(3, $first['applied_module_migrations']);
        self::assertSame(0, $second['applied_module_migrations']);
        self::assertSame(3, $this->scalar("SELECT COUNT(*) FROM pa_module_installation WHERE status = 'active'"));
        self::assertSame(3, $this->scalar("SELECT COUNT(*) FROM pa_module_migration WHERE status = 'applied'"));
        self::assertSame(50, $this->scalar("SELECT COUNT(*) FROM pa_permission WHERE status = 'active'"));
        self::assertSame(4, $this->scalar("SELECT COUNT(*) FROM pa_protected_resource WHERE status = 'active'"));
        self::assertSame(2, $this->scalar("SELECT COUNT(*) FROM pa_target_type WHERE status = 'active'"));
        self::assertSame(13, $this->scalar("SELECT COUNT(*) FROM pa_resource_operation WHERE status = 'active'"));
        self::assertSame(17, $this->scalar("SELECT COUNT(*) FROM pa_resource_operation_target_type WHERE status = 'active'"));
        self::assertSame(6, $this->scalar("SELECT COUNT(*) FROM pa_data_condition_definition WHERE status = 'active'"));
        self::assertSame(40, $this->scalar("SELECT COUNT(*) FROM pa_resource_operation_condition WHERE status = 'active'"));
        self::assertSame(1, $this->scalar(
            "SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = '"
            . self::DATABASE . "' AND table_name = 'pa_data_permission_policy'",
        ));
    }

    public function testAppliedMigrationChecksumDriftStopsBeforeFurtherChanges(): void
    {
        $workflow = new UpgradeWorkflow(dirname(__DIR__, 3), $this->database);
        $workflow->run();
        $this->database->exec(
            "UPDATE pa_module_migration SET checksum = REPEAT('0', 64)"
            . " WHERE module_key = 'example.target'",
        );

        try {
            $workflow->run();
        } catch (ModuleException $exception) {
            self::assertSame('MODULE_MIGRATION_CHECKSUM_MISMATCH', $exception->errorCode);
            self::assertSame(3, $this->scalar(
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

        $result = (new UpgradeWorkflow($root, $this->database))->run();

        self::assertSame(3, $result['applied_module_migrations']);
        self::assertSame(37, $this->scalar('SELECT COUNT(*) FROM pa_kernel_migration'));
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
            (new UpgradeWorkflow(dirname(__DIR__, 3), $this->connect(self::DATABASE)))->run();
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

    private function connect(?string $database = null): PDO
    {
        return new PDO(
            sprintf(
                'mysql:host=127.0.0.1;port=%d%s;charset=utf8mb4',
                (int) (getenv('MYSQL_PORT') ?: 3306),
                $database === null ? '' : ';dbname=' . $database,
            ),
            'root',
            getenv('MYSQL_ROOT_PASSWORD') ?: 'peanut_admin_root_dev',
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION],
        );
    }

    private function scalar(string $sql): int
    {
        $statement = $this->database->query($sql);
        self::assertNotFalse($statement);

        return (int) $statement->fetchColumn();
    }
}
