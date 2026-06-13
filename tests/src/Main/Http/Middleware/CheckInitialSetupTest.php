<?php

declare(strict_types=1);

namespace Igniter\Tests\Main\Http\Middleware;

use Igniter\Flame\Support\Facades\Igniter;
use Igniter\Main\Http\Middleware\CheckInitialSetup;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Mockery;

it('allows request to proceed when initial setup is complete', function() {
    $request = Request::create('/some-url', 'GET');
    Igniter::partialMock()->shouldReceive('runningInAdmin')->andReturn(false);
    Igniter::partialMock()->shouldReceive('hasDatabase')->andReturn(true);

    $middleware = Mockery::mock(CheckInitialSetup::class)->makePartial();
    $middleware->shouldAllowMockingProtectedMethods();
    $middleware->shouldReceive('needsInitialSetup')->andReturn(false);

    expect($middleware->handle($request, fn($req) => 'next'))->toBe('next');
});

it('allows request to proceed when in admin area', function() {
    $request = Request::create('/admin/some-url', 'GET');
    Igniter::partialMock()->shouldReceive('runningInAdmin')->andReturn(true);

    $middleware = Mockery::mock(CheckInitialSetup::class)->makePartial();
    $middleware->shouldAllowMockingProtectedMethods();
    $middleware->shouldReceive('needsInitialSetup')->never();

    expect($middleware->handle($request, fn($req) => 'next'))->toBe('next');
});

it('allows request to proceed when database is missing', function() {
    $request = Request::create('/some-url', 'GET');
    Igniter::partialMock()->shouldReceive('runningInAdmin')->andReturn(false);
    Igniter::partialMock()->shouldReceive('hasDatabase')->andReturn(false);

    $middleware = Mockery::mock(CheckInitialSetup::class)->makePartial();
    $middleware->shouldAllowMockingProtectedMethods();
    $middleware->shouldReceive('needsInitialSetup')->never();

    expect($middleware->handle($request, fn($req) => 'next'))->toBe('next');
});

it('redirects to admin when initial setup is required', function() {
    $request = Request::create('/some-url', 'GET');
    Igniter::partialMock()->shouldReceive('runningInAdmin')->andReturn(false);
    Igniter::partialMock()->shouldReceive('hasDatabase')->andReturn(true);

    $middleware = Mockery::mock(CheckInitialSetup::class)->makePartial();
    $middleware->shouldAllowMockingProtectedMethods();
    $middleware->shouldReceive('needsInitialSetup')->andReturn(true);

    $response = $middleware->handle($request, fn($req) => 'next');

    expect($response)->toBeInstanceOf(RedirectResponse::class)
        ->and($response->getTargetUrl())->toBe(admin_url());
});
