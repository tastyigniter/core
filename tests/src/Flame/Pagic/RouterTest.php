<?php

namespace Igniter\Tests\Flame\Pagic\Router;

use Igniter\Flame\Pagic\Router;
use Illuminate\Routing\Route;

it('finds a page by its route name', function() {
    $router = resolve(Router::class);
    expect($router->findPage('nested-page')->getFileName())->toBe('nested-page.blade.php')
        ->and($router->findPage('invalid_route'))->toBeNull();
});

it('returns routing parameters', function() {
    $route = new Route('GET', '/test', fn() => 'test');
    $route->bind(request());
    $route->setParameter('param1', 'value1');
    request()->setRouteResolver(fn() => $route);

    $router = resolve(Router::class);
    expect($router->getParameters())->toBe(['param1' => 'value1'])
        ->and($router->getParameter('param1'))->toBe('value1');
});

it('returns URL when route rule is found', function() {
    $router = resolve(Router::class);
    $parameters = [
        'levelOne' => 'level-1',
        'levelTwo' => 'level-2',
        ':levelThree' => null,
    ];

    expect($router->url('nested-page', $parameters))->toBe('/nested-page/level-1/level-2')
        ->and($router->pageUrl('page-with-lifecycle', [':lifeCycle' => null]))->toBe('/page-with-lifecycle/default');
});
