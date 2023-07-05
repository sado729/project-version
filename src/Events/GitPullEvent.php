<?php

namespace Sado729\ProjectVersion\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Sado729\ProjectVersion\Models\Information;

class GitPullEvent
{
    use Dispatchable, SerializesModels;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct()
    {
        $changedFiles = shell_exec('git diff --name-only');
        $newFiles = shell_exec('git ls-files --others --exclude-standard');

        $changedFilesCount = $changedFiles !== null ? count(explode("\n", trim($changedFiles))) : 0;
        $newFilesCount = $newFiles !== null ? count(explode("\n", trim($newFiles))) : 0;


        $information = Information::first();
        list($major, $minor, $patch) = explode('.', $information['version']);

        if ($newFilesCount > 0) {
            $minor += $newFilesCount;
            $patch = 0;
        }
        $patch += $changedFilesCount;

        $information->update(['version' => $major.'.'.$minor.'.'.$patch]);

        return $information;
    }
}