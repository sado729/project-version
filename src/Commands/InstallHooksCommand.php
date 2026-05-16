<?php

namespace Sado729\ProjectVersion\Commands;

use Illuminate\Console\Command;

final class InstallHooksCommand extends Command
{
    protected $signature = 'version:install-hooks {--force : Overwrite existing hooks}';
    protected $description = 'Install git post-commit and post-merge hooks that auto-bump the project version';

    public function handle(): int
    {
        $gitDir = base_path('.git');
        if (!is_dir($gitDir)) {
            $this->error('No .git directory found at ' . $gitDir);
            return self::FAILURE;
        }

        $hooksDir = $gitDir . '/hooks';
        if (!is_dir($hooksDir) && !mkdir($hooksDir, 0755, true)) {
            $this->error('Failed to create hooks directory: ' . $hooksDir);
            return self::FAILURE;
        }

        $phpBinary = PHP_BINARY ?: 'php';
        $projectRoot = base_path();

        $postCommit = $this->renderHook($phpBinary, $projectRoot, '--from=HEAD~1 --to=HEAD');
        $postMerge = $this->renderHook($phpBinary, $projectRoot, '--from=ORIG_HEAD --to=HEAD');

        $written = [];
        foreach (['post-commit' => $postCommit, 'post-merge' => $postMerge] as $name => $contents) {
            $path = $hooksDir . '/' . $name;
            if (file_exists($path) && !$this->option('force')) {
                $this->warn("Skipped {$name}: already exists. Re-run with --force to overwrite.");
                continue;
            }

            if (file_put_contents($path, $contents) === false) {
                $this->error("Failed to write {$path}");
                return self::FAILURE;
            }

            chmod($path, 0755);
            $written[] = $name;
        }

        if ($written) {
            $this->info('Installed hooks: ' . implode(', ', $written));
        }

        return self::SUCCESS;
    }

    private function renderHook(string $phpBinary, string $projectRoot, string $args): string
    {
        $phpBinary = escapeshellarg($phpBinary);
        $projectRoot = escapeshellarg($projectRoot);

        return <<<SH
#!/usr/bin/env bash
# Auto-installed by sado729/project-version
cd {$projectRoot} || exit 0
{$phpBinary} artisan version:bump {$args} >/dev/null 2>&1 || true

SH;
    }
}
