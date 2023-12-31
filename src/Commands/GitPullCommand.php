<?php

namespace Sado729\ProjectVersion\Commands;

use Sado729\ProjectVersion\Events\GitPullEvent;
use Sado729\ProjectVersion\Models\Information;
use Illuminate\Console\Command;

final class GitPullCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'git:pull';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Perform git pull and run version update process';

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle(): void
    {
        $output = shell_exec('git pull '.config('project-version.git_repository_name').' '.config('project-version.git_branch_name'));

        event(new GitPullEvent());
        $information = Information::first();

        $this->info($output);
        $this->info('Git pull operation completed successfully. New version: '.$information['version']);
    }
}