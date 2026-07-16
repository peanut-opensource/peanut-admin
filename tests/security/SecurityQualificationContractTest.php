<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class SecurityQualificationContractTest extends TestCase
{
    private string $root;

    protected function setUp(): void
    {
        $this->root = dirname(__DIR__, 2);
    }

    public function testEveryG07ControlHasExecutableEvidence(): void
    {
        $path = $this->root . '/tests/security/g07-evidence.json';
        self::assertFileExists($path);
        $document = json_decode((string) file_get_contents($path), true, 512, JSON_THROW_ON_ERROR);
        self::assertSame(1, $document['schema_version'] ?? null);

        $expected = [
            ...$this->ids('TEN', 20),
            ...$this->ids('AUTH', 23),
            ...$this->ids('PERM', 39),
            ...$this->ids('SYS', 22),
            ...$this->ids('WEB', 12),
        ];
        $groups = $document['groups'] ?? [];
        self::assertIsArray($groups);
        $actual = [];
        foreach ($groups as $group) {
            self::assertIsArray($group);
            $ids = $group['ids'] ?? null;
            $evidence = $group['evidence'] ?? null;
            self::assertIsArray($ids);
            self::assertIsArray($evidence);
            self::assertNotSame([], $ids);
            self::assertNotSame([], $evidence);
            foreach ($evidence as $reference) {
                self::assertIsString($reference);
                [$relativePath, $symbol] = array_pad(explode('::', $reference, 2), 2, '');
                self::assertNotSame('', $symbol);
                $evidencePath = $this->root . '/' . $relativePath;
                self::assertFileExists($evidencePath, $relativePath);
                self::assertStringContainsString($symbol, (string) file_get_contents($evidencePath), $reference);
            }
            foreach ($ids as $id) {
                self::assertIsString($id);
                self::assertArrayNotHasKey($id, $actual, "Duplicate G-07 control {$id}");
                $actual[$id] = true;
            }
        }

        sort($expected);
        $actualIds = array_keys($actual);
        sort($actualIds);
        self::assertSame($expected, $actualIds);
    }

    public function testSecurityGateFailsWhenAnyTestIsSkipped(): void
    {
        $script = $this->root . '/scripts/test-security';
        self::assertFileExists($script);
        self::assertTrue(is_executable($script), $script);
        $contents = (string) file_get_contents($script);
        self::assertStringContainsString('--log-junit', $contents);
        self::assertStringContainsString('skipped', $contents);
        self::assertFileExists($this->root . '/docs/security/asvs-p0-map.md');
    }

    /** @return list<string> */
    private function ids(string $prefix, int $last): array
    {
        $ids = [];
        for ($number = 1; $number <= $last; ++$number) {
            $ids[] = sprintf('%s-%03d', $prefix, $number);
        }

        return $ids;
    }
}
