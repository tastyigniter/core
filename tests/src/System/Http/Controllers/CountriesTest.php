<?php

namespace Igniter\Tests\System\Http\Controllers;

use Igniter\System\Models\Country;

it('loads countries page', function() {
    actingAsSuperUser()
        ->get(route('igniter.system.countries'))
        ->assertOk();
});

it('loads create country page', function() {
    actingAsSuperUser()
        ->get(route('igniter.system.countries'))
        ->assertOk();
});

it('loads edit country page', function() {
    $country = Country::factory()->create();

    actingAsSuperUser()
        ->get(route('igniter.system.countries', ['slug' => 'edit/'.$country->getKey()]))
        ->assertOk();
});

it('loads country preview page', function() {
    $country = Country::factory()->create();

    actingAsSuperUser()
        ->get(route('igniter.system.countries', ['slug' => 'edit/'.$country->getKey()]))
        ->assertOk();
});

it('sets a default country', function() {
    $country = Country::factory()->create();

    actingAsSuperUser()
        ->post(route('igniter.system.countries'), [
            'default' => $country->getKey(),
        ], [
            'X-Requested-With' => 'XMLHttpRequest',
            'X-IGNITER-REQUEST-HANDLER' => 'onSetDefault',
        ]);

    Country::clearDefaultModel();
    expect(Country::getDefaultKey())->toBe($country->getKey());
});

it('creates country', function() {
    actingAsSuperUser()
        ->post(route('igniter.system.countries', ['slug' => 'create']), [
            'country_name' => 'Test United States',
            'priority' => 1,
            'iso_code_2' => 'US',
            'iso_code_3' => 'USA',
            'status' => 1,
        ], [
            'X-Requested-With' => 'XMLHttpRequest',
            'X-IGNITER-REQUEST-HANDLER' => 'onSave',
        ]);

    expect(Country::where('country_name', 'Test United States')->exists())->toBeTrue();
});

it('updates country', function() {
    $country = Country::factory()->create();

    actingAsSuperUser()
        ->post(route('igniter.system.countries', ['slug' => 'edit/'.$country->getKey()]), [
            'country_name' => 'United States',
            'priority' => 1,
            'iso_code_2' => 'US',
            'iso_code_3' => 'USA',
            'status' => 1,
        ], [
            'X-Requested-With' => 'XMLHttpRequest',
            'X-IGNITER-REQUEST-HANDLER' => 'onSave',
        ]);

    expect(Country::find($country->getKey())->country_name)->toBe('United States');
});

it('deletes country', function() {
    $country = Country::factory()->create();

    actingAsSuperUser()
        ->post(route('igniter.system.countries', ['slug' => 'edit/'.$country->getKey()]), [], [
            'X-Requested-With' => 'XMLHttpRequest',
            'X-IGNITER-REQUEST-HANDLER' => 'onDelete',
        ]);

    expect(Country::find($country->getKey()))->toBeNull();
});

it('deletes multiple countries', function() {
    $countries = Country::factory()->count(3)->create();

    actingAsSuperUser()
        ->post(route('igniter.system.countries'), [
            'checked' => $countries->pluck('country_id')->all(),
        ], [
            'X-Requested-With' => 'XMLHttpRequest',
            'X-IGNITER-REQUEST-HANDLER' => 'onDelete',
        ]);

    expect(Country::whereIn('country_id', $countries->pluck('country_id')->all())->exists())->toBeFalse();
});
