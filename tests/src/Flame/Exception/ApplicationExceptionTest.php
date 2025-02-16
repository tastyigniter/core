<?php

declare(strict_types=1);

namespace Igniter\Tests\Flame\Exception;

use Igniter\Flame\Exception\ApplicationException;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

it('renders response with default message and status code', function() {
    config(['app.debug' => false]);
    $exception = new ApplicationException('Default error message');
    $request = new Request;
    $response = $exception->render($request);
    expect($response)->toBeInstanceOf(Response::class)
        ->and($response->getStatusCode())->toBe(500)
        ->and($response->getContent())->toBe('Default error message');
});

it('renders response with debug information when app debug is true', function() {
    config(['app.debug' => true]);
    $exception = new ApplicationException('Debug error message');
    $request = new Request;
    $response = $exception->render($request);
    expect($response)->toBeInstanceOf(Response::class)
        ->and($response->getStatusCode())->toBe(500)
        ->and($response->getContent())->toContain('Debug error message')
        ->and($response->getContent())->toContain('on line')
        ->and($response->getContent())->toContain('of')
        ->and($response->getContent())->toContain($exception->getTraceAsString());
    config(['app.debug' => false]);
});
