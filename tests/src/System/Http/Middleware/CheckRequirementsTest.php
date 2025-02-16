<?php

declare(strict_types=1);

namespace Igniter\Tests\System\Http\Middleware;

use Igniter\Flame\Support\Facades\Igniter;
use Igniter\System\Http\Middleware\CheckRequirements;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Response as ResponseFacade;
use Illuminate\Support\Facades\View;

it('returns no database view when database is missing', function() {
    Igniter::shouldReceive('hasDatabase')->andReturn(false);
    View::shouldReceive('make')->with('igniter.system::no_database')->andReturn('no_database_view');
    ResponseFacade::shouldReceive('make')->with('no_database_view')->andReturn(new Response('no_database_view'));

    $middleware = new CheckRequirements;
    $request = new Request;
    $next = function($req) {
        return new Response('next_response');
    };

    $result = $middleware->handle($request, $next);

    expect($result->getContent())->toBe('no_database_view');
});

it('calls next middleware when database exists', function() {
    Igniter::shouldReceive('hasDatabase')->andReturn(true);

    $middleware = new CheckRequirements;
    $request = new Request;
    $next = function($req) {
        return new Response('next_response');
    };

    $result = $middleware->handle($request, $next);

    expect($result->getContent())->toBe('next_response');
});
