<?php

declare(strict_types=1);

namespace PeanutAdmin\App\module;

use PeanutAdmin\Kernel\Module\CompiledModuleRegistry;
use PeanutAdmin\Kernel\Module\ManifestDocument;
use PeanutAdmin\Kernel\Module\ModuleException;
use PeanutAdmin\Kernel\Module\ModuleKey;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;

final readonly class ModuleBoundaryChecker
{
    public function __construct(private CompiledModuleRegistry $registry) {}

    public function check(): void
    {
        foreach ($this->registry->modules as $manifest) {
            $this->checkModule($manifest);
        }
    }

    private function checkModule(ManifestDocument $manifest): void
    {
        $moduleKey = $manifest->data['key'] ?? null;
        if (!is_string($moduleKey)) {
            throw new ModuleException('MODULE_MANIFEST_INVALID', 'Module key is required for boundary checks.');
        }
        $namespace = ModuleKey::fromString($moduleKey)->backendNamespace();
        $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator(
            $manifest->root,
            RecursiveDirectoryIterator::SKIP_DOTS,
        ));
        foreach ($iterator as $file) {
            if (!$file instanceof SplFileInfo || !$file->isFile() || $file->getExtension() !== 'php') {
                continue;
            }
            $this->checkPhpFile($file->getPathname(), $moduleKey, $namespace);
        }
    }

    private function checkPhpFile(string $path, string $moduleKey, string $moduleNamespace): void
    {
        $tokens = token_get_all((string) file_get_contents($path));
        foreach ($tokens as $token) {
            if (!is_array($token)) {
                continue;
            }
            [$type, $text] = $token;
            if (in_array($type, [T_NAME_QUALIFIED, T_NAME_FULLY_QUALIFIED], true)) {
                $reference = ltrim($text, '\\');
                if (str_starts_with($reference, 'PeanutAdmin\\App\\Modules\\')
                    && !str_starts_with($reference . '\\', $moduleNamespace)
                    && !str_contains($reference, '\\Contracts\\')) {
                    throw new ModuleException(
                        'MODULE_REGISTRY_CONFLICT',
                        "{$path} imports another module outside Contracts.",
                    );
                }
            }
            if (!in_array($type, [T_CONSTANT_ENCAPSED_STRING, T_ENCAPSED_AND_WHITESPACE], true)) {
                continue;
            }
            foreach ($this->tableCandidates($text) as $table) {
                $owner = $this->registry->ownedTableOwners[$table] ?? null;
                if ($owner !== $moduleKey && !$this->isDeclaredForeignKeyReference($path, $text, $table)) {
                    throw new ModuleException(
                        'MODULE_REGISTRY_CONFLICT',
                        "{$path} references table {$table} owned by " . ($owner ?? 'no registered module') . '.',
                    );
                }
            }
        }
    }

    private function isDeclaredForeignKeyReference(string $path, string $literal, string $table): bool
    {
        return str_contains($path, DIRECTORY_SEPARATOR . 'Database' . DIRECTORY_SEPARATOR)
            && str_contains($literal, "REFERENCES `{$table}`");
    }

    /** @return list<string> */
    private function tableCandidates(string $literal): array
    {
        $candidates = [];
        $offset = 0;
        while (($start = strpos($literal, 'pa_', $offset)) !== false) {
            $end = $start + 3;
            $length = strlen($literal);
            while ($end < $length) {
                $character = $literal[$end];
                if (!(ctype_lower($character) || ctype_digit($character) || $character === '_')) {
                    break;
                }
                ++$end;
            }
            $candidates[] = substr($literal, $start, $end - $start);
            $offset = $end;
        }

        return array_values(array_unique($candidates));
    }
}
