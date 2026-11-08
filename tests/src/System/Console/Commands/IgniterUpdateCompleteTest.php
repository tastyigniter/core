<?php

declare(strict_types=1);

namespace Igniter\Tests\System\Console\Commands;

use Exception;
use Igniter\System\Classes\PackageInfo;
use Igniter\System\Classes\UpdateManager;

it('completes installation of given packages and runs migrations', function() {
    $packageInfo = PackageInfo::fromArray([
        'code' => 'test.extension',
        'package' => 'test/extension',
        'type' => 'extension',
        'name' => 'Test Extension',
        'version' => '2.0.0',
    ]);

    $updateManager = mock(UpdateManager::class);
    app()->instance(UpdateManager::class, $updateManager);
    $updateManager->shouldReceive('setLogsOutput')->once()->andReturnSelf();
    $updateManager->shouldReceive('completeInstall')->once()->withArgs(
        fn(array $packages): bool => count($packages) === 1 && $packages[0] == $packageInfo,
    );
    $updateManager->shouldReceive('migrate')->once();

    $this->artisan('igniter:update-complete', ['--packages' => json_encode([$packageInfo->toArray()])])
        ->assertExitCode(0);
});

it('runs migrations when no packages are given', function() {
    $updateManager = mock(UpdateManager::class);
    app()->instance(UpdateManager::class, $updateManager);
    $updateManager->shouldReceive('setLogsOutput')->once()->andReturnSelf();
    $updateManager->shouldReceive('completeInstall')->once()->with([]);
    $updateManager->shouldReceive('migrate')->once();

    $this->artisan('igniter:update-complete')->assertExitCode(0);
});

it('returns failure when completing installation fails', function() {
    $updateManager = mock(UpdateManager::class);
    app()->instance(UpdateManager::class, $updateManager);
    $updateManager->shouldReceive('setLogsOutput')->once()->andReturnSelf();
    $updateManager->shouldReceive('completeInstall')->once()->andThrow(new Exception('Install failed'));
    $updateManager->shouldReceive('migrate')->never();

    $this->artisan('igniter:update-complete', ['--packages' => '[]'])
        ->expectsOutput('Install failed')
        ->assertExitCode(1);
});

it('returns failure when packages option is not valid json', function() {
    $updateManager = mock(UpdateManager::class);
    app()->instance(UpdateManager::class, $updateManager);
    $updateManager->shouldReceive('completeInstall')->never();

    $this->artisan('igniter:update-complete', ['--packages' => '{invalid'])
        ->assertExitCode(1);
});
