<?php

use Sado729\ProjectVersion\Services\VersionBumper;

if (!function_exists('project_version')) {
    function project_version(): string
    {
        return app(VersionBumper::class)->current();
    }
}
