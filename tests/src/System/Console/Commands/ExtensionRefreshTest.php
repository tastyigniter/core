<?php

declare(strict_types=1);

namespace Igniter\Tests\System\Console\Commands;

use Igniter\System\Classes\UpdateManager;

it('rolls back extension with step option', function() {
    $updateManager = mock(UpdateManager::class);
    app()->instance(UpdateManager::class, $updateManager);
    $updateManager->shouldReceive('setLogsOutput')->once();
    $updateManager->shouldReceive('rollbackExtension')->once();

    $this->artisan('igniter:extension-refresh igniter.user --step=1')
        ->expectsOutput('Rolling back extension igniter.user...')
        ->assertExitCode(0);
});

it('purges and migrates extension without step option', function() {
    $updateManager = mock(UpdateManager::class);
    app()->instance(UpdateManager::class, $updateManager);
    $updateManager->shouldReceive('setLogsOutput')->once();
    $updateManager->shouldReceive('purgeExtension')->once();
    $updateManager->shouldReceive('migrateExtension')->once();

    $this->artisan('igniter:extension-refresh igniter.user')
        ->expectsOutput('Purging extension igniter.user...')
        ->assertExitCode(0);
});

it('throws exception if extension not found', function() {
    $this->expectException(\InvalidArgumentException::class);
    $this->expectExceptionMessage('Extension "igniter.demo" not found.');

    $this->artisan('igniter:extension-refresh Igniter.Demo')
        ->assertExitCode(1);
});

it('does not proceed if confirmation is denied', function() {
    $this->app['env'] = 'production';

    $this->artisan('igniter:extension-refresh igniter.user')
        ->expectsConfirmation('Are you sure you want to run this command?', 'no')
        ->assertExitCode(0);
});
