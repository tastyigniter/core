<?php

namespace Igniter\Tests\Flame\Database\Relations;

use Igniter\System\Models\Country;
use Igniter\System\Models\Currency;

it('associates and dissociates model correctly', function() {
    Country::flushEventListeners();
    $country = Country::factory()->create();
    $currency = Currency::factory()->create();
    $builder = $country->currency();
    $builder->add($currency);

    expect($currency->country_id)->toBe($builder->getSimpleValue());

    $builder->remove($currency);

    expect($currency->country_id)->toBeNull();
});

it('sets simple value with null', function() {
    Country::flushEventListeners();
    $country = Country::factory()->create();
    $country->currency()->setSimpleValue(null);
    $country->save();

    expect($country->currency)->toBeNull()
        ->and($country->currency()->setSimpleValue([]))->toBeNull();
});

it('sets simple value with model instance', function() {
    Country::flushEventListeners();
    $country = Country::factory()->create();
    $currency = Currency::factory()->create();
    $country->currency()->setSimpleValue($currency);
    $country->save();

    expect($country->country_id)->toBe($currency->country_id);

    $country->currency()->setSimpleValue($currency);
    $country->save();
});

it('sets simple value with id', function() {
    Country::flushEventListeners();
    $country = Country::factory()->create();
    $currency = Currency::factory()->create();
    $country->currency()->setSimpleValue($currency->currency_id);
    $country->save();

    expect($country->country_id)->toBe($currency->fresh()->country_id);
});

it('returns null when parent does not exists in getResults', function() {
    expect((new Country)->currency()->getResults())->toBeNull();
});
