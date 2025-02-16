<?php

declare(strict_types=1);

namespace Igniter\Tests\Flame\Geolite;

use Igniter\Flame\Geolite\Circle;
use Igniter\Flame\Geolite\Model\Coordinates;

it('returns the correct radius', function() {
    $coordinate = new Coordinates(10, 20);
    $circle = new Circle($coordinate, 100);
    expect($circle->getRadius())->toBe(100)
        ->and($circle->getCoordinates()->first())->toEqual($coordinate);
});

it('returns the correct geometry type', function() {
    $circle = new Circle([10, 20], 100);
    $circle->setCoordinate(new Coordinates(10, 20));
    expect($circle->getGeometryType())->toBe('CIRCLE')
        ->and($circle->getBounds())->toBeNull();
});

it('returns the correct precision', function() {
    $circle = new Circle(new Coordinates(10, 20), 100);
    expect($circle->getPrecision())->toBe(8);
});

it('sets and returns the correct precision', function() {
    $circle = new Circle(new Coordinates(10, 20), 100);
    $circle->setPrecision(5);
    expect($circle->getPrecision())->toBe(5);
});

it('returns the correct coordinate', function() {
    $coordinate = new Coordinates(10, 20);
    $circle = new Circle($coordinate, 100);
    expect($circle->getCoordinate())->toBe($coordinate);
});

it('returns true when the circle is empty', function() {
    $coordinate = new Coordinates(10, 20);
    $circle = new Circle($coordinate, 0);
    expect($circle->isEmpty())->toBeTrue();
});

it('returns false when the circle is not empty', function() {
    $coordinate = new Coordinates(10, 20);
    $circle = new Circle($coordinate, 100);
    expect($circle->isEmpty())->toBeFalse();
});

it('returns true when a point is within the radius', function() {
    $coordinate = new Coordinates(51.5074, -0.1278);
    $circle = new Circle($coordinate, 5000);
    $circle->distanceUnit('mi');
    $point = new Coordinates(51.5014, -0.1419);
    expect($circle->pointInRadius($point))->toBeTrue();
});

it('returns false when a point is outside the radius', function() {
    $coordinate = new Coordinates(51.5074, -0.1278);
    $circle = new Circle($coordinate, 1000);
    $circle->distanceUnit('mi');
    $point = new Coordinates(51.5550, -0.2795);
    expect($circle->pointInRadius($point))->toBeFalse();
});
