<?php

declare(strict_types=1);

namespace Igniter\Tests\System\Http\Middleware;

use Igniter\System\Http\Middleware\PoweredBy;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

it('adds X-Powered-By header when config is enabled', function() {
    config()->set('igniter-system.sendPoweredByHeader', true);
    $middleware = new PoweredBy;
    $request = new Request;
    $response = new Response;
    $next = function($req) use ($response): Response {
        return $response;
    };

    $result = $middleware->handle($request, $next);

    expect($result->headers->get('X-Powered-By'))->toBe('TastyIgniter');
});

it('does not add X-Powered-By header when config is disabled', function() {
    config()->set('igniter-system.sendPoweredByHeader', false);
    $middleware = new PoweredBy;
    $request = new Request;
    $response = new Response;
    $next = function($req) use ($response): Response {
        return $response;
    };

    $result = $middleware->handle($request, $next);

    expect($result->headers->has('X-Powered-By'))->toBeFalse();
});

it('does not add X-Powered-By header for non-Response instance', function() {
    config()->set('igniter-system.sendPoweredByHeader', true);
    $middleware = new PoweredBy;
    $request = new Request;
    $response = new \Symfony\Component\HttpFoundation\Response;
    $next = function($req) use ($response): \Symfony\Component\HttpFoundation\Response {
        return $response;
    };

    $result = $middleware->handle($request, $next);

    expect($result->headers->has('X-Powered-By'))->toBeFalse();
});
