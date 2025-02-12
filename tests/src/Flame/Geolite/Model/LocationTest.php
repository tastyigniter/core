<?php

namespace Igniter\Tests\Flame\Geolite\Model;

use Igniter\Flame\Geolite\Model\AdminLevelCollection;
use Igniter\Flame\Geolite\Model\Location;

it('creates location from array with valid data', function() {
    $data = [
        'providedBy' => 'TestProvider',
        'latitude' => 40.7128,
        'longitude' => -74.0060,
        'streetNumber' => '123',
        'streetName' => 'Main St',
        'locality' => 'New York',
        'postalCode' => '10001',
        'countryName' => 'USA',
        'countryCode' => 'US',
        'adminLevels' => [
            ['level' => '1', 'name' => ''],
            ['level' => '', 'code' => 'NY'],
        ],
    ];
    $location = Location::createFromArray($data)
        ->setValue('custom', 'What?')
        ->setTimezone('America/New_York')
        ->withFormattedAddress('123 Main St, New York, NY 10001');

    expect($location->getProvidedBy())->toBe('TestProvider')
        ->and($location->getCoordinates()->getLatitude())->toBe(40.7128)
        ->and($location->getCoordinates()->getLongitude())->toBe(-74.0060)
        ->and($location->getStreetNumber())->toBe('123')
        ->and($location->getStreetName())->toBe('Main St')
        ->and($location->getLocality())->toBe('New York')
        ->and($location->getPostalCode())->toBe('10001')
        ->and($location->getCountryName())->toBe('USA')
        ->and($location->getCountryCode())->toBe('US')
        ->and($location->getTimezone())->toBe('America/New_York')
        ->and($location->isValid())->toBeTrue()
        ->and($location->format())->toBe('123 Main St New York 10001')
        ->and($location->getFormattedAddress())->toBe('123 Main St, New York, NY 10001')
        ->and($location->getValue('custom'))->toBe('What?');
});

it('sets bounds correctly', function() {
    $location = new Location('TestProvider');
    $location->setBounds(10, 20, 30, 40);
    expect($location->getBounds()->toArray())->toBe([
        'south' => 10.0,
        'west' => 20.0,
        'north' => 30.0,
        'east' => 40.0,
    ]);
});

it('sets coordinates correctly', function() {
    $location = new Location('TestProvider');
    expect($location->hasCoordinates())->toBeFalse();
    $location->setCoordinates(40.7128, -74.0060);
    expect($location->getCoordinates()->getLatitude())->toBe(40.7128)
        ->and($location->getCoordinates()->getLongitude())->toBe(-74.0060);
});

it('returns correct array representation', function() {
    $location = new Location('TestProvider', [
        'latitude' => 40.7128,
        'longitude' => -74.0060,
        'streetNumber' => '123',
        'streetName' => 'Main St',
        'locality' => 'New York',
        'postalCode' => '10001',
        'countryName' => 'USA',
        'countryCode' => 'US',
        'timezone' => 'America/New_York',
        'bounds' => [
            'south' => 10,
            'west' => 15,
            'north' => 20,
            'east' => 25,
        ],
    ]);
    $location->setAdminLevels(new AdminLevelCollection([]));
    $location->addAdminLevel(1, 'Country', 'US');

    expect($location->toArray())->toBe([
        'providedBy' => 'TestProvider',
        'latitude' => 40.7128,
        'longitude' => -74.0060,
        'bounds' => [
            'south' => 10.0,
            'west' => 15.0,
            'north' => 20.0,
            'east' => 25.0,
        ],
        'streetNumber' => '123',
        'streetName' => 'Main St',
        'postalCode' => '10001',
        'locality' => 'New York',
        'subLocality' => null,
        'adminLevels' => [
            1 => ['name' => 'Country', 'code' => 'US', 'level' => 1],
        ],
        'countryName' => 'USA',
        'countryCode' => 'US',
        'timezone' => 'America/New_York',
    ]);
});
