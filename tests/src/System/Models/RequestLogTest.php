<?php

namespace Igniter\Tests\System\Models;

use Igniter\Flame\Support\Facades\Igniter;
use Igniter\System\Models\RequestLog;

it('creates a new log entry with default status code', function() {
    Igniter::shouldReceive('hasDatabase')->andReturn(true);
    setting()->set(['enable_request_log' => true]);
    request()->headers->set('referer', 'http://referrer.com');

    $log = RequestLog::createLog();

    expect($log)->not->toBeNull()
        ->and($log->url)->toBe('http://localhost')
        ->and($log->status_code)->toBe(404)
        ->and($log->referrer)->toContain('http://referrer.com')
        ->and($log->count)->toBe(1);
});

it('increments count for existing log entry', function() {
    Igniter::shouldReceive('hasDatabase')->andReturn(true);
    setting()->set(['enable_request_log' => true]);
    request()->headers->set('referer', 'http://referrer.com');

    RequestLog::create(['url' => 'http://localhost', 'status_code' => 404, 'count' => 1]);

    $log = RequestLog::createLog();

    expect($log)->not->toBeNull()
        ->and($log->count)->toBe(2);
});

it('does not create log entry if database is not available', function() {
    Igniter::shouldReceive('hasDatabase')->andReturn(false);

    $log = RequestLog::createLog();

    expect($log)->toBeNull();
});

it('does not create log entry if logging is disabled', function() {
    Igniter::shouldReceive('hasDatabase')->andReturn(true);
    setting()->set(['enable_request_log' => false]);

    $log = RequestLog::createLog();

    expect($log)->toBeNull();
});

it('prunes old log entries', function() {
    setting()->set(['activity_log_timeout' => 30]);
    RequestLog::create(['created_at' => now()->subDays(31)]);
    RequestLog::create(['created_at' => now()->subDays(29)]);

    (new RequestLog)->pruneAll();

    $logs = RequestLog::all();
    expect($logs)->toHaveCount(1)
        ->and($logs->first()->created_at->diffInDays(now()))->toBeLessThan(30);
});
