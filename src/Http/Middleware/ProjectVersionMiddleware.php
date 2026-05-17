<?php

namespace Sado729\ProjectVersion\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Schema;

class ProjectVersionMiddleware
{
    private static ?bool $tableExists = null;

    public function handle($request, Closure $next)
    {
        if (self::$tableExists !== true) {
            self::$tableExists = Schema::hasTable('informations');
            if (! self::$tableExists) {
                throw new \RuntimeException('ProjectVersion package is not configured: missing informations DB table');
            }
        }

        return $next($request);
    }
}
