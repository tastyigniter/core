<?php

declare(strict_types=1);

namespace Igniter\Tests\Main\Traits;

use Igniter\Main\Classes\MainController;
use Igniter\Main\Helpers\MainHelper;
use Igniter\Main\Template\Page;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Route;
use Illuminate\Support\Facades\URL;

it('returns current page URL when path is null in url method', function() {
    $page = Page::resolveRouteBinding('components');
    $controller = new MainController;
    $controller->runPage($page);

    expect($controller->url())->toBe('http://localhost/components');
});

it('returns URL with path and params in url method', function() {
    expect(controller()->url('path', ['param' => 'value']))->toBe(URL::to('path', ['param' => 'value']));
});

it('returns current page URL when path is null in pageUrl method', function() {
    $page = Page::resolveRouteBinding('components');
    $controller = new MainController;
    $controller->runPage($page);

    expect($controller->pageUrl())->toBe('http://localhost/components');
});

it('returns page URL with path and params in pageUrl method', function() {
    expect(controller()->pageUrl('path', ['param' => 'value']))->toBe(MainHelper::pageUrl('path', ['param' => 'value']));
});

it('returns parameter value if set', function() {
    $route = new Route('GET', 'test', []);
    $route->bind(request());
    $route->setParameter('param', 'value');

    request()->setRouteResolver(fn() => $route);

    expect(controller()->param('param'))->toBe('value')
        ->and(controller()->param('non-existence', 'default'))->toBe('default');
});

it('returns redirect response for refresh method', function() {
    expect(controller()->refresh())->toBeInstanceOf(RedirectResponse::class);
});

it('returns redirect response for redirect method', function() {
    expect(controller()->redirect('path'))->toBeInstanceOf(RedirectResponse::class);
});

it('returns redirect response for redirectGuest method', function() {
    expect(controller()->redirectGuest('path'))->toBeInstanceOf(RedirectResponse::class);
});

it('returns redirect response for redirectIntended method', function() {
    expect(controller()->redirectIntended('path'))->toBeInstanceOf(RedirectResponse::class);
});

it('returns redirect response for redirectBack method', function() {
    expect(controller()->redirectBack())->toBeInstanceOf(RedirectResponse::class);
});
