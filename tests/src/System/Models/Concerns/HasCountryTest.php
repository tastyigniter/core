<?php

declare(strict_types=1);

namespace Igniter\Tests\System\Models\Concerns;

use Igniter\Flame\Database\Model;
use Igniter\System\Models\Concerns\HasCountry;
use Igniter\System\Models\Country;
use Igniter\System\Models\Currency;

it('returns country relation name', function() {
    $model = new class extends Model
    {
        use HasCountry;

        public const COUNTRY_RELATION = 'const_is_default';

        public function testCountryRelationName(): string
        {
            return $this->getCountryRelationName();
        }
    };

    expect($model->testCountryRelationName())->toBe('const_is_default');
});

it('saves model with single relation type country', function() {
    $model = Currency::factory()->create();

    $country = Country::factory()->create();
    $model->country()->associate($country)->save();

    expect($model->country_id)->toBe($country->getKey());
});

it('filters query by country with single relation type', function() {
    $country = Country::factory()->create();
    Currency::factory()->for($country, 'country')->create();

    $result = Currency::whereCountry($country->getKey())->first();

    expect($result->country_id)->toBe($country->getKey());
});
