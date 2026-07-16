<?php

declare(strict_types=1);

namespace PeanutAdmin\App\Modules\Example\Target\Infrastructure\Persistence;

use PDO;
use PeanutAdmin\App\Modules\Example\Target\Contracts\TargetOption;
use PeanutAdmin\App\Modules\Example\Target\Contracts\TargetQuery;
use RuntimeException;

final readonly class PdoTargetQuery implements TargetQuery
{
    public function __construct(private PDO $pdo) {}

    public function find(int $tenantId, string $resourceKey, string $id): ?TargetOption
    {
        $statement = $this->pdo->prepare(sprintf(
            'SELECT id, code, name FROM %s WHERE tenant_id = :tenant_id AND id = :id AND status = :status',
            $this->table($resourceKey),
        ));
        $statement->execute(['tenant_id' => $tenantId, 'id' => $id, 'status' => 'active']);
        $row = $statement->fetch(PDO::FETCH_ASSOC);

        return is_array($row) ? $this->option($resourceKey, $row) : null;
    }

    public function list(int $tenantId, string $resourceKey): array
    {
        $statement = $this->pdo->prepare(sprintf(
            'SELECT id, code, name FROM %s WHERE tenant_id = :tenant_id AND status = :status ORDER BY code, id',
            $this->table($resourceKey),
        ));
        $statement->execute(['tenant_id' => $tenantId, 'status' => 'active']);

        return array_values(array_map(
            fn(array $row): TargetOption => $this->option($resourceKey, $row),
            $statement->fetchAll(PDO::FETCH_ASSOC),
        ));
    }

    private function table(string $resourceKey): string
    {
        return match ($resourceKey) {
            'example.project' => 'pa_example_project',
            'example.queue' => 'pa_example_queue',
            default => throw new RuntimeException('Unknown example target resource key.'),
        };
    }

    /** @param array<string, mixed> $row */
    private function option(string $resourceKey, array $row): TargetOption
    {
        return new TargetOption($resourceKey, (string) $row['id'], (string) $row['code'], (string) $row['name']);
    }
}
