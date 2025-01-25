<?php

namespace Igniter\Tests\Main\Http\Middleware;

use Igniter\Flame\Support\Facades\Igniter;
use Igniter\Main\Http\Middleware\CheckMaintenance;
use Igniter\User\Facades\AdminAuth;
use Illuminate\Http\Request;

it('allows request to proceed when not in maintenance mode', function() {
    $request = Request::create('/some-url', 'GET');
    setting()->set(['maintenance_mode' => false]);

    expect((new CheckMaintenance())->handle($request, fn($req) => 'next'))->toBe('next');
});

it('allows request to proceed when in admin area', function() {
    $request = Request::create('/admin/some-url', 'GET');
    Igniter::partialMock()->shouldReceive('runningInAdmin')->andReturn(true);
    setting()->set(['maintenance_mode' => true]);

    expect((new CheckMaintenance())->handle($request, fn($req) => 'next'))->toBe('next');
});

it('allows request to proceed when admin is logged in', function() {
    $request = Request::create('/some-url', 'GET');
    Igniter::partialMock()->shouldReceive('runningInAdmin')->andReturn(false);
    AdminAuth::shouldReceive('isLogged')->andReturn(true);
    setting()->set(['maintenance_mode' => true]);

    expect((new CheckMaintenance())->handle($request, fn($req) => 'next'))->toBe('next');
});

it('returns maintenance response when in maintenance mode and not admin', function() {
    $request = Request::create('/some-url', 'GET');
    Igniter::partialMock()->shouldReceive('runningInAdmin')->andReturn(false);
    AdminAuth::shouldReceive('isLogged')->andReturn(false);
    setting()->set(['maintenance_mode' => true, 'maintenance_message' => 'Maintenance mode']);

    $response = (new CheckMaintenance())->handle($request, fn($req) => 'next');

    expect($response->getStatusCode())->toBe(503)
        ->and($response->getContent())->toContain('Maintenance mode');
});
