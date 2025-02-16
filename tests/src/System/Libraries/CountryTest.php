<?php

declare(strict_types=1);

use Igniter\System\Libraries\Country;
use Igniter\System\Models\Country as CountryModel;

it('formats address correctly', function() {
    $address = [
        'address_1' => '123 Street',
        'address_2' => 'Apt 4B',
        'city' => 'City',
        'postcode' => '12345',
        'state' => 'State',
        'country' => 'Country',
    ];
    $expectedWithLineBreaks = '123 Street<br />Apt 4B<br />City 12345<br />State<br />Country';
    $expectedWithoutLineBreaks = '123 Street, Apt 4B, City 12345, State, Country';

    $countryLibrary = new Country;

    expect($countryLibrary->addressFormat($address))->toBe($expectedWithLineBreaks)
        ->and($countryLibrary->addressFormat($address, false))->toBe($expectedWithoutLineBreaks);
});

it('formats address correctly using country id', function() {
    $country = CountryModel::factory()->create();
    $address = [
        'address_1' => '123 Street',
        'address_2' => 'Apt 4B',
        'city' => 'City',
        'postcode' => '12345',
        'state' => 'State',
        'country_id' => $country->getKey(),
    ];

    $countryLibrary = new Country;

    expect($countryLibrary->addressFormat($address))
        ->toBe('123 Street<br />Apt 4B<br />City 12345<br />State<br />'.$country->country_name);
});

it('formats address correctly using model', function() {
    $address = \Igniter\User\Models\Address::factory()->create([
        'address_1' => '123 Street',
        'address_2' => 'Apt 4B',
        'city' => 'City',
        'postcode' => '12345',
        'state' => 'State',
    ]);

    $countryName = $address->country->country_name;
    $countryLibrary = new Country;

    expect($countryLibrary->addressFormat($address))
        ->toBe('123 Street<br />Apt 4B<br />City 12345<br />State<br />'.$countryName);
});

it('formats address correctly using custom format', function() {
    $address = [
        'address_1' => '123 Street',
        'address_2' => 'Apt 4B',
        'city' => 'City',
        'postcode' => '12345',
        'state' => 'State',
        'country' => 'Country',
        'format' => '{address_1}, {address_2}, {city}, {postcode}, {state}, {country}',
    ];

    $countryLibrary = new Country;

    expect($countryLibrary->addressFormat($address, false))->toBe('123 Street, Apt 4B, City, 12345, State, Country');
});

it('returns country name by id correctly', function() {
    $country = CountryModel::factory()->create([
        'country_name' => 'Test Country',
    ]);

    $countryLibrary = new Country;
    expect($countryLibrary->getCountryNameById($country->getKey()))->toBe('Test Country')
        ->and($countryLibrary->getCountryNameById(1000))->toBeNull();
});

it('returns country code by id correctly', function() {
    $country = CountryModel::factory()->create([
        'iso_code_2' => 'TQ',
        'iso_code_3' => 'TQT',
    ]);

    $countryLibrary = new Country;
    expect($countryLibrary->getCountryCodeById(1000))->toBeNull()
        ->and($countryLibrary->getCountryCodeById($country->getKey(), Country::ISO_CODE_2))->toBe('TQ')
        ->and($countryLibrary->getCountryCodeById($country->getKey(), Country::ISO_CODE_3))->toBe('TQT');
});

it('returns country name by code correctly', function() {
    CountryModel::factory()->create([
        'country_name' => 'Test Country',
        'iso_code_2' => 'TQ',
        'iso_code_3' => 'TQT',
    ]);

    $countryLibrary = new Country;

    expect($countryLibrary->getCountryNameByCode('TQ'))->toBe('Test Country')
        ->and($countryLibrary->getCountryNameByCode('TAAQ'))->toBeNull();
});

it('lists all countries correctly', function() {
    CountryModel::factory()->create([
        'country_name' => 'Test Country',
    ]);

    $countryLibrary = new Country;
    $countries = $countryLibrary->listAll('country_name')->all();

    expect($countries)->toBeArray()
        ->and($countries)->toContain('Test Country')
        ->and($countryLibrary->listAll())->toBeCollection();
});
