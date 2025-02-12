<?php

namespace Igniter\Tests\Flame\Geolite;


use Igniter\Flame\Geolite\AddressMatch;
use Igniter\Flame\Geolite\Model\Location;

it('returns true when components match the location', function($component, $location, $expected) {
    $matchedLocation = new Location('test', $location);
    $addressMatch = new AddressMatch([[$component]]);
    expect($addressMatch->matches($matchedLocation))->toBe($expected);
})->with([
    'street' => [
        ['type' => 'street', 'value' => 'Main St'],
        ['streetName' => 'Main St'],
        true,
    ],
    'no matchingstreet' => [
        ['type' => 'street', 'value' => 'Main St'],
        ['streetName' => 'Elm St'],
        false,
    ],
    'street regex' => [
        ['type' => 'street', 'value' => '/^Main/'],
        ['streetName' => 'Main St'],
        true,
    ],
    'no matchingstreet regex' => [
        ['type' => 'street', 'value' => '/^Main/'],
        ['streetName' => 'Elm St'],
        false,
    ],
    'sub locality' => [
        ['type' => 'sub_locality', 'value' => 'Downtown'],
        ['subLocality' => 'Downtown'],
        true,
    ],
    'no matchingsub locality' => [
        ['type' => 'sub_locality', 'value' => 'Downtown'],
        ['subLocality' => 'Uptown'],
        false,
    ],
    'locality' => [
        ['type' => 'locality', 'value' => 'Springfield'],
        ['locality' => 'Springfield'],
        true,
    ],
    'no matchinglocality' => [
        ['type' => 'locality', 'value' => 'Springfield'],
        ['locality' => 'Shelbyville'],
        false,
    ],
    'admin level 2' => [
        ['type' => 'admin_level_2', 'value' => 'County'],
        ['adminLevels' => [['name' => 'County', 'level' => 2]]],
        true,
    ],
    'no matchingadmin level 2' => [
        ['type' => 'admin_level_2', 'value' => 'County'],
        ['adminLevels' => [['name' => 'City', 'level' => 2]]],
        false,
    ],
    'admin level 1' => [
        ['type' => 'admin_level_1', 'value' => 'State'],
        ['adminLevels' => [['name' => 'State', 'level' => 1]]],
        true,
    ],
    'no matchingadmin level 1' => [
        ['type' => 'admin_level_1', 'value' => 'State'],
        ['adminLevels' => [['name' => 'Province', 'level' => 1]]],
        false,
    ],
    'postal code' => [
        ['type' => 'postal_code', 'value' => '12345'],
        ['postalCode' => '12345'],
        true,
    ],
    'no postal code' => [
        ['type' => 'postal_code', 'value' => '12345'],
        ['postalCode' => ''],
        false,
    ],
    'invalid component value' => [
        ['type' => 'invalid', 'value' => ['invalid']],
        [],
        false,
    ],
    'invalid component type' => [
        ['type' => 'invalid', 'value' => 'Main St'],
        ['streetName' => 'Main St'],
        false,
    ],
]);
