<?php

declare(strict_types=1);

namespace PeanutAdmin\App\Tests\Install;

use PDO;
use PeanutAdmin\App\command\InstallProductProfile;
use PeanutAdmin\App\command\InstallWorkflow;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class InstallWorkflowIntegrationTest extends TestCase
{
    private const DATABASE = 'peanut_admin_ops_install_test';

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

    public function testFreshInstallBootstrapsPlatformTenantProfileAndDefaultDepartment(): void
    {
        $root = dirname(__DIR__, 3);
        $profile = InstallProductProfile::load(
            $root . '/profiles/reference-admin.json',
            $root . '/schemas/product-profile.schema.json',
        );
        $workflow = new InstallWorkflow($root, $this->database);

        $result = $workflow->run(
            $profile,
            'owner@example.test',
            'correct horse battery staple',
            'Platform Owner',
            [
                'code' => 'first-tenant',
                'name' => 'First Tenant',
                'owner_email' => 'owner@example.test',
                'owner_name' => 'Tenant Owner',
            ],
        );

        self::assertSame('installed', $result['status']);
        self::assertSame(1, $this->countRows('pa_account'));
        self::assertSame(1, $this->countRows('pa_platform_operator'));
        self::assertSame(1, $this->countRows('pa_tenant'));
        self::assertSame(3, $this->countRows('pa_tenant_module'));
        self::assertSame(1, $this->countRows('pa_department'));
        self::assertSame(1, $this->countRows('pa_role'));
        self::assertArrayNotHasKey('password', $result);

        $repeat = $workflow->run(
            $profile,
            'owner@example.test',
            'correct horse battery staple',
            'Platform Owner',
            null,
            true,
        );
        self::assertSame('already-installed', $repeat['status']);

        $this->expectException(RuntimeException::class);
        $workflow->run(
            $profile,
            'owner@example.test',
            'correct horse battery staple',
            'Platform Owner',
        );
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

    private function countRows(string $table): int
    {
        $statement = $this->database->query("SELECT COUNT(*) FROM `{$table}`");
        self::assertNotFalse($statement);

        return (int) $statement->fetchColumn();
    }
}
