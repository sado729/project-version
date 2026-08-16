<?php

namespace Sado729\ProjectVersion\Tests\Unit;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Sado729\ProjectVersion\Events\GitPullEvent;
use Sado729\ProjectVersion\Services\VersionBumper;
use Sado729\ProjectVersion\Support\Git;
use Sado729\ProjectVersion\Tests\TestCase;

class GitPullCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_failed_pull_returns_failure_and_does_not_bump(): void
    {
        Event::fake();

        $this->app->instance(Git::class, new class extends Git {
            public function run(array $arguments): string
            {
                return implode(' ', $arguments) === 'rev-parse HEAD' ? 'aaa' : '';
            }

            public function mustRun(array $arguments): string
            {
                throw new \RuntimeException('fatal: could not read from remote repository');
            }
        });

        $this->artisan('git:pull')->assertExitCode(1);

        $this->assertSame('1.0.0', app(VersionBumper::class)->current());
        Event::assertNotDispatched(GitPullEvent::class);
    }

    public function test_pull_with_no_new_commits_does_not_bump(): void
    {
        Event::fake();

        $this->app->instance(Git::class, new class extends Git {
            public function run(array $arguments): string
            {
                $key = implode(' ', $arguments);

                // Uncommitted local noise that must NOT be counted after a no-op pull.
                return [
                    'rev-parse HEAD' => 'aaa',
                    'diff --name-only' => 'src/LocalEdit.php',
                    'rev-parse --short HEAD' => 'aaa1234',
                ][$key] ?? '';
            }

            public function mustRun(array $arguments): string
            {
                return 'Already up to date.';
            }
        });

        $this->artisan('git:pull')->assertExitCode(0);

        $this->assertSame('1.0.0', app(VersionBumper::class)->current());
        Event::assertDispatched(GitPullEvent::class, fn (GitPullEvent $e) => $e->version === '1.0.0');
    }

    public function test_pull_with_new_commits_bumps_from_ref_range(): void
    {
        Event::fake();

        $this->app->instance(Git::class, new class extends Git {
            private int $revCalls = 0;

            public function run(array $arguments): string
            {
                $key = implode(' ', $arguments);
                if ($key === 'rev-parse HEAD') {
                    return ++$this->revCalls === 1 ? 'aaa' : 'bbb';
                }

                return [
                    'diff --name-only --diff-filter=ACMR aaa..bbb' => "src/X.php\nsrc/Y.php",
                    'diff --name-only --diff-filter=A aaa..bbb' => '',
                    'rev-parse --short HEAD' => 'bbb1234',
                ][$key] ?? '';
            }

            public function mustRun(array $arguments): string
            {
                return 'Updating aaa..bbb';
            }
        });

        $this->artisan('git:pull')->assertExitCode(0);

        $this->assertSame('1.0.2', app(VersionBumper::class)->current());
        Event::assertDispatched(GitPullEvent::class, fn (GitPullEvent $e) => $e->version === '1.0.2');
    }
}
