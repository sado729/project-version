<?php

namespace Sado729\ProjectVersion\Tests\Unit;

use Sado729\ProjectVersion\Models\Information;
use Sado729\ProjectVersion\Models\VersionHistory;
use Sado729\ProjectVersion\Services\VersionBumper;
use Sado729\ProjectVersion\Support\Git;
use Sado729\ProjectVersion\Tests\TestCase;

class VersionBumperTest extends TestCase
{
    use \Illuminate\Foundation\Testing\RefreshDatabase;

    public function test_current_returns_default_without_writing(): void
    {
        $bumper = new VersionBumper(new FakeGit([]));
        $this->assertSame('1.0.0', $bumper->current());
        $this->assertSame(0, Information::count(), 'current() must be read-only');
    }

    public function test_current_returns_default_when_table_missing(): void
    {
        \Illuminate\Support\Facades\Schema::dropIfExists('informations');

        $bumper = new VersionBumper(new FakeGit([]));
        $this->assertSame('1.0.0', $bumper->current());
    }

    public function test_noop_bump_does_not_write_history(): void
    {
        config()->set('project-version.ignore_patterns', ['*.md']);

        $git = new FakeGit([
            'diff --name-only' => 'README.md',
            'ls-files --others --exclude-standard' => '',
            'rev-parse --short HEAD' => 'abc1234',
        ]);

        $bumper = new VersionBumper($git);
        $this->assertSame('1.0.0', $bumper->bump());
        $this->assertSame(0, VersionHistory::count(), 'no-op bump must not record history');
    }

    public function test_files_at_module_root_do_not_count_as_modules(): void
    {
        $git = new FakeGit([
            'diff --name-only' => '',
            'ls-files --others --exclude-standard' => "app/A.php\napp/B.php\napp/C.php",
            'rev-parse --short HEAD' => 'abc1234',
        ]);

        $bumper = new VersionBumper($git);
        $this->assertSame('1.0.3', $bumper->bump(), 'top-level files are not modules; they should bump patch only');
    }

    public function test_bump_with_ref_range_uses_diff_filter(): void
    {
        $git = new FakeGit([
            'diff --name-only --diff-filter=ACMR HEAD~1..HEAD' => "src/A.php\nsrc/B.php",
            'diff --name-only --diff-filter=A HEAD~1..HEAD' => '',
            'rev-parse --short HEAD' => 'abc1234',
        ]);

        $bumper = new VersionBumper($git);
        $this->assertSame('1.0.2', $bumper->bump('HEAD~1', 'HEAD'));
    }

    public function test_patch_increments_per_changed_file(): void
    {
        $git = new FakeGit([
            'diff --name-only' => "src/Foo.php\nsrc/Bar.php",
            'ls-files --others --exclude-standard' => '',
            'rev-parse --short HEAD' => 'abc1234',
        ]);

        $bumper = new VersionBumper($git);
        $this->assertSame('1.0.2', $bumper->bump());
    }

    public function test_new_app_module_bumps_minor_and_resets_patch(): void
    {
        $git = new FakeGit([
            'diff --name-only' => "src/Foo.php",
            'ls-files --others --exclude-standard' => "app/Billing/Service.php\napp/Billing/Model.php",
            'rev-parse --short HEAD' => 'abc1234',
        ]);

        $bumper = new VersionBumper($git);
        $this->assertSame('1.1.0', $bumper->bump());
    }

    public function test_ignored_files_do_not_bump_patch(): void
    {
        config()->set('project-version.ignore_patterns', ['*.md', 'composer.lock']);

        $git = new FakeGit([
            'diff --name-only' => "README.md\ncomposer.lock",
            'ls-files --others --exclude-standard' => '',
            'rev-parse --short HEAD' => 'abc1234',
        ]);

        $bumper = new VersionBumper($git);
        $this->assertSame('1.0.0', $bumper->bump());
    }

    public function test_release_increments_chosen_segment(): void
    {
        $bumper = new VersionBumper(new FakeGit(['rev-parse --short HEAD' => 'abc1234']));

        $this->assertSame('1.0.1', $bumper->release('patch'));
        $this->assertSame('1.1.0', $bumper->release('minor'));
        $this->assertSame('2.0.0', $bumper->release('major'));
    }

    public function test_patch_rollover_threshold(): void
    {
        config()->set('project-version.patch_rollover_threshold', 10);
        Information::create(['id' => 1, 'version' => '1.0.9']);

        $git = new FakeGit([
            'diff --name-only' => implode("\n", array_map(fn ($i) => "src/F{$i}.php", range(1, 3))),
            'ls-files --others --exclude-standard' => '',
            'rev-parse --short HEAD' => 'abc1234',
        ]);

        $bumper = new VersionBumper($git);
        $this->assertSame('1.1.2', $bumper->bump());
    }

    public function test_history_is_persisted_when_enabled(): void
    {
        $git = new FakeGit([
            'diff --name-only' => 'src/Foo.php',
            'ls-files --others --exclude-standard' => '',
            'rev-parse --short HEAD' => 'deadbeef',
        ]);

        (new VersionBumper($git))->bump();

        $row = VersionHistory::firstOrFail();
        $this->assertSame('1.0.1', $row->version);
        $this->assertSame('deadbeef', $row->commit_hash);
    }
}

class FakeGit extends Git
{
    public function __construct(private array $map)
    {
        parent::__construct();
    }

    public function run(array $arguments): string
    {
        $key = implode(' ', $arguments);
        return $this->map[$key] ?? '';
    }
}
