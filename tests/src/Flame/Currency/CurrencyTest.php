<?php

declare(strict_types=1);

namespace Igniter\Tests\Flame\Currency;

use Igniter\Flame\Currency\Converter;
use Igniter\Flame\Currency\Currency;
use Igniter\System\Models\Currency as CurrencyModel;

it('converts amount between currencies', function() {
    CurrencyModel::factory()->create([
        'currency_code' => 'ABB',
        'currency_rate' => 1.0,
        'currency_status' => 1,
    ]);
    CurrencyModel::factory()->create([
        'currency_code' => 'ABC',
        'currency_rate' => 0.85,
        'currency_status' => 1,
    ]);

    $currency = resolve(Currency::class);

    expect($currency->convert(100, 'ABB', 'ABC'))->toBe('£85.00')
        ->and($currency->convert(100, 'ABB', 'ABC', false))->toBe(85.0);
});

it('formats value to currency string', function() {
    CurrencyModel::factory()->create([
        'currency_code' => 'ABC',
        'currency_rate' => 0.85,
        'currency_status' => 1,
    ]);

    expect(resolve(Currency::class)->format(1234.56, 'ABC'))->toBe('£1,234.56')
        ->and(resolve(Currency::class)->format(-1234.56, 'ABC'))->toBe('-£1,234.56');
});

it('formats value to currency string with custom formatter', function() {
    config(['igniter-currency.formatter' => 'php_intl']);
    $currency = CurrencyModel::factory()->create([
        'currency_code' => 'ABC',
        'currency_rate' => 0.85,
        'currency_status' => 1,
    ]);

    expect(resolve(Currency::class)->format(1234.56, $currency->getKey()))->toBe('ABC 1,234.56');
});

it('formats value to currency string when thousand sign is missing', function() {
    CurrencyModel::factory()->create([
        'currency_code' => 'ABC',
        'currency_rate' => 0.85,
        'currency_status' => 1,
        'thousand_sign' => '!',
    ]);

    expect(resolve(Currency::class)->format(1234.56, 'ABC'))->toBe('£1234.56');
});

it('returns null for invalid to currency rate', function() {
    CurrencyModel::factory()->create([
        'currency_code' => 'ABC',
        'currency_rate' => 1.0,
        'currency_status' => 1,
    ]);

    expect(resolve(Currency::class)->convert(100, 'ABC', 'INVALID'))->toBeNull();
});

it('formats value to json array', function() {
    $currency = CurrencyModel::factory()->create([
        'currency_code' => 'ABC',
        'currency_status' => 1,
    ]);

    expect(resolve(Currency::class)->formatToJson(1234.56, $currency->getKey()))->toBe([
        'currency' => 'ABC',
        'value' => 1234.56,
    ]);
});

it('sets and gets user currency', function() {
    CurrencyModel::factory()->create([
        'currency_code' => 'ABC',
        'currency_status' => 1,
    ]);

    $currency = resolve(Currency::class);
    $currency->setUserCurrency('ABC');

    expect($currency->getUserCurrency())->toBe('ABC')
        ->and($currency->currency_code)->toBe('ABC')
        ->and($currency->getFormat())->toBeString()
        ->and($currency->hasCurrency('ABC'))->toBeTrue()
        ->and($currency->isActive('ABC'))->toBeTrue()
        ->and($currency->clearCache())->toBeNull()
        ->and($currency->config())->toBeArray();
});

it('updates exchange rates', function() {
    app()->instance('currency.converter', $converter = mock(Converter::class));
    $converter->shouldReceive('getExchangeRates')->andReturn([
        'ABC' => 0.75,
        'ABB' => 0.85,
    ]);
    CurrencyModel::factory()->create([
        'currency_code' => 'ABC',
        'currency_status' => 1,
    ]);
    CurrencyModel::factory()->create([
        'currency_code' => 'ABB',
        'currency_status' => 1,
    ]);

    $currency = resolve(Currency::class);
    $currency->updateRates();

    expect($currency->convert(100, 'ABC', 'ABB'))->toBe('£113.33');
});

it('updates exchanges rates with skip cache', function() {
    app()->instance('currency.converter', $converter = mock(Converter::class));
    $converter->shouldReceive('getExchangeRates')->andReturn([
        'ABC' => 0.75,
        'ABB' => 0.85,
    ]);
    CurrencyModel::factory()->create([
        'currency_code' => 'ABC',
        'currency_status' => 1,
    ]);
    CurrencyModel::factory()->create([
        'currency_code' => 'ABB',
        'currency_status' => 1,
    ]);

    $currency = resolve(Currency::class);
    $currency->updateRates(true);

    expect($currency->convert(100, 'ABC', 'ABB'))->toBe('£113.33');
});
