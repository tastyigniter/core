<?php

namespace Igniter\Tests\Flame\Geolite;

use Igniter\Flame\Geolite\AddressMatch;
use Igniter\Flame\Geolite\Circle;
use Igniter\Flame\Geolite\Distance;
use Igniter\Flame\Geolite\Geolite;
use Igniter\Flame\Geolite\Model\Coordinates;
use Igniter\Flame\Geolite\Polygon;
use Igniter\Flame\Geolite\Vertex;

it('creates a distance instance', function() {
    expect(\Igniter\Flame\Geolite\Facades\Geolite::distance())->toBeInstanceOf(Distance::class);
});

it('creates a circle instance with coordinates array', function() {
    $circle = resolve(Geolite::class)->circle([10, 20], 100);
    expect($circle)->toBeInstanceOf(Circle::class)
        ->and($circle->getRadius())->toBe(100);
});

it('creates a circle instance with coordinates object', function() {
    $coordinates = new Coordinates(10, 20);
    $circle = resolve(Geolite::class)->circle($coordinates, 100);
    expect($circle)->toBeInstanceOf(Circle::class)
        ->and($circle->getRadius())->toBe(100);
});

it('creates a polygon instance with coordinates array', function() {
    $polygon = resolve(Geolite::class)->polygon([new Coordinates(10, 20), new Coordinates(15, 25)]);
    expect($polygon)->toBeInstanceOf(Polygon::class);
});

it('creates a vertex instance', function() {
    expect(resolve(Geolite::class)->vertex())->toBeInstanceOf(Vertex::class);
});

it('creates a coordinates instance', function() {
    $coordinates = resolve(Geolite::class)->coordinates(10, 20);
    $otherCoordinates = resolve(Geolite::class)->coordinates(0, 0);
    $otherCoordinates->setLatitude(15);
    $otherCoordinates->setLongitude(25);
    expect($coordinates)->toBeInstanceOf(Coordinates::class)
        ->and($coordinates->getLatitude())->toBe(10.0)
        ->and($coordinates->getLongitude())->toBe(20.0)
        ->and($coordinates->getPrecision())->toBe(8)
        ->and($coordinates->isEqual($otherCoordinates))->toBeFalse()
        ->and($otherCoordinates->toArray())->toBe([15, 25]);
});

it('creates an address match instance', function() {
    $components = [['type' => 'street', 'value' => 'Main St']];
    $addressMatch = resolve(Geolite::class)->addressMatch($components);
    expect($addressMatch)->toBeInstanceOf(AddressMatch::class);
});
