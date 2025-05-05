<?php

declare(strict_types=1);

namespace Igniter\Tests\System\Console\Commands;

use Exception;
use Igniter\System\Classes\UpdateManager;
use Illuminate\Support\Facades\Http;

it('installs an extension', function() {
    config(['igniter-system.disableCoreUpdates' => true]);
    $expectedResponse = [
        'code' => 'test.extension',
        'type' => 'extension',
        'package' => 'item2/package',
        'name' => 'Package2',
        'version' => '1.0.0',
        'author' => 'Sam',
    ];
    app()->instance(UpdateManager::class, $updateManager = mock(UpdateManager::class));
    $updateManager->shouldReceive('setLogsOutput')->once();
    $updateManager->shouldReceive('requestItemDetail')->once()->with([
        'name' => 'test.extension',
        'type' => 'extension',
    ])->andReturn($expectedResponse);
    $updateManager->shouldReceive('install')->once();
    $updateManager->shouldReceive('completeInstall')->once();
    $updateManager->shouldReceive('migrateExtension')->once();

    $this->artisan('igniter:extension-install test.extension')
        ->expectsOutput('Installing test.extension extension')
        ->assertExitCode(0);
});

it('outputs error if extension not found', function() {
    config(['igniter-system.disableCoreUpdates' => true]);
    $expectedResponse = [
        'data' => [],
    ];
    Http::fake(['https://api.tastyigniter.com/v2/core/installed' => Http::response($expectedResponse)]);

    $this->artisan('igniter:extension-install IgniterLab.Demo')
        ->expectsOutput('Extension IgniterLab.Demo not found')
        ->assertExitCode(0);
});

it('handles composer exception during installation', function() {
    config(['igniter-system.disableCoreUpdates' => true]);
    $expectedResponse = [
        'code' => 'test.extension',
        'type' => 'extension',
        'package' => 'item2/package',
        'name' => 'Package2',
        'version' => '1.0.0',
        'author' => 'Sam',
    ];
    app()->instance(UpdateManager::class, $updateManager = mock(UpdateManager::class));
    $updateManager->shouldReceive('setLogsOutput')->once();
    $updateManager->shouldReceive('requestItemDetail')->once()->andReturn($expectedResponse);
    $updateManager->shouldReceive('install')->andThrow(new Exception('Composer error'));
    $updateManager->shouldReceive('completeInstall')->never();
    $updateManager->shouldReceive('migrateExtension')->never();

    $this->artisan('igniter:extension-install test.extension')
        ->expectsOutput('Composer error')
        ->assertExitCode(0);
});
