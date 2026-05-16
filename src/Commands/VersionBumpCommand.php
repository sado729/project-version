<?php

namespace Sado729\ProjectVersion\Commands;

use Illuminate\Console\Command;
use Sado729\ProjectVersion\Services\VersionBumper;

final class VersionBumpCommand extends Command
{
    protected $signature = 'version:bump
        {--from= : Source git ref (e.g. HEAD~1 or ORIG_HEAD). Compared against --to.}
        {--to=HEAD : Target git ref.}
        {--working-tree : Ignore refs and inspect uncommitted + untracked files instead.}';

    protected $description = 'Recalculate project version from a git diff range or the working tree';

    public function handle(VersionBumper $bumper): int
    {
        if ($this->option('working-tree')) {
            $version = $bumper->bump();
        } else {
            $from = $this->option('from');
            $to = $this->option('to') ?: 'HEAD';

            $version = $from
                ? $bumper->bump($from, $to)
                : $bumper->bump();
        }

        $this->info("Version bumped to {$version}");

        return self::SUCCESS;
    }
}
