<?php

namespace Igniter\Flame\Router;

use Igniter\Flame\Pagic\Router;

it('converts a pagic route uri to laravel style')->skip();

it('finds a theme page', function () {
    expect(resolve(Router::class)->findPage('components'))
        ->permalink
        ->toBe('/components');
});

it('rewrites page path to url', function () {
    expect(resolve(Router::class)->findByFile('nested-page', ['slug' => 'hello']))
        ->toBe('/nested/page/hello');
});
