<?php

declare(strict_types=1);

namespace Igniter\Tests\System\Console\Commands;

use Igniter\System\Classes\ExtensionManager;
use Igniter\System\Classes\UpdateManager;
use InvalidArgumentException;

it('rolls back extension with step option', function() {
    app()->instance(ExtensionManager::class, $extensionManager = mock(ExtensionManager::class));
    $extensionManager->shouldReceive('getIdentifier')->once()->andReturn('test.extension');
    $extensionManager->shouldReceive('hasExtension')->once()->andReturn(true);

    app()->instance(UpdateManager::class, $updateManager = mock(UpdateManager::class));
    $updateManager->shouldReceive('setLogsOutput')->once();
    $updateManager->shouldReceive('rollbackExtension')->once();

    $this->artisan('igniter:extension-refresh test.extension --step=1')
        ->expectsOutput('Rolling back extension test.extension...')
        ->assertExitCode(0);
});

it('purges and migrates extension without step option', function() {
    app()->instance(ExtensionManager::class, $extensionManager = mock(ExtensionManager::class));
    $extensionManager->shouldReceive('getIdentifier')->once()->andReturn('test.extension');
    $extensionManager->shouldReceive('hasExtension')->once()->andReturn(true);

    app()->instance(UpdateManager::class, $updateManager = mock(UpdateManager::class));
    $updateManager->shouldReceive('setLogsOutput')->once();
    $updateManager->shouldReceive('purgeExtension')->once();
    $updateManager->shouldReceive('migrateExtension')->once();

    $this->artisan('igniter:extension-refresh test.extension')
        ->expectsOutput('Purging extension test.extension...')
        ->assertExitCode(0);
});

it('throws exception if extension not found', function() {
    $this->expectException(InvalidArgumentException::class);
    $this->expectExceptionMessage('Extension "igniter.demo" not found.');

    $this->artisan('igniter:extension-refresh Igniter.Demo')
        ->assertExitCode(1);
});

it('does not proceed if confirmation is denied', function() {
    $this->app['env'] = 'production';

    $this->artisan('igniter:extension-refresh test.extension')
        ->expectsConfirmation('Are you sure you want to run this command?', 'no')
        ->assertExitCode(0);
});
