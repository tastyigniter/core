<?php

declare(strict_types=1);

namespace Igniter\Tests\Flame\Exception;

use Igniter\Flame\Exception\AjaxException;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

it('returns contents as array when string is passed to constructor', function() {
    $exception = new AjaxException('error message');
    expect($exception->getContents())->toBe(['result' => 'error message']);
});

it('returns false when report is called', function() {
    $exception = new AjaxException('error message');
    expect($exception->report())->toBeFalse();
});

it('renders response with correct contents and status code', function() {
    $contents = ['error' => 'Invalid input'];
    $exception = new AjaxException($contents, 422);
    $request = new Request;
    $response = $exception->render($request);
    expect($response)->toBeInstanceOf(Response::class)
        ->and($response->getStatusCode())->toBe(422)
        ->and($response->getContent())->toBe(json_encode($contents));
});
