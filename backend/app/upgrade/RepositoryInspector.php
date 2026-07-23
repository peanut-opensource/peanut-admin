<?php

declare(strict_types=1);

namespace PeanutAdmin\App\upgrade;

final class RepositoryInspector
{
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
}
