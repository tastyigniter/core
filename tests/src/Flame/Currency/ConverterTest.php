<?php

namespace Igniter\Tests\Flame\Currency\Console;

use Igniter\Flame\Currency\Converter;
use Igniter\Flame\Currency\Converters\FixerIO;
use Igniter\Flame\Currency\Converters\OpenExchangeRates;
use Igniter\System\Models\Currency;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;

it('retrieves exchange rates for given currencies', function() {
    config(['igniter-currency.converters.openexchangerates.apiKey' => 'test']);
    Http::fake([
        'https://openexchangerates.org/api/latest.json?app_id=test&base=USD&symbols=GBP,EUR' => Http::response([
            'rates' => [
                'GBP' => 0.75,
                'EUR' => 0.85,
            ],
        ]),
    ]);
    $currencies = new Collection([
        new Currency(['currency_code' => 'GBP']),
        new Currency(['currency_code' => 'EUR']),
    ]);

    expect(resolve(Converter::class)->getExchangeRates('USD', $currencies))->toBe(['GBP' => 0.75, 'EUR' => 0.85]);
});

it('returns default driver when no driver is specified', function() {
    expect(resolve(Converter::class)->getDefaultDriver())->toBe('openexchangerates');
});

it('creates OpenExchangeRates driver', function() {
    $config = [
        'class' => OpenExchangeRates::class,
    ];
    config(['igniter-currency.converters.openexchangerates' => $config]);

    expect(resolve(Converter::class)->createOpenExchangeRatesDriver())->toBeInstanceOf(OpenExchangeRates::class);
});

it('creates FixerIO driver', function() {
    $config = [
        'class' => FixerIO::class,
    ];
    config(['igniter-currency.converters.fixerio' => $config]);

    expect(resolve(Converter::class)->createFixerIODriver())->toBeInstanceOf(FixerIO::class);
});
