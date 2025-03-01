<?php

declare(strict_types=1);

namespace Igniter\Tests\Flame\Translation\Middleware;

use Igniter\Flame\Support\Facades\Igniter;
use Igniter\Flame\Translation\Middleware\Localization;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;

it('loads admin locale when running in admin', function() {
    Igniter::shouldReceive('hasDatabase')->andReturn(true);
    Igniter::shouldReceive('runningInAdmin')->andReturn(true);

    $request = new Request;
    $next = fn($request): string => 'next';

    expect((new Localization)->handle($request, $next))->toBe('next');
});

it('loads locale from request when not running in admin', function() {
    Igniter::shouldReceive('hasDatabase')->andReturn(true);
    Igniter::shouldReceive('runningInAdmin')->andReturn(false);
    $mockRequest = $this->mock(Request::class);
    $mockRequest->shouldReceive('setUserResolver')->andReturnNull();
    $mockRequest->shouldReceive('segment')->with(1)->andReturn('fr');
    app()->instance('request', $mockRequest);
    setting()->setPref('supported_languages', ['fr', 'en']);

    expect((new Localization)->handle($mockRequest, fn($request) => 'next'))->toBe('next')
        ->and(app()->getLocale())->toBe('fr');
});

it('loads locale from browser when not running in admin', function() {
    Igniter::shouldReceive('hasDatabase')->andReturn(true);
    Igniter::shouldReceive('runningInAdmin')->andReturn(false);
    request()->server->set('HTTP_ACCEPT_LANGUAGE', 'fr');
    setting()->setPref('supported_languages', ['fr', 'en']);
    setting()->set('detect_language', true);

    $request = new Request;

    expect((new Localization)->handle($request, fn($request) => 'next'))->toBe('next')
        ->and(app()->getLocale())->toBe('fr');

    request()->server->set('HTTP_ACCEPT_LANGUAGE', 'de');
    expect((new Localization)->handle($request, fn($request) => 'next'))->toBe('next')
        ->and(app()->getLocale())->toBe('en')
        ->and(app('translator.localization')->getLocale())->toBe('en');
});

it('loads locale from session when not running in admin', function() {
    Session::shouldReceive('get')->with('igniter.translation.locale')->andReturn('fr');
    Session::shouldReceive('put')->with('igniter.translation.locale', 'fr')->andReturnNull();
    Igniter::shouldReceive('hasDatabase')->andReturn(true);
    Igniter::shouldReceive('runningInAdmin')->andReturn(false);
    setting()->setPref('supported_languages', ['fr', 'en']);
    app('translator.localization')->setSessionLocale('fr');
    expect(app('translator.localization')->loadLocale())->toBeNull();

    $request = new Request;
    expect((new Localization)->handle($request, fn($request) => 'next'))->toBe('next')
        ->and(app()->getLocale())->toBe('fr')
        ->and(app('translator.localization')->getLocale())->toBe('fr');
});

it('returns false when setting invalid locale', function() {
    expect(resolve(\Igniter\Flame\Translation\Localization::class)->setLocale('invalid'))->toBeFalse();
});
