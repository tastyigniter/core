<?php

declare(strict_types=1);

namespace Igniter\Tests\System\Http\Controllers;

use Igniter\Flame\Support\Facades\File;

it('loads system logs index page', function() {
    actingAsSuperUser()
        ->get(route('igniter.system.system_logs'))
        ->assertOk();
});

it('empties system logs', function() {
    File::partialMock()->shouldReceive('exists')->andReturn(false, true);
    File::partialMock()->shouldReceive('isWritable')->andReturnTrue();
    File::partialMock()->shouldReceive('put')->andReturnSelf();

    actingAsSuperUser()
        ->post(route('igniter.system.system_logs'), [
        ], [
            'X-Requested-With' => 'XMLHttpRequest',
            'X-IGNITER-REQUEST-HANDLER' => 'onEmptyLog',
        ])
        ->assertOk();
});
