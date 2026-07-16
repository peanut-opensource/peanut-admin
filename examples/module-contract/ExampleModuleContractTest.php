<?php

declare(strict_types=1);

namespace PeanutAdmin\Examples\ModuleContract;

use DateTimeImmutable;
use PDO;
use PeanutAdmin\App\Modules\Example\Reference\Database\Schema as ReferenceSchema;
use PeanutAdmin\App\Modules\Example\Reference\Infrastructure\Authorization\PdoReferenceScopeProvider;
use PeanutAdmin\App\Modules\Example\Reference\Infrastructure\Persistence\PdoReferenceQuery;
use PeanutAdmin\App\Modules\Example\Target\Database\Schema as TargetSchema;
use PeanutAdmin\App\Modules\Example\Target\Infrastructure\Authorization\PdoTargetCatalogProvider;
use PeanutAdmin\App\Modules\Example\Target\Infrastructure\Authorization\PdoTargetResolver;
use PeanutAdmin\App\Modules\Example\WorkItem\Application\CreateWorkItem;
use PeanutAdmin\App\Modules\Example\WorkItem\Application\WorkItemCommandService;
use PeanutAdmin\App\Modules\Example\WorkItem\Application\WorkItemPolicyPublisher;
use PeanutAdmin\App\Modules\Example\WorkItem\Database\Schema as WorkItemSchema;
use PeanutAdmin\App\Modules\Example\WorkItem\Infrastructure\Persistence\PdoWorkItemQuery;
use PeanutAdmin\DataPermission\Catalog\ResourceOperation;
use PeanutAdmin\DataPermission\Context\AuthorizationContext;
use PeanutAdmin\DataPermission\Target\TargetCatalogQuery;
use PeanutAdmin\DataPermission\Target\TypedResourceTargetCollection;
use PeanutAdmin\DataPermission\Target\TypedResourceTargetSet;
use PeanutAdmin\Kernel\Auth\TenantContext;
use PeanutAdmin\Kernel\Auth\ValidatedTenantSession;
use PeanutAdmin\Kernel\Context\AuthorizationDecision;
use PeanutAdmin\Kernel\Context\AuthorizedOperationContext;
use PeanutAdmin\Kernel\Context\RequestedTargetSet;
use PeanutAdmin\Kernel\Module\ModuleException;
use PeanutAdmin\Kernel\Persistence\Schema\KernelSchema;
use PHPUnit\Framework\TestCase;

final class ExampleModuleContractTest extends TestCase
{
    private const DATABASE = 'peanut_admin_example_module_test';

    private PDO $admin;
    private PDO $pdo;

    protected function setUp(): void
    {
        if (getenv('PEANUT_INTEGRATION') !== '1') {
            self::markTestSkipped('Run with PEANUT_INTEGRATION=1.');
        }
        $this->admin = $this->connect();
        $this->admin->exec('DROP DATABASE IF EXISTS `' . self::DATABASE . '`');
        $this->admin->exec('CREATE DATABASE `' . self::DATABASE . '` CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci');
        $this->pdo = $this->connect(self::DATABASE);
        foreach (['pa_account', 'pa_tenant', 'pa_department', 'pa_tenant_member'] as $table) {
            $this->pdo->exec(KernelSchema::createSql($table));
        }
        $this->pdo->exec(TargetSchema::createProject());
        $this->pdo->exec(TargetSchema::createQueue());
        $this->pdo->exec(ReferenceSchema::createItem());
        $this->pdo->exec(ReferenceSchema::createScope());
        foreach (WorkItemSchema::createStatements() as $statement) {
            $this->pdo->exec($statement);
        }
        $this->seed();
    }

    protected function tearDown(): void
    {
        if (isset($this->admin)) {
            $this->admin->exec('DROP DATABASE IF EXISTS `' . self::DATABASE . '`');
        }
    }

    public function testTypedTargetsUnifiedReferenceAndWorkItemContracts(): void
    {
        $tenant = $this->tenantContext();
        $resolver = new PdoTargetResolver($this->pdo);
        $project = $resolver->resolveAndValidate($tenant, new TypedResourceTargetSet('example.project', ['1']));
        $queue = $resolver->resolveAndValidate($tenant, new TypedResourceTargetSet('example.queue', ['1'], 'related'));
        self::assertSame('example.project', $project->targets->sets[0]->targetResourceKey);
        self::assertSame('example.queue', $queue->targets->sets[0]->targetResourceKey);
        $catalog = new PdoTargetCatalogProvider($this->pdo, ['example.project' => ['1', '2']]);
        $options = $catalog->searchAllowedTargets(
            $authorization = new AuthorizationContext($tenant, 1),
            new ResourceOperation(1, 1, 'example.project', 'example.target', 'project', 'tenant_owned', 'select', 'read', 'many_readable', 'any', [], []),
            new TargetCatalogQuery('example.project', '', 1, 20),
        );
        self::assertSame(['1', '2'], array_column($options->items, 'id'));

        $scope = new PdoReferenceScopeProvider($this->pdo);
        $query = new PdoReferenceQuery($this->pdo, $scope);
        $projectA = new TypedResourceTargetCollection([new TypedResourceTargetSet('example.project', ['1'])]);
        $projectB = new TypedResourceTargetCollection([new TypedResourceTargetSet('example.project', ['2'])]);
        self::assertSame(['private-a', 'public-ref'], array_map(
            static fn($item): string => $item->code,
            $query->candidates($authorization, $projectA, 'use'),
        ));
        self::assertSame(['public-ref'], array_map(
            static fn($item): string => $item->code,
            $query->candidates($authorization, $projectB, 'use'),
        ));

        $createContext = $this->operationContext('create', [
            new RequestedTargetSet('example.project', ['1']),
            new RequestedTargetSet('example.queue', ['1'], 'related'),
        ]);
        $workItemId = (new WorkItemCommandService($this->pdo, $scope))->create(
            $createContext,
            new CreateWorkItem('1', '1', '2', 'Fixture work item', 1),
        );
        self::assertSame('1', $workItemId);

        $listContext = $this->operationContext('list', [new RequestedTargetSet('example.project', ['1', '2'])]);
        $page = (new PdoWorkItemQuery($this->pdo))->list($listContext);
        self::assertCount(1, $page->items);
        self::assertSame(1, $page->total);

        $policyId = (new WorkItemPolicyPublisher($this->pdo))->publish(
            $this->operationContext('policy-publish', [new RequestedTargetSet('example.project', ['1', '2'])]),
            'Fixture policy',
            ['status' => ['open']],
        );
        self::assertSame('1', $policyId);
        self::assertSame(2, (int) $this->pdo->query('SELECT COUNT(*) FROM pa_example_work_item_policy_publication')->fetchColumn());
    }

    public function testCategoryConfusionPrivateScopeAndBulkWriteFailClosed(): void
    {
        $tenant = $this->tenantContext();
        $resolver = new PdoTargetResolver($this->pdo);
        try {
            $resolver->resolveAndValidate($tenant, new TypedResourceTargetSet('example.queue', ['2']));
            self::fail('Project ID must not be interpreted as Queue ID.');
        } catch (ModuleException $exception) {
            self::assertSame('AUTHZ_TARGET_NOT_FOUND', $exception->errorCode);
        }

        $service = new WorkItemCommandService($this->pdo, new PdoReferenceScopeProvider($this->pdo));
        try {
            $service->create(
                $this->operationContext('create', [new RequestedTargetSet('example.project', ['2'])]),
                new CreateWorkItem('2', null, '2', 'Denied reference', 1),
            );
            self::fail('Project B must not use Project A private reference.');
        } catch (ModuleException $exception) {
            self::assertSame('AUTHZ_SHARED_MASTER_SCOPE_DENIED', $exception->errorCode);
        }

        $this->expectException(ModuleException::class);
        $service->bulkWrite();
    }

    private function seed(): void
    {
        $now = '2026-07-16 12:00:00.000';
        $this->pdo->exec("INSERT INTO pa_account (id, display_name, status, security_revision, created_at, updated_at) VALUES (1, 'Fixture', 'active', 1, '{$now}', '{$now}')");
        $this->pdo->exec("INSERT INTO pa_tenant (id, code, name, display_name, status, locale, timezone, security_revision, authorization_revision, revision, activated_at, created_at, updated_at) VALUES (1, 'alpha', 'Alpha', 'Alpha', 'active', 'zh-CN', 'Asia/Shanghai', 1, 1, 1, '{$now}', '{$now}', '{$now}')");
        $this->pdo->exec("INSERT INTO pa_department (id, tenant_id, parent_id, code, name, status, revision, created_at, updated_at) VALUES (1, 1, NULL, 'root', 'Root', 'active', 1, '{$now}', '{$now}')");
        $this->pdo->exec("INSERT INTO pa_tenant_member (id, tenant_id, account_id, display_name, member_type, primary_department_id, status, security_revision, authorization_revision, joined_at, created_at, updated_at) VALUES (1, 1, 1, 'Fixture', 'internal', 1, 'active', 1, 1, '{$now}', '{$now}', '{$now}')");
        $this->pdo->exec("INSERT INTO pa_example_project (id, tenant_id, code, name, status, revision, created_at, updated_at) VALUES (1,1,'A','Project A','active',1,'{$now}','{$now}'),(2,1,'B','Project B','active',1,'{$now}','{$now}'),(3,1,'C','Project C','active',1,'{$now}','{$now}')");
        $this->pdo->exec("INSERT INTO pa_example_queue (id, tenant_id, code, name, status, revision, created_at, updated_at) VALUES (1,1,'A','Queue A','active',1,'{$now}','{$now}')");
        $this->pdo->exec("INSERT INTO pa_example_reference_item (id, owner_type, owner_tenant_id, code, name, status, revision, created_at, updated_at) VALUES (1,'deployment',NULL,'public-ref','Public Reference','active',1,'{$now}','{$now}'),(2,'tenant',1,'private-a','Private A','active',1,'{$now}','{$now}')");
        $this->pdo->exec("INSERT INTO pa_example_reference_scope (reference_item_id, scope_kind, target_tenant_id, target_resource_key, target_id, capability, status, revision) VALUES (1,'all_tenants',NULL,NULL,NULL,'use','active',1),(2,'typed_target',1,'example.project','1','use','active',1)");
    }

    private function tenantContext(): TenantContext
    {
        return TenantContext::fromValidatedSession(new ValidatedTenantSession(
            1,
            'fixture-session',
            1,
            1,
            1,
            'admin-web',
            new DateTimeImmutable('2026-07-16T12:00:00Z'),
            1,
        ), 'fixture-request');
    }

    /** @param list<RequestedTargetSet> $targets */
    private function operationContext(string $operation, array $targets): AuthorizedOperationContext
    {
        return AuthorizedOperationContext::fromDecision(AuthorizationDecision::allow(
            $this->tenantContext(),
            'example.work-item',
            $operation,
            $targets,
            hash('sha256', $operation),
        ));
    }

    private function connect(?string $database = null): PDO
    {
        $dsn = 'mysql:host=127.0.0.1;port=' . (getenv('MYSQL_PORT') ?: '3306')
            . ($database === null ? '' : ";dbname={$database}") . ';charset=utf8mb4';
        return new PDO($dsn, 'root', getenv('MYSQL_ROOT_PASSWORD') ?: 'peanut_admin_root_dev', [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);
    }
}
