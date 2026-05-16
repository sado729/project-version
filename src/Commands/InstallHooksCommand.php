<?php

namespace Sado729\ProjectVersion\Commands;

use Illuminate\Console\Command;

final class InstallHooksCommand extends Command
{
    private const MARKER_BEGIN = '# >>> project-version managed >>>';
    private const MARKER_END = '# <<< project-version managed <<<';

    protected $signature = 'version:install-hooks {--uninstall : Remove the managed block from existing hooks}';
    protected $description = 'Install (or remove) project-version git hooks. Idempotent and preserves existing hook contents.';

    public function handle(): int
    {
        $gitDir = base_path('.git');
        if (!is_dir($gitDir)) {
            $this->error("No .git directory found at {$gitDir}");
            return self::FAILURE;
        }

        $hooksDir = $gitDir . '/hooks';
        if (!is_dir($hooksDir) && !mkdir($hooksDir, 0755, true)) {
            $this->error("Failed to create hooks directory: {$hooksDir}");
            return self::FAILURE;
        }

        $phpBinary = PHP_BINARY ?: 'php';
        $projectRoot = base_path();

        $hooks = [
            'post-commit' => '--from=HEAD~1 --to=HEAD',
            'post-merge' => '--from=ORIG_HEAD --to=HEAD',
        ];

        foreach ($hooks as $name => $args) {
            $path = $hooksDir . '/' . $name;
            $action = $this->option('uninstall')
                ? $this->uninstallFrom($path, $name)
                : $this->installInto($path, $name, $phpBinary, $projectRoot, $args);

            $this->line("  {$action}  {$name}");
        }

        return self::SUCCESS;
    }

    private function installInto(string $path, string $name, string $phpBinary, string $projectRoot, string $args): string
    {
        $block = $this->renderManagedBlock($phpBinary, $projectRoot, $args);
        $existing = is_file($path) ? (string) file_get_contents($path) : '';

        if ($existing === '') {
            $contents = "#!/usr/bin/env bash\nset -e\n\n" . $block . "\n";
            file_put_contents($path, $contents);
            chmod($path, 0755);
            return 'created';
        }

        if (str_contains($existing, self::MARKER_BEGIN)) {
            $contents = $this->replaceManagedBlock($existing, $block);
            file_put_contents($path, $contents);
            chmod($path, 0755);
            return 'updated';
        }

        $contents = rtrim($existing) . "\n\n" . $block . "\n";
        file_put_contents($path, $contents);
        chmod($path, 0755);
        return 'appended';
    }

    private function uninstallFrom(string $path, string $name): string
    {
        if (!is_file($path)) {
            return 'absent  ';
        }

        $existing = (string) file_get_contents($path);
        if (!str_contains($existing, self::MARKER_BEGIN)) {
            return 'no block';
        }

        $stripped = $this->replaceManagedBlock($existing, '');
        $stripped = preg_replace('/\n{3,}/', "\n\n", $stripped) ?? $stripped;
        file_put_contents($path, rtrim($stripped) . "\n");
        return 'cleared ';
    }

    private function replaceManagedBlock(string $contents, string $replacement): string
    {
        $pattern = '/' . preg_quote(self::MARKER_BEGIN, '/') . '.*?' . preg_quote(self::MARKER_END, '/') . '/s';
        $replaced = preg_replace($pattern, $replacement, $contents);

        return $replaced ?? $contents;
    }

    private function renderManagedBlock(string $phpBinary, string $projectRoot, string $args): string
    {
        $phpBinary = escapeshellarg($phpBinary);
        $projectRoot = escapeshellarg($projectRoot);

        return self::MARKER_BEGIN . "\n"
            . "cd {$projectRoot} || exit 0\n"
            . "{$phpBinary} artisan version:bump {$args} >/dev/null 2>&1 || true\n"
            . self::MARKER_END;
    }
}
