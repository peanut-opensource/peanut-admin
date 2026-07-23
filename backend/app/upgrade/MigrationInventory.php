<?php

declare(strict_types=1);

namespace PeanutAdmin\App\upgrade;

final readonly class MigrationInventory
{
    /** @var list<array{owner: string, key: string, checksum: string}> */
    public array $entries;

    /** @param list<array{owner: string, key: string, checksum: string}> $entries */
    public function __construct(array $entries)
    {
        $normalized = [];
        foreach ($entries as $entry) {
            $owner = $entry['owner'] ?? '';
            $key = $entry['key'] ?? '';
            $checksum = $entry['checksum'] ?? '';
            if (!is_string($owner) || preg_match('/^(?:kernel|data-permission|module:[a-z0-9][a-z0-9.-]*)$/D', $owner) !== 1
                || !is_string($key) || preg_match('/^[a-zA-Z0-9][a-zA-Z0-9_.:-]*$/D', $key) !== 1
                || !is_string($checksum) || preg_match('/^[a-f0-9]{64}$/D', $checksum) !== 1) {
                throw new UpgradeFailure('UPGRADE_RELEASE_MANIFEST_INVALID');
            }
            $identity = $owner . ':' . $key;
            if (isset($normalized[$identity])) {
                throw new UpgradeFailure('UPGRADE_RELEASE_MANIFEST_INVALID');
            }
            $normalized[$identity] = ['owner' => $owner, 'key' => $key, 'checksum' => $checksum];
        }
        ksort($normalized, SORT_STRING);
        $this->entries = array_values($normalized);
    }

    /** @return array<string, string> */
    public function checksums(): array
    {
        $checksums = [];
        foreach ($this->entries as $entry) {
            $checksums[$entry['owner'] . ':' . $entry['key']] = $entry['checksum'];
        }

        return $checksums;
    }

    public function digest(): string
    {
        return hash('sha256', json_encode($this->entries, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES));
    }

    /** @return list<string> */
    public function assertAppendOnlyTo(self $target): array
    {
        $source = $this->checksums();
        $targetChecksums = $target->checksums();
        foreach ($source as $identity => $checksum) {
            if (!array_key_exists($identity, $targetChecksums)) {
                throw new UpgradeFailure('UPGRADE_MIGRATION_MISSING');
            }
            if (!hash_equals($checksum, $targetChecksums[$identity])) {
                throw new UpgradeFailure('UPGRADE_MIGRATION_REWRITTEN');
            }
        }

        return array_values(array_keys(array_diff_key($targetChecksums, $source)));
    }
}
