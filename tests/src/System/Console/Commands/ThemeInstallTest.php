<?php

namespace Igniter\Tests\System\Console\Commands;

use Exception;
use Igniter\Main\Classes\ThemeManager;
use Igniter\System\Classes\UpdateManager;

it('installs theme successfully', function() {
    $updateManager = mock(UpdateManager::class);
    app()->instance(UpdateManager::class, $updateManager);
    $updateManager->shouldReceive('setLogsOutput')->once();
    $updateManager->shouldReceive('requestApplyItems')->with([[
        'name' => 'demo',
        'type' => 'theme',
    ]])->andReturn(collect([
        (object)['code' => 'demo', 'version' => '1.0.0'],
    ]));
    $updateManager->shouldReceive('install')->once();
    $themeManager = mock(ThemeManager::class);
    app()->instance(ThemeManager::class, $themeManager);
    $themeManager->shouldReceive('loadThemes')->once();
    $themeManager->shouldReceive('installTheme')->with('demo', '1.0.0')->once();

    $this->artisan('igniter:theme-install demo')
        ->expectsOutput('Installing demo theme')
        ->assertExitCode(0);
});

it('handles theme not found', function() {
    $updateManager = mock(UpdateManager::class);
    app()->instance(UpdateManager::class, $updateManager);
    $updateManager->shouldReceive('setLogsOutput')->once();
    $updateManager->shouldReceive('requestApplyItems')->with([[
        'name' => 'demo',
        'type' => 'theme',
    ]])->andReturn(collect());

    $this->artisan('igniter:theme-install demo')
        ->expectsOutput('Theme demo not found')
        ->assertExitCode(0);
});

it('handles composer exception during installation', function() {
    $updateManager = mock(UpdateManager::class);
    app()->instance(UpdateManager::class, $updateManager);
    $updateManager->shouldReceive('setLogsOutput')->once();
    $updateManager->shouldReceive('requestApplyItems')->with([[
        'name' => 'demo',
        'type' => 'theme',
    ]])->andReturn(collect([
        (object)['code' => 'demo', 'version' => '1.0.0'],
    ]));
    $updateManager->shouldReceive('install')->andThrow(new Exception('Composer error'));

    $this->artisan('igniter:theme-install demo')
        ->expectsOutput('Installing demo theme')
        ->expectsOutput('Composer error')
        ->assertExitCode(0);
});
