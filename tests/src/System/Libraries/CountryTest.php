<?php

use Igniter\System\Libraries\Country;
use Igniter\System\Models\Country as CountryModel;

it('formats address correctly', function($address, $expectedWithLineBreaks, $expectedWithoutLineBreaks) {
    $country = new Country();

    expect($country->addressFormat($address))->toBe($expectedWithLineBreaks)
        ->and($country->addressFormat($address, false))->toBe($expectedWithoutLineBreaks);
})->with([
    [
        [
            'address_1' => '123 Street',
            'address_2' => 'Apt 4B',
            'city' => 'City',
            'postcode' => '12345',
            'state' => 'State',
            'country' => 'Country',
        ],
        '123 Street<br />Apt 4B<br />City 12345<br />State<br />Country',
        '123 Street, Apt 4B, City 12345, State, Country',
    ],
    [
        [
            'address_1' => '123 Street',
            'city' => 'City',
            'postcode' => '12345',
            'state' => 'State',
            'country' => 'Country',
        ],
        '123 Street<br />City 12345<br />State<br />Country',
        '123 Street, City 12345, State, Country',
    ],
]);

it('gets country name by id correctly', function() {
    $country = CountryModel::factory()->create([
        'country_name' => 'Test Country',
    ]);

    expect((new Country())->getCountryNameById($country->getKey()))->toBe('Test Country');
});

it('gets country code by id correctly', function() {
    $country = CountryModel::factory()->create([
        'iso_code_2' => 'TQ',
        'iso_code_3' => 'TQT',
    ]);

    expect((new Country())->getCountryCodeById($country->getKey(), Country::ISO_CODE_2))->toBe('TQ')
        ->and((new Country())->getCountryCodeById($country->getKey(), Country::ISO_CODE_3))->toBe('TQT');
});

it('gets country name by code correctly', function() {
    CountryModel::factory()->create([
        'country_name' => 'Test Country',
        'iso_code_2' => 'TQ',
        'iso_code_3' => 'TQT',
    ]);

    expect((new Country())->getCountryNameByCode('TQ'))->toBe('Test Country');
});

it('lists all countries correctly', function() {
    CountryModel::factory()->create([
        'country_name' => 'Test Country',
    ]);

    $countries = (new Country())->listAll('country_name')->all();

    expect($countries)->toBeArray()
        ->and($countries)->toContain('Test Country');
});
