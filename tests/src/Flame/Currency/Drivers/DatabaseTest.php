<?php

namespace Igniter\Tests\Flame\Currency\Drivers;

use Igniter\Flame\Currency\Drivers\Database;
use Igniter\System\Models\Currency;

it('creates a new currency when it does not exist', function() {
    $driver = new Database(['table' => 'currencies']);
    $result = $driver->create([
        'currency_code' => 'ABC',
        'currency_name' => 'United States Dollar',
        'currency_symbol' => 'US$',
        'currency_rate' => 1.0,
    ]);

    expect($result)->toBeTrue();
    $this->assertDatabaseHas('currencies', [
        'currency_code' => 'ABC',
        'currency_name' => 'United States Dollar',
        'currency_symbol' => 'US$',
        'currency_rate' => 1.0,
    ]);
});

it('does not create a new currency when it already exists', function() {
    Currency::factory()->create([
        'currency_code' => 'ABC',
    ]);

    $driver = new Database(['table' => 'currencies']);
    $currency = $driver->create([
        'currency_code' => 'ABC',
        'currency_name' => 'United States Dollar',
        'currency_symbol' => 'US$',
        'currency_rate' => 1.0,
    ]);
    expect($currency)->toBeTrue();
});

it('returns all currencies', function() {
    Currency::factory()->create([
        'currency_code' => 'ABC',
    ]);
    Currency::factory()->create([
        'currency_code' => 'ABD',
        'symbol_position' => 1,
    ]);
    $driver = new Database(['table' => 'currencies']);
    $currencies = $driver->all();

    expect($currencies)->toBeArray()
        ->and($currencies['ABC'])->toHaveKeys([
            'currency_id', 'currency_name', 'currency_code', 'currency_symbol',
            'format', 'currency_rate', 'currency_status',
        ]);
});

it('finds a currency by code', function() {
    $currency = Currency::factory()->create(['currency_status' => 1]);
    $driver = new Database(['table' => 'currencies']);
    $result = $driver->find($currency->currency_code);

    expect($result)->toHaveKeys([
        'currency_id', 'currency_name', 'currency_code', 'currency_symbol',
        'currency_rate', 'currency_status',
    ]);
});

it('updates a currency by code', function() {
    Currency::factory()->create([
        'currency_code' => 'ABC',
        'currency_name' => 'United States Dollar',
        'currency_symbol' => 'US$',
        'currency_rate' => 1.0,
    ]);

    $driver = new Database(['table' => 'currencies']);
    $driver->update('ABC', [
        'currency_name' => 'United Dollar',
        'currency_symbol' => '$',
        'currency_rate' => 2.0,
    ]);

    $this->assertDatabaseHas('currencies', [
        'currency_code' => 'ABC',
        'currency_name' => 'United Dollar',
        'currency_symbol' => '$',
        'currency_rate' => 2.0,
    ]);
});

it('deletes a currency by code', function() {
    Currency::factory()->create([
        'currency_code' => 'ABC',
    ]);

    $driver = new Database(['table' => 'currencies']);
    $driver->delete('ABC');

    $this->assertDatabaseMissing('currencies', [
        'currency_code' => 'ABC',
    ]);
});
