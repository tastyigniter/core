<?php

declare(strict_types=1);

namespace Igniter\Tests\System\Http\Controllers;

use Facades\Igniter\Flame\Support\LogViewer as LogViewerFacade;
use Igniter\Flame\Support\Facades\File;
use Igniter\Flame\Support\LogViewer;

it('loads system logs index page', function() {
    actingAsSuperUser()
        ->get(route('igniter.system.system_logs'))
        ->assertOk();
});

it('flashes error for large log file', function() {
    $dailyLogFile = storage_path('logs/laravel-'.date('Y-m-d').'.log');
    $logFile = storage_path('logs/laravel.log');
    File::partialMock()->shouldReceive('exists')->with($dailyLogFile)->andReturnFalse();
    File::partialMock()->shouldReceive('exists')->with($logFile)->andReturnTrue();
    File::partialMock()->shouldReceive('size')->with($logFile)->andReturn(LogViewer::MAX_FILE_SIZE + 100);

    actingAsSuperUser()
        ->get(route('igniter.system.system_logs'))
        ->assertSee(sprintf(
            'The log file %s is too large to be processed. Maximum size is %d bytes.',
            LogViewerFacade::getFileName(),
            LogViewer::MAX_FILE_SIZE,
        ));
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
