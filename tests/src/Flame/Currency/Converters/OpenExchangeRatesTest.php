<?php

declare(strict_types=1);

namespace Igniter\Tests\Flame\Currency\Converters;

use Igniter\Flame\Currency\Converters\OpenExchangeRates;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

it('returns correct converter details', function() {
    $converter = new OpenExchangeRates;

    expect($converter->converterDetails())->toEqual([
        'name' => 'Open Exchange Rates',
        'description' => 'Conversion services provided by Open Exchange Rates.',
    ]);
});

it('returns empty rates when access key is missing', function() {
    $converter = new OpenExchangeRates(['apiKey' => '']);

    expect($converter->getExchangeRates('USD', ['GBP', 'EUR']))->toBeEmpty();
});

it('returns exchange rates successfully', function() {
    Http::fake([
        'https://openexchangerates.org/api/latest.json?app_id=test&base=USD&symbols=GBP,EUR' => Http::response([
            'rates' => [
                'GBP' => 0.75,
                'EUR' => 0.85,
            ],
        ]),
    ]);
    $converter = new OpenExchangeRates(['apiKey' => 'test']);

    expect($converter->getExchangeRates('USD', ['GBP', 'EUR']))->toEqual([
        'GBP' => 0.75,
        'EUR' => 0.85,
    ]);
});

it('logs error when API request fails', function() {
    Http::fake([
        'https://openexchangerates.org/api/latest.json?app_id=test&base=USD&symbols=GBP,EUR' => Http::response([
            'error' => true,
            'description' => 'An error occurred',
        ]),
    ]);
    config(['currency.ratesCacheDuration' => 60]);
    Log::shouldReceive('info')->once()->with(
        'An error occurred',
    );

    $converter = new OpenExchangeRates(['apiKey' => 'test']);
    $converter->getExchangeRates('USD', ['GBP', 'EUR']);
});
