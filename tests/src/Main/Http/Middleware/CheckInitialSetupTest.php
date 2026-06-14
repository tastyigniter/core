<?php

declare(strict_types=1);

namespace Igniter\Tests\Main\Http\Middleware;

use Igniter\Flame\Support\Facades\Igniter;
use Igniter\Main\Http\Middleware\CheckInitialSetup;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Route;
use Mockery;

function createRequestWithRoute(string $uri, string $routeName): Request
{
    $request = Request::create($uri, 'GET');
    $route = new Route('GET', $uri, []);
    $route->name($routeName);
    $request->setRouteResolver(fn() => $route);

    return $request;
}

it('allows request to proceed when initial setup is complete on a theme route', function() {
    $request = createRequestWithRoute('/', 'igniter.theme.home');
    Igniter::partialMock()->shouldReceive('hasDatabase')->andReturn(true);

    $middleware = Mockery::mock(CheckInitialSetup::class)->makePartial();
    $middleware->shouldAllowMockingProtectedMethods();
    $middleware->shouldReceive('needsInitialSetup')->andReturn(false);

    expect($middleware->handle($request, fn($req) => 'next'))->toBe('next');
});

it('allows request to proceed when not on a theme route', function() {
    $request = createRequestWithRoute('/admin/some-url', 'igniter.admin.dashboard');
    Igniter::partialMock()->shouldReceive('hasDatabase')->andReturn(true);

    $middleware = Mockery::mock(CheckInitialSetup::class)->makePartial();
    $middleware->shouldAllowMockingProtectedMethods();
    $middleware->shouldReceive('needsInitialSetup')->never();

    expect($middleware->handle($request, fn($req) => 'next'))->toBe('next');
});

it('allows request to proceed when database is missing', function() {
    $request = createRequestWithRoute('/', 'igniter.theme.home');
    Igniter::partialMock()->shouldReceive('hasDatabase')->andReturn(false);

    $middleware = Mockery::mock(CheckInitialSetup::class)->makePartial();
    $middleware->shouldAllowMockingProtectedMethods();
    $middleware->shouldReceive('needsInitialSetup')->never();

    expect($middleware->handle($request, fn($req) => 'next'))->toBe('next');
});

it('redirects to admin when initial setup is required on a theme route', function() {
    $request = createRequestWithRoute('/', 'igniter.theme.home');
    Igniter::partialMock()->shouldReceive('hasDatabase')->andReturn(true);

    $middleware = Mockery::mock(CheckInitialSetup::class)->makePartial();
    $middleware->shouldAllowMockingProtectedMethods();
    $middleware->shouldReceive('needsInitialSetup')->andReturn(true);

    $response = $middleware->handle($request, fn($req) => 'next');

    expect($response)->toBeInstanceOf(RedirectResponse::class)
        ->and($response->getTargetUrl())->toBe(admin_url());
});
