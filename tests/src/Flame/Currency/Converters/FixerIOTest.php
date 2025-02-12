<?php

namespace Igniter\Tests\Flame\Currency\Converters;

use Igniter\Flame\Currency\Converters\FixerIO;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

it('returns correct converter details', function() {
    $converter = new FixerIO;

    expect($converter->converterDetails())->toEqual([
        'name' => 'Fixer.io',
        'description' => 'Conversion services by Fixer.io',
    ]);
});

it('returns empty rates when access key is missing', function() {
    $converter = new FixerIO(['apiKey' => '']);

    expect($converter->getExchangeRates('USD', ['GBP', 'EUR']))->toBeEmpty();
});

it('returns exchange rates successfully', function() {
    Http::fake([
        'http://data.fixer.io/*' => Http::response([
            'success' => true,
            'rates' => [
                'GBP' => 0.75,
                'EUR' => 0.85,
            ],
        ]),
    ]);
    $converter = new FixerIO(['apiKey' => 'test']);

    expect($converter->getExchangeRates('USD', ['GBP', 'EUR']))->toEqual([
        'GBP' => 0.75,
        'EUR' => 0.85,
    ]);
});

it('logs error when API request fails', function() {
    Http::fake([
        'http://data.fixer.io/api/*' => Http::response([
            'success' => false,
        ]),
    ]);
    config(['currency.ratesCacheDuration' => 60]);
    Log::shouldReceive('debug')->once()
        ->with('An error occurred when requesting currency exchange rates from fixer.io, check your api key.');

    $converter = new FixerIO(['apiKey' => 'test']);
    $converter->getExchangeRates('USD', ['GBP', 'EUR']);
});
