<?php

declare(strict_types=1);

namespace Igniter\Tests\Flame\Exception;

use Exception;
use Igniter\Flame\Exception\AjaxException;
use Igniter\Flame\Exception\ErrorHandler;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Http\Request;
use Illuminate\Session\TokenMismatchException;
use Illuminate\Support\Facades\Event;
use Symfony\Component\HttpKernel\Exception\HttpException;

it('does not report exceptions in dontReport list', function() {
    $handler = resolve(ExceptionHandler::class);
    $errorHandler = new ErrorHandler($handler);

    $exception = new ModelNotFoundException;
    $result = $errorHandler->report($exception);

    expect($result)->toBeFalse();
});

it('reports exceptions not in dontReport list', function() {
    $handler = resolve(ExceptionHandler::class);
    $errorHandler = new ErrorHandler($handler);

    $exception = new Exception('Test exception');
    $result = $errorHandler->report($exception);

    expect($result)->toBeNull();
});

it('returns null when exception.beforeRender event has no listeners', function() {
    $handler = resolve(ExceptionHandler::class);
    $exception = new HttpException(404);
    $request = new Request;

    expect((new ErrorHandler($handler))->render($request, $exception))->toBeNull();
});

it('renders response with exception.beforeRender event', function() {
    Event::listen('exception.beforeRender', fn($request, $exception) => response('Custom response', 500));
    $handler = resolve(ExceptionHandler::class);
    $exception = new Exception('', 500);
    $request = new Request;

    $response = (new ErrorHandler($handler))->render($request, $exception);

    expect($response->getContent())->toContain('Custom response');
});

it('returns null when exception.beforeReport event returns false', function() {
    Event::listen('exception.beforeReport', fn($exception): false => false);
    $handler = resolve(ExceptionHandler::class);
    $exception = new Exception('Test exception');

    expect((new ErrorHandler($handler))->report($exception))->toBeNull();
});

it('maps csrf token mismatch exception to flash exception', function() {
    $handler = resolve(ExceptionHandler::class);
    $exception = new TokenMismatchException;
    expect($handler->report($exception))->toBeNull();
});

it('renders response with correct status code for AjaxException', function() {
    $handler = resolve(ExceptionHandler::class);
    $errorHandler = new ErrorHandler($handler);

    $exception = new AjaxException('Ajax error');
    $request = new Request;

    expect($errorHandler->render($request, $exception))->toBeNull();
});
