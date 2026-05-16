<?php

namespace Sado729\ProjectVersion\Commands;

use Illuminate\Console\Command;
use Sado729\ProjectVersion\Services\VersionBumper;

final class VersionReleaseCommand extends Command
{
    protected $signature = 'version:release {type : major|minor|patch}';
    protected $description = 'Manually bump the project version (major/minor/patch).';

    public function handle(VersionBumper $bumper): int
    {
        $type = strtolower((string) $this->argument('type'));
        if (!in_array($type, ['major', 'minor', 'patch'], true)) {
            $this->error("Invalid release type: {$type}. Use major, minor, or patch.");
            return self::FAILURE;
        }

        $version = $bumper->release($type);
        $this->info("Released {$type} version: {$version}");

        return self::SUCCESS;
    }
}
