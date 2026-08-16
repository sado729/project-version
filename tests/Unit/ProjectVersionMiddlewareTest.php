<?php

namespace Sado729\ProjectVersion\Tests\Unit;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Sado729\ProjectVersion\Tests\TestCase;

class ProjectVersionMiddlewareTest extends TestCase
{
    use RefreshDatabase;

    public function test_adds_version_header_to_response(): void
    {
        Route::middleware('project-version')->get('/_pv-header', fn () => 'ok');

        $this->get('/_pv-header')
            ->assertOk()
            ->assertHeader('X-App-Version', '1.0.0');
    }

    public function test_header_name_is_configurable(): void
    {
        config()->set('project-version.version_header', 'X-Release');

        Route::middleware('project-version')->get('/_pv-custom', fn () => 'ok');

        $this->get('/_pv-custom')
            ->assertOk()
            ->assertHeader('X-Release', '1.0.0');
    }

    public function test_header_can_be_disabled(): void
    {
        config()->set('project-version.version_header', null);

        Route::middleware('project-version')->get('/_pv-off', fn () => 'ok');

        $this->get('/_pv-off')
            ->assertOk()
            ->assertHeaderMissing('X-App-Version');
    }

    public function test_does_not_fail_when_informations_table_is_missing(): void
    {
        \Illuminate\Support\Facades\Schema::dropIfExists('informations');

        Route::middleware('project-version')->get('/_pv-no-table', fn () => 'ok');

        $this->get('/_pv-no-table')
            ->assertOk()
            ->assertHeader('X-App-Version', '1.0.0');
    }
}
