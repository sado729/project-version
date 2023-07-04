<?php

namespace Sado729\ProjectVersion;

use Illuminate\Routing\Router;
use Illuminate\Support\ServiceProvider;
use Sado729\ProjectVersion\Http\Middleware\ProjectVersionMiddleware;
use Sado729\ProjectVersion\Commands\GitPullCommand;


class ProjectVersionServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        $router = $this->app->make(Router::class);
        $router->aliasMiddleware('project-version', ProjectVersionMiddleware::class);

        if ($this->app->runningInConsole()) {
 
            $this->publishes([
                __DIR__.'/../config/project-version.php' => config_path('project-version.php'),
            ], 'project-version-config');

            $this->publishes([
                __DIR__ . '/../database/migrations/2023_01_01_100000_create_informations_table.php' =>
                database_path('migrations/' . date('Y_m_d_His', time()) . '_create_informations_table.php'),
            ], 'migrations');

            $this->commands([
                GitPullCommand::class,
            ]);
        }
    }
}
