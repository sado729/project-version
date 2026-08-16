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

    public function handle(VersionBumper $bumper, Git $git): int
    {
        $repo = (string) config('project-version.git_repository_name');
        $branch = (string) config('project-version.git_branch_name');

        $before = trim($git->run(['rev-parse', 'HEAD']));

        try {
            $output = $git->mustRun(['pull', $repo, $branch]);
        } catch (\Throwable $e) {
            $this->error('git pull failed: ' . trim($e->getMessage()));

            return self::FAILURE;
        }

        $after = trim($git->run(['rev-parse', 'HEAD']));

        if ($before !== '' && $after !== '' && $before !== $after) {
            $version = $bumper->bump($before, $after);
            $message = "Git pull operation completed successfully. New version: {$version}";
        } else {
            $version = $bumper->current();
            $message = "Already up to date. Current version: {$version}";
        }

        event(new GitPullEvent($version));

        $this->info(trim($output));
        $this->info($message);

        return self::SUCCESS;
    }
}
