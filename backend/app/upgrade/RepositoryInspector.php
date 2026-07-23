<?php

declare(strict_types=1);

namespace PeanutAdmin\App\upgrade;

final class RepositoryInspector
{
    public function inspectRelease(string $root, ReleaseManifest $release): RepositoryState
    {
        if (file_exists($root . '/.git') || is_link($root . '/.git')) {
            $this->assertCommitTree($root, $release->source);
            $this->assertCommitTree($root, $release->target);

            return $this->inspect($root);
        }

        $this->assertPackagedReleaseRegistry($root, $release);

        return new RepositoryState($release->target['commit'], $release->target['tree'], true);
    }

    public function inspect(string $root): RepositoryState
    {
        return new RepositoryState(
            $this->git($root, ['rev-parse', 'HEAD']),
            $this->git($root, ['rev-parse', 'HEAD^{tree}']),
            $this->git($root, ['status', '--porcelain', '--untracked-files=all']) === '',
        );
    }

    /** @param list<string> $arguments */
    private function git(string $root, array $arguments): string
    {
        $command = ['git', '-C', $root, ...$arguments];
        $pipes = [];
        $process = proc_open($command, [1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes);
        if (!is_resource($process)) {
            throw new UpgradeFailure('UPGRADE_REPOSITORY_STATE_UNAVAILABLE');
        }
        $stdout = stream_get_contents($pipes[1]);
        $stderr = stream_get_contents($pipes[2]);
        fclose($pipes[1]);
        fclose($pipes[2]);
        $exit = proc_close($process);
        if ($exit !== 0 || !is_string($stdout) || !is_string($stderr)) {
            throw new UpgradeFailure('UPGRADE_REPOSITORY_STATE_UNAVAILABLE');
        }

        return trim($stdout);
    }

    /** @param array{commit: string, tree: string} $identity */
    private function assertCommitTree(string $root, array $identity): void
    {
        $commit = $this->git($root, ['rev-parse', '--verify', $identity['commit'] . '^{commit}']);
        $tree = $this->git($root, ['rev-parse', '--verify', $identity['commit'] . '^{tree}']);
        if ($commit !== $identity['commit'] || $tree !== $identity['tree']) {
            throw new UpgradeFailure('UPGRADE_RELEASE_IDENTITY_INVALID');
        }
    }

    private function assertPackagedReleaseRegistry(string $root, ReleaseManifest $release): void
    {
        if ($release->releaseRegistrySha256 === null) {
            throw new UpgradeFailure('UPGRADE_RELEASE_REGISTRY_REQUIRED');
        }
        $path = $root . '/release/release-registry.json';
        $contents = is_readable($path) ? file_get_contents($path) : false;
        if (!is_string($contents)
            || !hash_equals($release->releaseRegistrySha256, hash('sha256', $contents))) {
            throw new UpgradeFailure('UPGRADE_RELEASE_REGISTRY_INVALID');
        }
        try {
            $registry = json_decode($contents, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException) {
            throw new UpgradeFailure('UPGRADE_RELEASE_REGISTRY_INVALID');
        }
        if (!is_array($registry)
            || ($registry['schema_version'] ?? null) !== 1
            || ($registry['release_id'] ?? null) !== $release->releaseId
            || ($registry['source'] ?? null) !== $release->source
            || ($registry['target'] ?? null) !== $release->target
            || ($registry['migration_inventory_sha256'] ?? null) !== $release->targetMigrations->digest()) {
            throw new UpgradeFailure('UPGRADE_RELEASE_REGISTRY_INVALID');
        }
    }
}
