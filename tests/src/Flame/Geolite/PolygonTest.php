<?php

namespace Igniter\Tests\Flame\Geolite;

use Igniter\Flame\Geolite\Contracts\PolygonInterface;
use Igniter\Flame\Geolite\Model\Bounds;
use Igniter\Flame\Geolite\Model\Coordinates;
use Igniter\Flame\Geolite\Model\CoordinatesCollection;
use Igniter\Flame\Geolite\Polygon;

it('creates a polygon with coordinates array', function() {
    $coordinates = [new Coordinates(10, 20), new Coordinates(15, 25)];
    $polygon = new Polygon($coordinates);
    $polygon->setPrecision(5);
    expect($polygon->getCoordinates()->toArray())->toBe($coordinates)
        ->and($polygon->getGeometryType())->toBe('POLYGON')
        ->and($polygon->getCoordinate())->toEqual($coordinates[0])
        ->and($polygon->getPrecision())->toBe(5)
        ->and($polygon->getIterator())->toBeInstanceOf(\Traversable::class)
        ->and($polygon->getBounds()->getAsPolygon())->toBeInstanceOf(PolygonInterface::class)
        ->and($polygon->getBounds()->toArray())->toHaveKeys(['north', 'east', 'south', 'west']);
});

it('creates a polygon with coordinates collection', function() {
    $bounds = new Bounds(10, 20, 15, 25);
    $coordinatesCollection = new CoordinatesCollection([new Coordinates(10, 20), new Coordinates(15, 25)]);
    $polygon = new Polygon($coordinatesCollection);
    $polygon->setBounds($bounds);
    expect($polygon->getCoordinates())->toBe($coordinatesCollection)
        ->and($polygon->getBounds())->toBe($bounds)
        ->and($polygon->toArray())->toBeArray()
        ->and($polygon->jsonSerialize())->toBeArray()
        ->and(isset($polygon['invalid']))->toBeFalse();
});

it('throws exception for invalid coordinates input', function() {
    expect(fn() => new Polygon('invalid'))->toThrow(\InvalidArgumentException::class);
});

it('adds coordinates to the polygon', function() {
    $polygon = new Polygon();
    $coordinate = new Coordinates(10, 20);
    $polygon->push($coordinate);
    $polygon->put(123, $coordinate);
    $polygon->put([123 => [15, 25]]);
    $polygon[12] = new Coordinates(15, 25);
    $polygon->forget(123);
    unset($polygon[12]);
    $polygon->setCoordinates(new CoordinatesCollection([new Coordinates(10, 20), 'valid' => new Coordinates(15, 25)]));

    expect(fn() => $polygon->put(0))->toThrow(\InvalidArgumentException::class)
        ->and($polygon->getCoordinates()->count())->toBe(2)
        ->and($polygon->isEmpty())->toBeFalse()
        ->and($polygon['valid'])->toBeInstanceOf(Coordinates::class);
});

it('merges polygon bounds correctly', function() {
    $coordinates = [new Coordinates(10, 20), new Coordinates(15, 25)];
    $polygon = new Polygon($coordinates);

    $bounds = new Bounds(15, 25, 35, 45);
    $mergedBounds = $polygon->getBounds()->merge($bounds);
    expect($mergedBounds->toArray())->toBe([
        'south' => 10.0,
        'west' => 20.0,
        'north' => 35.0,
        'east' => 45.0,
    ]);
});

it('returns false if coordinate is empty when checking point in polygon', function() {
    $polygon = new Polygon();
    $point = new Coordinates(10, 20);
    expect($polygon->pointInPolygon($point))->toBeFalse();
});

it('checks if a point is on the horizontal boundary of the polygon', function() {
    $coordinates = [new Coordinates(10, 20), new Coordinates(15, 25), new Coordinates(10, 25)];
    $polygon = new Polygon($coordinates);
    $point = new Coordinates(10, 22.5);
    expect($polygon->pointInPolygon($point))->toBeTrue();
});

it('checks if a point is on the vertical boundary of the polygon', function() {
    $coordinates = [new Coordinates(10, 20), new Coordinates(15, 25), new Coordinates(15, 20)];
    $polygon = new Polygon($coordinates);
    $point = new Coordinates(12.5, 20);
    expect($polygon->pointInPolygon($point))->toBeTrue();
});

it('checks point in polygon', function($lat, $lng, $expected) {
    $polygon = new Polygon([
        new Coordinates(51.5074, -0.1278), // London City Center
        new Coordinates(51.5155, -0.1419), // Oxford Circus
        new Coordinates(51.5025, -0.1457), // Victoria
        new Coordinates(51.5081, -0.1351), // Intersection point (near Soho)
        new Coordinates(51.5074, -0.1278), // Closing the loop
    ]);

    $point = new Coordinates($lat, $lng);

    expect($polygon->pointInPolygon($point))->toBe($expected);
})->with([
    'outside' => [51.5200, -0.1000, false], // Angel, London
    'on boundary' => [51.5110, -0.1380, true], // Midpoint on boundary between London Center and Oxford Circus
    'inside' => [51.5100, -0.1350, true], // Soho
    'on vertex' => [51.5074, -0.1278, true], // London City Center
    'intersection' => [51.5081, -0.1351, true], // Soho
]);
