<?php

declare(strict_types=1);

namespace Igniter\Tests\Flame\Exception;

use Igniter\Flame\Exception\FlashException;
use Igniter\Tests\Fixtures\Controllers\TestController;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

it('returns correct contents array', function() {
    $exception = FlashException::error('Error message');
    expect($exception->getContents())->toBe([
        'class' => 'danger',
        'title' => null,
        'text' => 'Error message',
        'important' => false,
        'overlay' => false,
        'actionUrl' => null,
    ]);
});

it('returns correct status code', function() {
    $exception = FlashException::alert('Error message', 'danger', 404)->actionUrl('http://example.com');
    expect($exception->getStatusCode())->toBe(404)
        ->and($exception->getContents())->toHaveKey('class', 'danger')
        ->and($exception->getContents())->toHaveKey('actionUrl', 'http://example.com');
});

it('returns empty headers array', function() {
    $exception = FlashException::info('Info message');
    expect($exception->getHeaders())->toBe([])
        ->and($exception->getContents())->toHaveKey('class', 'info');
});

it('returns shouldReport value', function() {
    $exception = FlashException::warning('Warning message');
    $exception->shouldReport(true);
    expect($exception->report())->toBeTrue()
        ->and($exception->getContents())->toHaveKey('class', 'warning');
});

it('renders response with redirect when redirectUrl is set', function() {
    $exception = FlashException::error('Error message')->overlay()->important();
    $exception->redirectTo('http://example.com');

    $request = new Request;
    $response = $exception->render($request);
    expect($response->getTargetUrl())->toBe('http://example.com')
        ->and($exception->getContents())->toHaveKey('overlay', true)
        ->and($exception->getContents())->toHaveKey('important', true);
});

it('renders response with custom response when response is set', function() {
    $exception = FlashException::success('Success message');
    $customResponse = new Response('Custom response', 200);
    $exception->setResponse($customResponse);
    $request = new Request;
    $response = $exception->render($request);
    expect($response)->toBe($customResponse)
        ->and($exception->getContents())->toHaveKey('class', 'success');
});

it('renders JSON response when request expects JSON', function() {
    $exception = new FlashException('Error message', 'danger');
    $request = new Request;
    $request->headers->set('Accept', 'application/json');
    $response = $exception->render($request);
    expect($response->getStatusCode())->toBe(406)
        ->and($response->getContent())->toBe(json_encode([
            'X_IGNITER_FLASH_MESSAGES' => [$exception->getContents()],
        ]));
});

it('renders false when no redirectUrl or response is set and app is in debug mode', function() {
    config(['app.debug' => true]);
    $exception = new FlashException('Error message', 'danger');
    $request = new Request;
    $response = $exception->render($request);
    expect($response)->toBeFalse();
    config(['app.debug' => false]);
});

it('renders flash partial when debugging is off', function() {
    config(['app.debug' => false]);
    $exception = new FlashException('Error message', 'danger');
    $request = new Request;
    $request->setRouteResolver(fn() => new class
    {
        public function getController(): TestController
        {
            return new TestController;
        }
    });
    $response = $exception->render($request);
    expect($response->getContent())->toContain('Error message');
    config(['app.debug' => true]);
});
