<?php

namespace Igniter\Flame\Router;

use Igniter\Flame\Pagic\Router;

it('converts a pagic route uri to laravel style')->skip();

it('finds a theme page', function() {
    expect(resolve(Router::class)->findPage('nested-page'))
        ->permalink
        ->toBe('/nested/page/:slug');
})->skip();
