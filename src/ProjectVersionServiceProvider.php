<?php

namespace Sado729\ProjectVersion;

use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;
use Sado729\ProjectVersion\Commands\GitPullCommand;
use Sado729\ProjectVersion\Commands\InstallHooksCommand;
use Sado729\ProjectVersion\Commands\VersionBumpCommand;
use Sado729\ProjectVersion\Commands\VersionReleaseCommand;
use Sado729\ProjectVersion\Http\Middleware\ProjectVersionMiddleware;
use Sado729\ProjectVersion\Services\VersionBumper;
use Sado729\ProjectVersion\Support\Git;

class ProjectVersionServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/project-version.php', 'project-version');

        $this->app->singleton(Git::class, fn () => new Git());
        $this->app->singleton(VersionBumper::class);
    }

    public function boot(): void
    {
        $router = $this->app->make(Router::class);
        $router->aliasMiddleware('project-version', ProjectVersionMiddleware::class);

        Blade::directive('projectVersion', fn () => "<?php echo e(project_version()); ?>");

        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/../config/project-version.php' => config_path('project-version.php'),
            ], 'project-version-config');

            $this->publishes([
                __DIR__ . '/../database/migrations/2023_01_01_100000_create_informations_table.php' =>
                    database_path('migrations/' . date('Y_m_d_His', time()) . '_create_informations_table.php'),
                __DIR__ . '/../database/migrations/2023_01_01_100100_create_version_histories_table.php' =>
                    database_path('migrations/' . date('Y_m_d_His', time() + 1) . '_create_version_histories_table.php'),
            ], 'migrations');

            $this->commands([
                GitPullCommand::class,
                VersionBumpCommand::class,
                VersionReleaseCommand::class,
                InstallHooksCommand::class,
            ]);
        }
    }
}
