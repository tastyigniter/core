<?php

namespace Igniter\Tests\Flame\Geolite\Model;

use Igniter\Flame\Geolite\Distance;
use Igniter\Flame\Geolite\Exception\GeoliteException;
use Igniter\Flame\Geolite\Model\Coordinates;
use Igniter\Flame\Geolite\Model\Ellipsoid;

it('throws exception when creating ellipsoid with invalid name', function($name) {
    expect(fn() => Ellipsoid::createFromName($name))->toThrow(GeoliteException::class);
})->with([
    'empty name' => ['', 'Please provide an ellipsoid name !'],
    'invalid name' => ['invalid', 'invalid ellipsoid does not exist in selected reference ellipsoids !'],
]);

it('throws exception when inverse flattening is zero or negative', function() {
    expect(fn() => new Ellipsoid('Test', 6378137, 0))->toThrow(GeoliteException::class)
        ->and(fn() => new Ellipsoid('Test', 6378137, -298.257223563))->toThrow(GeoliteException::class);
});

it('throws exception for invalid ellipsoid array', function() {
    expect(fn() => Ellipsoid::createFromArray(['name' => 'Custom']))->toThrow(GeoliteException::class);
});

it('throws exception when coordinates have different ellipsoids', function() {
    $from = new Coordinates(10, 20, Ellipsoid::createFromName());
    $to = new Coordinates(15, 25, Ellipsoid::createFromName(Ellipsoid::GRS_1980));
    $distance = (new Distance)->setFrom($from)->setTo($to)->in('meters');
    expect(fn() => $distance->vincenty())->toThrow(GeoliteException::class);
});
