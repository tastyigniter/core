<?php

namespace Igniter\Tests\Flame\Currency\Middleware;

use Igniter\Flame\Currency\Middleware\CurrencyMiddleware;
use Igniter\System\Models\Currency;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

it('handles request without user defined currency', function() {
    App::shouldReceive('runningInConsole')->andReturnFalse();
    $request = mock(Request::class);
    $request->shouldReceive('get')->with('currency')->andReturnNull();
    $request->shouldReceive('getSession')->andReturn($session = mock(SessionInterface::class));
    $session->shouldReceive('get')->with('igniter.currency')->andReturnNull();

    expect((new CurrencyMiddleware())->handle($request, fn($req) => 'next'))->toBe('next');
});

it('handles request with user defined currency in query', function() {
    Currency::factory()->create(['currency_code' => 'ABC', 'currency_status' => 1]);
    App::shouldReceive('runningInConsole')->andReturnFalse();
    $request = mock(Request::class);
    $request->shouldReceive('get')->with('currency')->andReturn('ABC');
    $request->shouldReceive('getSession')->andReturn($session = mock(SessionInterface::class));
    $session->shouldReceive('get')->with('igniter.currency')->andReturnNull();
    $session->shouldReceive('put')->with(['igniter.currency' => 'ABC'])->andReturnSelf();

    expect((new CurrencyMiddleware())->handle($request, fn($req) => 'next'))->toBe('next');
});

it('handles request with user defined currency in session', function() {
    Currency::factory()->create(['currency_code' => 'ABC', 'currency_status' => 1]);
    App::shouldReceive('runningInConsole')->andReturnFalse();
    $request = mock(Request::class);
    $request->shouldReceive('get')->with('currency')->andReturnNull();
    $request->shouldReceive('getSession')->andReturn($session = mock(SessionInterface::class));
    $session->shouldReceive('get')->with('igniter.currency')->andReturn('ABC');
    $session->shouldReceive('put')->with(['igniter.currency' => 'ABC'])->andReturnSelf();

    expect((new CurrencyMiddleware())->handle($request, fn($req) => 'next'))->toBe('next');
});

it('handles request with inactive currency', function() {
    App::shouldReceive('runningInConsole')->andReturnFalse();
    $request = mock(Request::class);
    $request->shouldReceive('get')->with('currency')->andReturn('INVALID');
    $request->shouldReceive('getSession')->andReturn($session = mock(SessionInterface::class));
    $session->shouldReceive('get')->with('igniter.currency')->andReturnNull();
    $session->shouldReceive('put')->never();

    expect((new CurrencyMiddleware())->handle($request, fn($req) => 'next'))->toBe('next');
});

it('handles request when running in console', function() {
    App::shouldReceive('runningInConsole')->andReturnTrue();
    $request = Request::create('/');

    expect((new CurrencyMiddleware())->handle($request, fn($req) => 'next'))->toBe('next');
});
