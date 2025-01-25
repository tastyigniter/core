<?php

namespace Igniter\Tests\System\Models;

use Igniter\Flame\Database\Traits\Sortable;
use Igniter\System\Models\Concerns\Defaultable;
use Igniter\System\Models\Concerns\Switchable;
use Igniter\System\Models\Country;

it('returns enabled countries in dropdown options', function() {
    $options = Country::getDropdownOptions();

    expect($options->isNotEmpty())->toBeTrue();
});

it('returns defaultable name as country name', function() {
    $country = new Country(['country_name' => 'Country 1']);

    $defaultableName = $country->defaultableName();

    expect($defaultableName)->toBe('Country 1');
});

it('sorts countries by priority', function() {
    Country::create(['country_name' => 'Country 1', 'priority' => 2]);
    Country::create(['country_name' => 'Country 2', 'priority' => 1]);

    $sortedCountries = Country::sorted()->get();

    expect($sortedCountries->first()->country_name)->toBe('Country 2');
});

it('configures model correctly', function() {
    $country = new Country;

    expect(class_uses_recursive($country))
        ->toContain(Defaultable::class)
        ->toContain(Sortable::class)
        ->toContain(Switchable::class)
        ->and($country->getTable())->toBe('countries')
        ->and($country->getKeyName())->toBe('country_id')
        ->and($country->getGuarded())->toBe([])
        ->and($country->getCasts())->toEqual([
            'country_id' => 'int',
            'priority' => 'integer',
            'is_default' => 'boolean',
        ])
        ->and($country->relation['hasOne'])->toEqual([
            'currency' => \Igniter\System\Models\Currency::class,
        ])
        ->and($country->timestamps)->toBeTrue();
});
