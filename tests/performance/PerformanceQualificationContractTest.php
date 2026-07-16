<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class PerformanceQualificationContractTest extends TestCase
{
    private string $root;

    protected function setUp(): void
    {
        $this->root = dirname(__DIR__, 2);
    }

    public function testVersionedBaselineCoversSecurityCriticalScenarios(): void
    {
        $path = $this->root . '/tests/performance/p0-baseline.json';
        self::assertFileExists($path);
        $baseline = json_decode((string) file_get_contents($path), true, 512, JSON_THROW_ON_ERROR);
        self::assertSame(1, $baseline['schema_version'] ?? null);
        self::assertSame(1.2, $baseline['maximum_regression_ratio'] ?? null);
        self::assertSame('mysql:8.4.10', $baseline['environment']['database_image'] ?? null);

        $scenarios = $baseline['scenarios'] ?? [];
        self::assertIsArray($scenarios);
        foreach (['typed-targets-10', 'typed-targets-500', 'typed-targets-5000', 'shared-master-scope'] as $scenario) {
            self::assertArrayHasKey($scenario, $scenarios);
            self::assertIsFloat($scenarios[$scenario]['p95_ms'] ?? null);
            self::assertGreaterThan(0, $scenarios[$scenario]['p95_ms']);
        }
    }

    public function testPerformanceGateIsPartOfTheRepositoryGate(): void
    {
        $script = $this->root . '/scripts/test-performance';
        self::assertFileExists($script);
        self::assertTrue(is_executable($script), $script);
        self::assertFileExists($this->root . '/docs/performance/p0-baseline.md');
        self::assertStringContainsString(
            './scripts/test-performance',
            (string) file_get_contents($this->root . '/scripts/check'),
        );
    }
}
