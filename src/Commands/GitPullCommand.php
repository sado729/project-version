<?php

namespace Sado729\ProjectVersion\Commands;

use Illuminate\Console\Command;
use Sado729\ProjectVersion\Events\GitPullEvent;
use Sado729\ProjectVersion\Services\VersionBumper;
use Sado729\ProjectVersion\Support\Git;

final class GitPullCommand extends Command
{
    protected $signature = 'git:pull';
    protected $description = 'Perform git pull and run version update process';

    public function handle(VersionBumper $bumper, Git $git): void
    {
        $repo = (string) config('project-version.git_repository_name');
        $branch = (string) config('project-version.git_branch_name');

        $before = trim($git->run(['rev-parse', 'HEAD']));
        $output = $git->run(['pull', $repo, $branch]);
        $after = trim($git->run(['rev-parse', 'HEAD']));

        $version = $before !== '' && $before !== $after
            ? $bumper->bump($before, $after)
            : $bumper->bump();

        event(new GitPullEvent($version));

        $this->info($output);
        $this->info("Git pull operation completed successfully. New version: {$version}");
    }
}
