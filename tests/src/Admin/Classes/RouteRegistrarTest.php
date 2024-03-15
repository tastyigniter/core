<?php

namespace Tests\Admin\Classes;

use Igniter\Admin\Classes\RouteRegistrar;
use Illuminate\Events\Dispatcher;
use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Route;

beforeEach(function () {
    $this->routeRegistrar = new RouteRegistrar(new Router(new Dispatcher));
});

it('registers all routes correctly', function () {
    $this->routeRegistrar->all();

    expect(Route::has('igniter.admin.assets'))->toBeTrue()
        ->and(Route::has('tests.admin.test_controller'))->toBeTrue();
});

it('registers assets routes correctly', function () {
    $this->routeRegistrar->forAssets();

    expect(Route::has('igniter.admin.assets'))->toBeTrue();
});

it('registers admin pages routes correctly', function () {
    $this->routeRegistrar->forAdminPages();

    expect(Route::has('igniter.admin.dashboard'))->toBeTrue()
        ->and(Route::has('igniter.admin.statuses'))->toBeTrue();
});