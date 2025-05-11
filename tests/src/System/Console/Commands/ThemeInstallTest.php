<?php

declare(strict_types=1);

namespace Igniter\Tests\System\Console\Commands;

use Exception;
use Igniter\System\Classes\UpdateManager;

it('installs theme successfully', function() {
    $packageResponse = [
        'code' => 'demo',
        'type' => 'theme',
        'package' => 'item2/package',
        'name' => 'Package2',
        'version' => '1.0.0',
        'author' => 'Sam',
    ];
    $updateManager = mock(UpdateManager::class);
    app()->instance(UpdateManager::class, $updateManager);
    $updateManager->shouldReceive('setLogsOutput')->once();
    $updateManager->shouldReceive('requestItemDetail')->with([
        'name' => 'demo',
        'type' => 'theme',
    ])->andReturn($packageResponse);
    $updateManager->shouldReceive('install')->once()->andReturn($packageResponse);
    $updateManager->shouldReceive('completeInstall')->once();
    $updateManager->shouldReceive('migrate')->once();

    $this->artisan('igniter:theme-install demo')
        ->expectsOutput('Installing demo theme')
        ->assertExitCode(0);
});

it('handles theme not found', function() {
    $updateManager = mock(UpdateManager::class);
    app()->instance(UpdateManager::class, $updateManager);
    $updateManager->shouldReceive('setLogsOutput')->once();
    $updateManager->shouldReceive('requestItemDetail')->with([
        'name' => 'demo',
        'type' => 'theme',
    ])->andReturn([]);

    $this->artisan('igniter:theme-install demo')
        ->expectsOutput('Theme demo not found')
        ->assertExitCode(0);
});

it('handles composer exception during installation', function() {
    $packageResponse = [
        'code' => 'demo',
        'type' => 'theme',
        'package' => 'item2/package',
        'name' => 'Package2',
        'version' => '1.0.0',
        'author' => 'Sam',
    ];
    $updateManager = mock(UpdateManager::class);
    app()->instance(UpdateManager::class, $updateManager);
    $updateManager->shouldReceive('setLogsOutput')->once();
    $updateManager->shouldReceive('requestItemDetail')->with([
        'name' => 'demo',
        'type' => 'theme',
    ])->andReturn($packageResponse);
    $updateManager->shouldReceive('install')->andThrow(new Exception('Composer error'));

    $this->artisan('igniter:theme-install demo')
        ->expectsOutput('Installing demo theme')
        ->expectsOutput('Composer error')
        ->assertExitCode(0);
});
