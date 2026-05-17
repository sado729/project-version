<?php

namespace Sado729\ProjectVersion\Services;

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

        $information = $this->loadInformation();
        [$major, $minor, $patch] = $this->parseVersion($information->version);

        $newModuleCount = $this->countNewModules($newFiles);
        if ($newModuleCount > 0) {
            $minor += $newModuleCount;
            $patch = 0;
        } else {
            $patch += count($changedFiles);
            [$minor, $patch] = $this->applyRollover($minor, $patch);
        }

        return $this->persist($information, "{$major}.{$minor}.{$patch}");
    }

    public function release(string $type): string
    {
        $information = $this->loadInformation();
        [$major, $minor, $patch] = $this->parseVersion($information->version);

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
            default:
                throw new \InvalidArgumentException("Unknown release type: {$type}");
        }

        return $this->persist($information, "{$major}.{$minor}.{$patch}");
    }

    public function current(): string
    {
        return $this->loadInformation()->version ?? '1.0.0';
    }

    private function loadInformation(): Information
    {
        return Information::firstOrCreate(['id' => 1], ['version' => '1.0.0']);
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
                if (fnmatch($pattern, $file, FNM_PATHNAME)) {
                    return false;
                }
            }
            return true;
        }));
    }

    private function countNewModules(array $newFiles): int
    {
        $patterns = (array) config('project-version.module_paths', ['app/*']);
        if (!$newFiles || !$patterns) {
            return 0;
        }

        $modules = [];
        foreach ($newFiles as $file) {
            foreach ($patterns as $pattern) {
                if (fnmatch($pattern, $file, FNM_PATHNAME)) {
                    $modules[$file] = true;
                    break;
                }

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

        return count($modules);
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

    private function persist(Information $information, string $version): string
    {
        $information->update(['version' => $version]);

        if (config('project-version.history_enabled', true)) {
            $this->recordHistory($version);
        }

        return $version;
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
