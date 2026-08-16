<?php

namespace Sado729\ProjectVersion\Services;

use Illuminate\Support\Facades\DB;
use Sado729\ProjectVersion\Models\Information;
use Sado729\ProjectVersion\Models\VersionHistory;
use Sado729\ProjectVersion\Support\Git;

class VersionBumper
{
    public function __construct(private Git $git)
    {
    }

    public function bump(?string $fromRef = null, ?string $toRef = null): string
    {
        [$changedFiles, $newFiles] = $this->collectFileLists($fromRef, $toRef);

        $changedFiles = $this->filterIgnored($changedFiles);
        $newFiles = $this->filterIgnored($newFiles);

        $newModuleCount = $this->countNewModules(
            $newFiles,
            $fromRef !== null && $toRef !== null ? $fromRef : null
        );

        return $this->applyVersion(function (int $major, int $minor, int $patch) use ($changedFiles, $newFiles, $newModuleCount) {
            if ($newModuleCount > 0) {
                $minor += $newModuleCount;
                $patch = 0;
            } else {
                $patch += count(array_unique(array_merge($changedFiles, $newFiles)));
                [$minor, $patch] = $this->applyRollover($minor, $patch);
            }

            return "{$major}.{$minor}.{$patch}";
        });
    }

    public function release(string $type): string
    {
        if (!in_array($type, ['major', 'minor', 'patch'], true)) {
            throw new \InvalidArgumentException("Unknown release type: {$type}");
        }

        return $this->applyVersion(function (int $major, int $minor, int $patch) use ($type) {
            switch ($type) {
                case 'major':
                    $major += 1;
                    $minor = 0;
                    $patch = 0;
                    break;
                case 'minor':
                    $minor += 1;
                    $patch = 0;
                    break;
                case 'patch':
                    $patch += 1;
                    break;
            }

            return "{$major}.{$minor}.{$patch}";
        });
    }

    public function current(): string
    {
        try {
            return Information::query()->find(1)?->version ?? '1.0.0';
        } catch (\Throwable) {
            return '1.0.0';
        }
    }

    /**
     * Compute the next version inside a row-locked transaction so that
     * concurrent bumps (e.g. overlapping git hooks) serialize instead of
     * losing updates. History is recorded after commit — a missing
     * version_histories table must not poison the transaction.
     */
    private function applyVersion(\Closure $compute): string
    {
        [$version, $changed] = DB::transaction(function () use ($compute) {
            $information = $this->loadInformation();
            $version = $compute(...$this->parseVersion($information->version));

            if ($information->version === $version) {
                return [$version, false];
            }

            $information->update(['version' => $version]);

            return [$version, true];
        });

        if ($changed && config('project-version.history_enabled', true)) {
            $this->recordHistory($version);
        }

        return $version;
    }

    private function loadInformation(): Information
    {
        return Information::query()->lockForUpdate()->firstOrCreate(['id' => 1], ['version' => '1.0.0']);
    }

    private function parseVersion(?string $version): array
    {
        $parts = array_pad(explode('.', $version ?: '1.0.0'), 3, 0);

        return [(int) $parts[0], (int) $parts[1], (int) $parts[2]];
    }

    private function collectFileLists(?string $fromRef, ?string $toRef): array
    {
        if ($fromRef !== null && $toRef !== null) {
            $range = "{$fromRef}..{$toRef}";
            $changed = $this->git->run(['diff', '--name-only', '--diff-filter=ACMR', $range]);
            $new = $this->git->run(['diff', '--name-only', '--diff-filter=A', $range]);

            return [$this->splitLines($changed), $this->splitLines($new)];
        }

        $changed = $this->git->run(['diff', '--name-only']);
        $new = $this->git->run(['ls-files', '--others', '--exclude-standard']);

        return [$this->splitLines($changed), $this->splitLines($new)];
    }

    private function splitLines(string $text): array
    {
        $trimmed = trim($text);
        if ($trimmed === '') {
            return [];
        }

        return preg_split('/\R/', $trimmed) ?: [];
    }

    private function filterIgnored(array $files): array
    {
        $patterns = (array) config('project-version.ignore_patterns', []);
        if (!$patterns) {
            return $files;
        }

        return array_values(array_filter($files, function (string $file) use ($patterns) {
            foreach ($patterns as $pattern) {
                // No FNM_PATHNAME: '*' must cross directory separators so
                // 'vendor/*' also matches 'vendor/a/b.php' and '*.md'
                // matches 'docs/notes.md'.
                if (fnmatch($pattern, $file)) {
                    return false;
                }
            }
            return true;
        }));
    }

    private function countNewModules(array $newFiles, ?string $fromRef): int
    {
        $patterns = (array) config('project-version.module_paths', ['app/*']);
        if (!$newFiles || !$patterns) {
            return 0;
        }

        $modules = [];
        foreach ($newFiles as $file) {
            foreach ($patterns as $pattern) {
                $parts = explode('/', $file);
                while (count($parts) > 1) {
                    array_pop($parts);
                    $candidate = implode('/', $parts);
                    if (fnmatch($pattern, $candidate, FNM_PATHNAME)) {
                        $modules[$candidate] = true;
                        break 2;
                    }
                }
            }
        }

        return count(array_filter(
            array_keys($modules),
            fn (string $module) => $this->moduleIsNew($module, $fromRef)
        ));
    }

    /**
     * A directory only counts as a new module when nothing existed under it
     * before this change set — otherwise adding a file to an existing module
     * (e.g. a new controller in app/Http) would wrongly bump minor.
     */
    private function moduleIsNew(string $module, ?string $fromRef): bool
    {
        $output = $fromRef !== null
            ? $this->git->run(['ls-tree', '--name-only', $fromRef, '--', $module])
            : $this->git->run(['ls-files', '--', $module . '/']);

        return trim($output) === '';
    }

    private function applyRollover(int $minor, int $patch): array
    {
        $threshold = config('project-version.patch_rollover_threshold');
        if (is_int($threshold) && $threshold > 0 && $patch >= $threshold) {
            $minor += intdiv($patch, $threshold);
            $patch = $patch % $threshold;
        }

        return [$minor, $patch];
    }

    private function recordHistory(string $version): void
    {
        try {
            VersionHistory::create([
                'version' => $version,
                'commit_hash' => trim($this->git->run(['rev-parse', '--short', 'HEAD'])) ?: null,
                'recorded_at' => date('Y-m-d H:i:s'),
            ]);
        } catch (\Throwable $e) {
            // History is best-effort; absence of the table must not block versioning.
        }
    }
}
