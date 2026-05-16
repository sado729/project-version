<?php

namespace Sado729\ProjectVersion\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class GitPullEvent
{
    use Dispatchable, SerializesModels;

    public string $version;

    public function __construct(string $version)
    {
        $this->version = $version;
    }
}
