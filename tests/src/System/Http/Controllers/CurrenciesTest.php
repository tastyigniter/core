<?php

namespace Igniter\Tests\System\Http\Controllers;

use Igniter\System\Models\Currency;

it('loads currencies page', function() {
    actingAsSuperUser()
        ->get(route('igniter.system.currencies'))
        ->assertOk();
});

it('loads create currency page', function() {
    actingAsSuperUser()
        ->get(route('igniter.system.currencies', ['slug' => 'create']))
        ->assertOk();
});

it('loads edit currency page', function() {
    $currency = Currency::factory()->create();

    actingAsSuperUser()
        ->get(route('igniter.system.currencies', ['slug' => 'edit/'.$currency->getKey()]))
        ->assertOk();
});

it('loads currency preview page', function() {
    $currency = Currency::factory()->create();

    actingAsSuperUser()
        ->get(route('igniter.system.currencies', ['slug' => 'preview/'.$currency->getKey()]))
        ->assertOk();
});

it('sets a default currency', function() {
    $currency = Currency::factory()->create(['currency_status' => 1]);

    actingAsSuperUser()
        ->post(route('igniter.system.currencies'), [
            'default' => $currency->getKey(),
        ], [
            'X-Requested-With' => 'XMLHttpRequest',
            'X-IGNITER-REQUEST-HANDLER' => 'onSetDefault',
        ]);

    Currency::clearDefaultModel();
    expect(Currency::getDefaultKey())->toBe($currency->getKey());
});

it('creates currency', function() {
    actingAsSuperUser()
        ->post(route('igniter.system.currencies', ['slug' => 'create']), [
            'Currency' => [
                'currency_name' => 'Test United States',
                'currency_code' => 'USD',
                'currency_symbol' => '$',
                'country_id' => 1,
                'currency_status' => 1,
            ],
        ], [
            'X-Requested-With' => 'XMLHttpRequest',
            'X-IGNITER-REQUEST-HANDLER' => 'onSave',
        ]);

    expect(Currency::where('currency_name', 'Test United States')->exists())->toBeTrue();
});

it('updates currency', function() {
    $currency = Currency::factory()->create();

    actingAsSuperUser()
        ->post(route('igniter.system.currencies', ['slug' => 'edit/'.$currency->getKey()]), [
            'Currency' => [
                'currency_name' => 'Test United States',
                'currency_code' => 'USD',
                'currency_symbol' => '$',
                'country_id' => 1,
                'currency_status' => 1,
            ],
        ], [
            'X-Requested-With' => 'XMLHttpRequest',
            'X-IGNITER-REQUEST-HANDLER' => 'onSave',
        ]);

    expect(Currency::where('currency_name', 'Test United States')->exists())->toBeTrue();
});

it('deletes currency', function() {
    $currency = Currency::factory()->create();

    actingAsSuperUser()
        ->post(route('igniter.system.currencies', ['slug' => 'edit/'.$currency->getKey()]), [], [
            'X-Requested-With' => 'XMLHttpRequest',
            'X-IGNITER-REQUEST-HANDLER' => 'onDelete',
        ]);

    expect(Currency::find($currency->getKey()))->toBeNull();
});
