<?php

namespace Igniter\Tests\Flame\Translation\Middleware;

use Igniter\Flame\Support\Facades\Igniter;
use Igniter\Flame\Translation\Middleware\Localization;
use Illuminate\Http\Request;

it('loads admin locale when running in admin', function() {
    Igniter::shouldReceive('hasDatabase')->andReturn(true);
    Igniter::shouldReceive('runningInAdmin')->andReturn(true);

    $request = new Request();
    $next = function($request) {
        return 'next';
    };

    expect((new Localization())->handle($request, $next))->toBe('next');
});

it('loads locale from request when not running in admin', function() {
    Igniter::shouldReceive('hasDatabase')->andReturn(true);
    Igniter::shouldReceive('runningInAdmin')->andReturn(false);
    $mockRequest = $this->mock(Request::class);
    $mockRequest->shouldReceive('setUserResolver')->andReturnNull();
    $mockRequest->shouldReceive('segment')->with(1)->andReturn('fr');
    app()->instance('request', $mockRequest);
    setting()->setPref('supported_languages', ['fr', 'en']);

    expect((new Localization())->handle($mockRequest, fn($request) => 'next'))->toBe('next')
        ->and(app()->getLocale())->toBe('fr');
});

it('loads locale from browser when not running in admin', function() {
    Igniter::shouldReceive('hasDatabase')->andReturn(true);
    Igniter::shouldReceive('runningInAdmin')->andReturn(false);
    request()->server->set('HTTP_ACCEPT_LANGUAGE', 'fr');
    setting()->setPref('supported_languages', ['fr', 'en']);
    setting()->set('detect_language', true);
    $request = new Request();

    expect((new Localization())->handle($request, fn($request) => 'next'))->toBe('next')
        ->and(app()->getLocale())->toBe('fr');

    request()->server->set('HTTP_ACCEPT_LANGUAGE', 'de');
    expect((new Localization())->handle($request, fn($request) => 'next'))->toBe('next')
        ->and(app()->getLocale())->toBe('en')
        ->and(app('translator.localization')->getLocale())->toBe('en');
});

it('loads locale from session when not running in admin', function() {
    Igniter::shouldReceive('hasDatabase')->andReturn(true);
    Igniter::shouldReceive('runningInAdmin')->andReturn(false);
    setting()->setPref('supported_languages', ['fr', 'en']);
    app('translator.localization')->setSessionLocale('fr');
    expect(app('translator.localization')->loadLocale())->toBeNull();

    $request = new Request();
    expect((new Localization())->handle($request, fn($request) => 'next'))->toBe('next')
        ->and(app()->getLocale())->toBe('fr')
        ->and(app('translator.localization')->getLocale())->toBe('fr');
});
