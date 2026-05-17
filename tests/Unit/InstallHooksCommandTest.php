<?php

namespace Sado729\ProjectVersion\Tests\Unit;

use Sado729\ProjectVersion\Tests\TestCase;

class InstallHooksCommandTest extends TestCase
{
    private string $basePath;

    protected function setUp(): void
    {
        parent::setUp();

        $this->basePath = sys_get_temp_dir() . '/pv-hooks-' . uniqid();
        mkdir($this->basePath . '/.git/hooks', 0755, true);

        $this->app->setBasePath($this->basePath);
    }

    protected function tearDown(): void
    {
        $this->rrmdir($this->basePath);
        parent::tearDown();
    }

    public function test_creates_managed_block_in_hooks(): void
    {
        $this->artisan('version:install-hooks')->assertSuccessful();

        foreach (['post-commit', 'post-merge'] as $hook) {
            $path = $this->basePath . '/.git/hooks/' . $hook;
            $this->assertFileExists($path);
            $contents = (string) file_get_contents($path);
            $this->assertStringContainsString('# >>> project-version managed >>>', $contents);
            $this->assertStringContainsString('# <<< project-version managed <<<', $contents);
            $this->assertStringContainsString('artisan version:bump', $contents);
        }
    }

    public function test_install_is_idempotent(): void
    {
        $this->artisan('version:install-hooks')->assertSuccessful();
        $this->artisan('version:install-hooks')->assertSuccessful();

        $contents = (string) file_get_contents($this->basePath . '/.git/hooks/post-commit');
        $this->assertSame(1, substr_count($contents, '# >>> project-version managed >>>'));
    }

    public function test_install_preserves_existing_hook_contents(): void
    {
        file_put_contents(
            $this->basePath . '/.git/hooks/post-commit',
            "#!/usr/bin/env bash\necho 'my custom hook'\n"
        );

        $this->artisan('version:install-hooks')->assertSuccessful();

        $contents = (string) file_get_contents($this->basePath . '/.git/hooks/post-commit');
        $this->assertStringContainsString("echo 'my custom hook'", $contents);
        $this->assertStringContainsString('# >>> project-version managed >>>', $contents);
    }

    public function test_uninstall_removes_managed_block_only(): void
    {
        file_put_contents(
            $this->basePath . '/.git/hooks/post-commit',
            "#!/usr/bin/env bash\necho 'keep me'\n"
        );

        $this->artisan('version:install-hooks')->assertSuccessful();
        $this->artisan('version:install-hooks', ['--uninstall' => true])->assertSuccessful();

        $contents = (string) file_get_contents($this->basePath . '/.git/hooks/post-commit');
        $this->assertStringContainsString("echo 'keep me'", $contents);
        $this->assertStringNotContainsString('# >>> project-version managed >>>', $contents);
    }

    private function rrmdir(string $dir): void
    {
        if (! is_dir($dir)) {
            return;
        }
        foreach (scandir($dir) as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }
            $path = $dir . '/' . $item;
            is_dir($path) ? $this->rrmdir($path) : unlink($path);
        }
        rmdir($dir);
    }
}
