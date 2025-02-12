<?php

namespace Igniter\Tests\Flame\Geolite;

use Igniter\Flame\Geolite\Distance;
use Igniter\Flame\Geolite\Exception\GeoliteException;
use Igniter\Flame\Geolite\Model\Coordinates;
use Igniter\Flame\Geolite\Model\Ellipsoid;

beforeEach(function() {
    $this->ellipsoid = Ellipsoid::createFromName();
});

it('returns the correct flat distance', function() {
    $from = new Coordinates(10, 20, $this->ellipsoid);
    $to = new Coordinates(15, 25, $this->ellipsoid);
    $distance = (new Distance())->setFrom($from)->setTo($to)->in('meters');
    expect(round($distance->flat(), 2))->toBe(777874.22)
        ->and($this->ellipsoid->getName())->toBe('WGS 84')
        ->and(round($this->ellipsoid->getArithmeticMeanRadius(), 2))->toBe(6371007.77)
        ->and($this->ellipsoid->getAvailableEllipsoidNames())->toBeString();
});

it('returns the correct great circle distance', function() {
    $from = new Coordinates(10, 20, $this->ellipsoid);
    $to = new Coordinates(15, 25, $this->ellipsoid);
    $distance = (new Distance())->setFrom($from)->setTo($to)->in('meters');
    expect(round($distance->greatCircle(), 2))->toBe(777730.30);
});

it('returns the correct haversine distance', function() {
    $from = new Coordinates(10, 20, $this->ellipsoid);
    $to = new Coordinates(15, 25, $this->ellipsoid);
    $distance = (new Distance())->setFrom($from)->setTo($to)->in('meters');
    expect(round($distance->haversine(), 2))->toBe(777730.30);
});

it('returns the correct vincenty distance', function() {
    $from = new Coordinates(10, 20, $this->ellipsoid);
    $to = new Coordinates(15, 25, $this->ellipsoid);
    $distance = (new Distance())->setFrom($from)->setTo($to)->in('meters');
    expect(round($distance->vincenty(), 2))->toBe(775316.28);
});

it('converts distance correctly', function() {
    $from = new Coordinates(10, 20, $this->ellipsoid);
    $to = new Coordinates(15, 25, $this->ellipsoid);
    $distance = (new Distance())->setFrom($from)->setTo($to)->in('kilometers');
    expect(round($distance->flat(), 2))->toBe(777874.22);

    $distance = (new Distance())->setFrom($from)->setTo($to)->in('miles');
    expect(round($distance->flat(), 2))->toBe(777874.22);

    $distance = (new Distance())->setFrom($from)->setTo($to)->in('feet');
    expect(round($distance->flat(), 2))->toBe(777874.22);
});

it('returns default distance in meters when unit is not set', function() {
    $from = new Coordinates(10, 20, $this->ellipsoid);
    $to = new Coordinates(15, 25, $this->ellipsoid);
    $distance = (new Distance())->setFrom($from)->setTo($to);
    expect(round($distance->flat(), 2))->toBe(777874.22);
});

it('returns zero distance for co-incident points', function() {
    $coordinate = new Coordinates(10, 20, $this->ellipsoid);
    $distance = (new Distance())->setFrom($coordinate)->setTo($coordinate)->in('meters');
    expect($distance->vincenty())->toBe(0.0);
});

it('throws exception when vincenty formula fails to converge', function() {
    $from = new Coordinates(0, 0, $this->ellipsoid);
    $to = new Coordinates(0, 180, $this->ellipsoid);
    $distance = (new Distance())->setFrom($from)->setTo($to)->in('meters');
    expect(fn() => $distance->vincenty())->toThrow(GeoliteException::class);
});
