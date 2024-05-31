<?php

use Illuminate\Http\Request;
use Illuminate\Routing\Route;

uses(Igniter\Tests\TestCase::class)->in(__DIR__.'/src');

function testThemePath()
{
    return realpath(__DIR__.'/resources/themes/tests-theme');
}

function createRequest($uri, $routeName)
{
    $request = new Request([], [], [], [], [], ['REQUEST_URI' => $uri]);

    $request->setRouteResolver(function() use ($uri, $routeName, $request) {
        return (new Route('GET', $uri, ['as' => $routeName]))->bind($request);
    });

    return $request;
}
