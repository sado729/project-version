<?php

namespace Sado729\ProjectVersion\Services;

use Sado729\ProjectVersion\Models\Information;

class VersionBumper
{
    public function bump(?string $fromRef = null, ?string $toRef = null): string
    {
        [$changedFiles, $newFiles] = $this->collectFileLists($fromRef, $toRef);

        $changedCount = $this->countLines($changedFiles);
        $newCount = $this->countLines($newFiles);

        $information = Information::firstOrCreate(['id' => 1], ['version' => '1.0.0']);
        [$major, $minor, $patch] = array_pad(explode('.', $information->version ?: '1.0.0'), 3, 0);

        $newModuleCount = $newCount > 0
            ? preg_match_all('#^app/[^/]+$#m', $newFiles)
            : 0;

        if ($newModuleCount > 0) {
            $minor += $newModuleCount;
            $patch = 0;
        }

        $patch += $changedCount;

        $version = "{$major}.{$minor}.{$patch}";
        $information->update(['version' => $version]);

        return $version;
    }

    private function collectFileLists(?string $fromRef, ?string $toRef): array
    {
        if ($fromRef !== null && $toRef !== null) {
            $range = escapeshellarg($fromRef) . '..' . escapeshellarg($toRef);
            $changed = shell_exec("git diff --name-only --diff-filter=ACMR {$range}") ?? '';
            $new = shell_exec("git diff --name-only --diff-filter=A {$range}") ?? '';

            return [$changed, $new];
        }

        $changed = shell_exec('git diff --name-only') ?? '';
        $new = shell_exec('git ls-files --others --exclude-standard') ?? '';

        return [$changed, $new];
    }

    private function countLines(string $text): int
    {
        $trimmed = trim($text);
        if ($trimmed === '') {
            return 0;
        }

        return count(explode("\n", $trimmed));
    }
}
