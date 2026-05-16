<?php

namespace Sado729\ProjectVersion\Commands;

use Sado729\ProjectVersion\Events\GitPullEvent;
use Sado729\ProjectVersion\Services\VersionBumper;
use Illuminate\Console\Command;

final class GitPullCommand extends Command
{
    protected $signature = 'git:pull';
    protected $description = 'Perform git pull and run version update process';

    public function handle(VersionBumper $bumper): void
    {
        $repo = escapeshellarg((string) config('project-version.git_repository_name'));
        $branch = escapeshellarg((string) config('project-version.git_branch_name'));

        $beforePull = trim((string) shell_exec('git rev-parse HEAD'));
        $output = shell_exec("git pull {$repo} {$branch}");
        $afterPull = trim((string) shell_exec('git rev-parse HEAD'));

        $version = $beforePull !== '' && $beforePull !== $afterPull
            ? $bumper->bump($beforePull, $afterPull)
            : $bumper->bump();

        event(new GitPullEvent($version));

        $this->info($output);
        $this->info('Git pull operation completed successfully. New version: ' . $version);
    }
}
