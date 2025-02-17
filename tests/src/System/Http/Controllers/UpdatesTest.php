<?php

declare(strict_types=1);

namespace Igniter\Tests\System\Http\Controllers;

use Exception;
use Igniter\System\Classes\UpdateManager;

it('loads updates index page', function() {
    actingAsSuperUser()
        ->get(route('igniter.system.updates'))
        ->assertOk();
});

it('flashes error when requesting update list fails', function() {
    $updateManager = mock(UpdateManager::class);
    app()->instance(UpdateManager::class, $updateManager);
    $updateManager->shouldReceive('requestUpdateList')->andThrow(new Exception('Error requesting update list'));

    actingAsSuperUser()
        ->post(route('igniter.system.updates'))
        ->assertOk();
});
