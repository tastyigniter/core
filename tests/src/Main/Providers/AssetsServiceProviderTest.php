<?php

namespace Igniter\Tests\Main\Providers;

use Igniter\Flame\Support\Facades\Igniter;
use Igniter\Main\Providers\AssetsServiceProvider;
use Igniter\System\Facades\Assets;
use Illuminate\Foundation\Application;

it('registers assets when not running in console and not in admin', function() {
    $app = mock(Application::class)->makePartial();
    $app->shouldReceive('runningInConsole')->andReturn(false);
    Igniter::partialMock()->shouldReceive('runningInAdmin')->andReturn(false);
    $app->shouldReceive('resolving')->withArgs(function($name, $callback) use ($app) {
        $callback(app('assets'));
        return true;
    });

    $provider = new AssetsServiceProvider($app);
    $provider->register();

    expect(Assets::getJs())->toBeNull()
        ->and(Assets::getCss())->toBeNull();
});

it('does not register assets when running in console', function() {
    Igniter::partialMock()->shouldReceive('runningInAdmin')->andReturn(false);
    $app = mock(Application::class)->makePartial();
    $app->shouldReceive('runningInConsole')->andReturn(true)->once();

    $provider = new AssetsServiceProvider($app);
    $provider->register();
});
