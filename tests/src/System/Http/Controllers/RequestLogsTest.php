<?php

declare(strict_types=1);

namespace Igniter\Tests\System\Http\Controllers;

use Igniter\System\Models\RequestLog;

it('loads request logs index page', function() {
    actingAsSuperUser()
        ->get(route('igniter.system.request_logs'))
        ->assertOk();
});

it('empties request logs', function() {
    RequestLog::createLog(404);

    actingAsSuperUser()
        ->post(route('igniter.system.request_logs'), [
        ], [
            'X-Requested-With' => 'XMLHttpRequest',
            'X-IGNITER-REQUEST-HANDLER' => 'onEmptyLog',
        ])
        ->assertOk();

    $this->assertDatabaseCount('request_logs', 0);
});
