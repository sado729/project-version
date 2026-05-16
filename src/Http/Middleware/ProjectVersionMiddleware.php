<?php

namespace Sado729\ProjectVersion\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Schema;

class ProjectVersionMiddleware
{
    public function handle($request, Closure $next)
    {
        if (!Schema::hasTable('informations')) {
            throw new \Exception('ProjectVersion package is not configured: missing informations DB table');
        }

        return $next($request);
    }
}
