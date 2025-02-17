<?php

declare(strict_types=1);

namespace Igniter\Tests\System\Models;

use Igniter\System\Models\Concerns\Defaultable;
use Igniter\System\Models\Concerns\HasCountry;
use Igniter\System\Models\Concerns\Switchable;
use Igniter\System\Models\Country;
use Igniter\System\Models\Currency;

it('returns enabled currencies in dropdown options', function() {
    $country = Country::factory()->create();
    Currency::factory()->for($country, 'country')->create([
        'currency_name' => 'Currency 1',
        'currency_code' => 'CUR1',
        'currency_symbol' => '$',
        'currency_status' => 1,
    ]);
    Currency::factory()->for($country, 'country')->create([
        'currency_name' => 'Currency 2',
        'currency_code' => 'CUR2',
        'currency_symbol' => '€',
        'currency_status' => 0,
    ]);

    expect(Currency::getDropdownOptions())->toContain($country->country_name.' - CUR - $');
});

it('returns the converter dropdown options', function() {
    expect(Currency::getConverterDropdownOptions())->toEqual([
        'openexchangerates' => 'lang:igniter::system.settings.text_openexchangerates',
        'fixerio' => 'lang:igniter::system.settings.text_fixerio',
    ]);
});

it('getters returns values correctly', function() {
    $currency = Currency::factory()->create();

    expect($currency->getId())->toBe($currency->currency_id)
        ->and($currency->getName())->toBe($currency->currency_name)
        ->and($currency->getCode())->toBe($currency->currency_code)
        ->and($currency->getSymbol())->toBe($currency->currency_symbol)
        ->and($currency->getSymbolPosition())->toBe($currency->symbol_position);
});

it('returns defaultable name as currency name', function() {
    $currency = new Currency(['currency_name' => 'Currency 1']);
    expect($currency->defaultableName())->toBe('Currency 1');
});

it('updates currency rate', function() {
    $currency = Currency::factory()->create(['currency_name' => 'Currency 1', 'currency_rate' => 1.0]);
    $currency->updateRate(1.5);

    expect($currency->getRate())->toBe(1.5);
});

it('returns correct currency format with symbol at the end', function() {
    $currency = new Currency([
        'currency_symbol' => '$',
        'symbol_position' => true,
        'thousand_sign' => ',',
        'decimal_sign' => '.',
        'decimal_position' => 2,
    ]);

    expect($currency->getFormat())->toBe('1,0.00$');
});

it('returns correct currency format with symbol at the beginning', function() {
    $currency = new Currency([
        'currency_symbol' => '$',
        'symbol_position' => false,
        'thousand_sign' => ',',
        'decimal_sign' => '.',
        'decimal_position' => 2,
    ]);

    expect($currency->getFormat())->toBe('$1,0.00');
});

it('configures model correctly', function() {
    $currency = new Currency;

    expect(class_uses_recursive($currency))
        ->toContain(Defaultable::class)
        ->toContain(HasCountry::class)
        ->toContain(Switchable::class)
        ->and($currency->getTable())->toBe('currencies')
        ->and($currency->getKeyName())->toBe('currency_id')
        ->and($currency->getGuarded())->toBe([])
        ->and($currency->getCasts())->toEqual([
            'currency_id' => 'int',
            'country_id' => 'integer',
            'currency_rate' => 'float',
            'symbol_position' => 'boolean',
            'is_default' => 'boolean',
        ])
        ->and($currency->relation['belongsTo'])->toEqual([
            'country' => Country::class,
        ])
        ->and($currency->timestamps)->toBeTrue();
});
