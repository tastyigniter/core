<?php

declare(strict_types=1);

namespace Igniter\Tests\Main\Classes;

use Igniter\Flame\Pagic\Router;
use Igniter\Flame\Support\Facades\Igniter;
use Igniter\Main\Classes\RouteRegistrar;
use Illuminate\Events\Dispatcher;
use Illuminate\Routing\Router as IlluminateRouter;

it('registers theme page routes', function() {
    app()->instance(Router::class, $router = mock(Router::class));
    $router->shouldReceive('getRouteMap')->once()->andReturn(collect([
        [
            'uri' => 'test',
            'route' => 'test',
            'file' => 'test',
            'defaults' => ['test' => 'test'],
            'constraints' => ['test' => 'test'],
        ],
    ]));

    (new RouteRegistrar(new IlluminateRouter(new Dispatcher)))->forThemePages();
});

it('returns empty array when theme routes are disabled', function() {
    Igniter::shouldReceive('disableThemeRoutes')->andReturn(true)->once();
    Igniter::shouldReceive('uri')->andReturn('/');

    (new RouteRegistrar(new IlluminateRouter(new Dispatcher)))->all();
});
