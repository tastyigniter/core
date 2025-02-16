<?php

declare(strict_types=1);

namespace Igniter\Tests\System\Console\Commands;

use Igniter\System\Classes\UpdateManager;

it('destroys all database tables when confirmed', function() {
    $updateManager = mock(UpdateManager::class);
    app()->instance(UpdateManager::class, $updateManager);
    $updateManager->shouldReceive('setLogsOutput')->once();
    $updateManager->shouldReceive('down')->once();

    $this->artisan('igniter:down')
        ->assertExitCode(0);
});

it('does not destroy database tables when not confirmed', function() {
    $this->app['env'] = 'production';

    $this->artisan('igniter:down')
        ->expectsConfirmation('Are you sure you want to run this command?', 'no')
        ->assertExitCode(0);
});
