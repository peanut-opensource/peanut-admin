<?php

declare(strict_types=1);

namespace PeanutAdmin\App\upgrade;

final class ExecutionReport
{
    /** @param array{modules: list<string>, applied_module_migrations: int} $result
     *  @return array<string, mixed>
     */
    public static function success(UpgradePlan $plan, array $result, bool $preflightOnly = false): array
    {
        return [
            'schema_version' => 1,
            'status' => $preflightOnly ? 'preflight_passed' : 'succeeded',
            'release_id' => $plan->releaseId,
            'environment' => $plan->environment,
            'source' => $plan->source,
            'target' => $plan->target,
            'preflight' => ['status' => 'passed', 'migration_plan' => $plan->migrationPlan],
            'execution' => [
                'performed' => !$preflightOnly,
                'modules' => $result['modules'],
                'applied_module_migrations' => $result['applied_module_migrations'],
            ],
            'modules' => $result['modules'],
            'applied_module_migrations' => $result['applied_module_migrations'],
            'recovery' => self::recovery($plan->backupId, $plan->source),
            'error' => null,
        ];
    }

    /** @param array{commit: string, tree: string} $source
     *  @param array{commit: string, tree: string} $target
     *  @return array<string, mixed>
     */
    public static function failure(
        string $releaseId,
        array $source,
        array $target,
        string $backupId,
        string $errorCode,
        string $environment,
    ): array {
        return [
            'schema_version' => 1,
            'status' => 'failed',
            'release_id' => $releaseId,
            'environment' => $environment,
            'source' => $source,
            'target' => $target,
            'preflight' => ['status' => 'passed'],
            'execution' => ['performed' => true, 'status' => 'failed'],
            'recovery' => self::recovery($backupId, $source),
            'error' => ['code' => $errorCode, 'message' => 'Upgrade did not complete.'],
        ];
    }

    /** @param array{commit: string, tree: string} $source
     *  @return array<string, mixed>
     */
    private static function recovery(string $backupId, array $source): array
    {
        return [
            'required_on_failure' => true,
            'backup_id' => $backupId,
            'source' => $source,
            'automatic_ddl_rollback' => false,
            'operator_action' => 'Restore the verified backup with the matching source release.',
        ];
    }
}
