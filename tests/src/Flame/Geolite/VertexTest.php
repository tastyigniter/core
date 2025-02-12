<?php

namespace Igniter\Tests\Flame\Geolite;

use Igniter\Flame\Geolite\Model\Coordinates;
use Igniter\Flame\Geolite\Vertex;

it('sets and gets the from coordinate', function() {
    $vertex = new Vertex;
    $from = new Coordinates(10, 20);
    $vertex->setFrom($from);
    expect($vertex->getFrom())->toBe($from);
});

it('sets and gets the to coordinate', function() {
    $vertex = new Vertex;
    $to = new Coordinates(15, 25);
    $vertex->setTo($to);
    expect($vertex->getTo())->toBe($to);
});

it('calculates the correct gradient', function() {
    $vertex = new Vertex;
    $from = new Coordinates(10, 20);
    $to = new Coordinates(15, 25);
    $vertex->setFrom($from)->setTo($to);
    expect($vertex->getGradient())->toBe(1.0);
});

it('calculates the correct ordinate intercept', function() {
    $vertex = new Vertex;
    $from = new Coordinates(10, 20);
    $to = new Coordinates(15, 25);
    $vertex->setFrom($from)->setTo($to);
    expect($vertex->getOrdinateIntercept())->toBe(10.0);
});

it('calculates the initial bearing correctly', function() {
    $vertex = new Vertex;
    $from = new Coordinates(10, 20);
    $to = new Coordinates(15, 25);
    $vertex->setTo($to)->setFrom($from);
    expect($vertex->initialBearing())->toBe(43);
});

it('calculates the final bearing correctly', function() {
    $vertex = new Vertex;
    $from = new Coordinates(10, 20);
    $to = new Coordinates(15, 25);
    $vertex->setTo($to)->setFrom($from);
    expect($vertex->finalBearing())->toBe(44);
});

it('calculates the initial cardinal direction correctly', function() {
    $vertex = new Vertex;
    $from = new Coordinates(10, 20);
    $to = new Coordinates(15, 25);
    $vertex->setTo($to)->setFrom($from);
    expect($vertex->initialCardinal())->toBe('NE');
});

it('calculates the final cardinal direction correctly', function() {
    $vertex = new Vertex;
    $from = new Coordinates(10, 20);
    $to = new Coordinates(5, 15);
    $vertex->setFrom($from)->setTo($to);
    expect($vertex->finalCardinal())->toBe('SW');
});

it('calculates the middle point correctly', function() {
    $vertex = new Vertex;
    $from = new Coordinates(10, 20);
    $to = new Coordinates(15, 25);
    $vertex->setTo($to)->setFrom($from);
    $middle = $vertex->middle();
    expect(round($middle->getLatitude(), 2))->toBe(12.51)
        ->and(round($middle->getLongitude(), 2))->toBe(22.48);
});

it('calculates the destination point correctly', function() {
    $vertex = new Vertex;
    $from = new Coordinates(10, 20);
    $vertex->setFrom($from);
    $destination = $vertex->destination(45, 1000000);
    expect(round($destination->getLatitude(), 2))->toBe(16.28)
        ->and(round($destination->getLongitude(), 2))->toBe(26.6);
});

it('checks if two vertices are on the same line', function() {
    $vertex1 = new Vertex;
    $vertex2 = new Vertex;
    $from1 = new Coordinates(10, 20);
    $to1 = new Coordinates(15, 25);
    $from2 = new Coordinates(20, 30);
    $to2 = new Coordinates(25, 35);
    $vertex1->setTo($to1)->setFrom($from1);
    $vertex2->setTo($to2)->setFrom($from2);
    expect($vertex1->isOnSameLine($vertex2))->toBeTrue();
});

it('checks if two vertices are not on the same line', function() {
    $vertex1 = new Vertex;
    $vertex2 = new Vertex;
    $from1 = new Coordinates(10, 20);
    $to1 = new Coordinates(15, 25);
    $from2 = new Coordinates(20, 30);
    $to2 = new Coordinates(20, 30);
    $vertex1->setTo($to1)->setFrom($from1);
    $vertex2->setFrom($from2)->setTo($to2);
    expect($vertex1->isOnSameLine($vertex2))->toBeFalse();
});

it('returns true when vertices have the same longitude and null gradients', function() {
    $vertex1 = new Vertex;
    $vertex2 = new Vertex;
    $from1 = new Coordinates(10, 20);
    $to1 = new Coordinates(10, 20);
    $from2 = new Coordinates(25, 20);
    $to2 = new Coordinates(25, 20);
    $vertex1->setTo($to1)->setFrom($from1);
    $vertex2->setFrom($from2)->setTo($to2);
    expect($vertex1->isOnSameLine($vertex2))->toBeTrue()
        ->and($vertex1->getGradient())->toBeNull()
        ->and($vertex1->getOrdinateIntercept())->toBeNull();
});

it('gets the other coordinate correctly', function() {
    $vertex = new Vertex;
    $from = new Coordinates(10, 20);
    $to = new Coordinates(15, 25);
    $other = new Coordinates(20, 30);
    $vertex->setTo($to)->setFrom($from);
    expect($vertex->getOtherCoordinate($from))->toBe($to)
        ->and($vertex->getOtherCoordinate($to))->toBe($from)
        ->and($vertex->getOtherCoordinate($other))->toBeNull();
});

it('calculates the determinant correctly', function() {
    $vertex1 = new Vertex;
    $vertex2 = new Vertex;
    $from1 = new Coordinates(10, 20);
    $to1 = new Coordinates(15, 25);
    $from2 = new Coordinates(20, 30);
    $to2 = new Coordinates(25, 35);
    $vertex1->setFrom($from1)->setTo($to1);
    $vertex2->setFrom($from2)->setTo($to2);
    expect($vertex1->getDeterminant($vertex2))->toBe('0.00000000');
});
