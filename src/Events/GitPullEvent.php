<?php

namespace Sado729\ProjectVersion\Events;

use Illuminate\Foundation\Events\Dispatchable;

class GitPullEvent
{
    use Dispatchable;

    public function __construct(public string $version)
    {
    }
}
