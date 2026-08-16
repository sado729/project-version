<?php

namespace Sado729\ProjectVersion\Http\Middleware;

use Closure;
use Symfony\Component\HttpFoundation\Response;

class ProjectVersionMiddleware
{
    public function handle($request, Closure $next)
    {
        $response = $next($request);

        $header = config('project-version.version_header', 'X-App-Version');
        if ($header && $response instanceof Response) {
            $response->headers->set($header, project_version());
        }

        return $response;
    }
}
