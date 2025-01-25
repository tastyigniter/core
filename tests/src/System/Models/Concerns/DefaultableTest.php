<?php

namespace Igniter\Tests\System\Models\Concerns;

use Igniter\Flame\Database\Model;
use Igniter\System\Models\Concerns\Defaultable;
use Igniter\System\Models\Country;
use Igniter\System\Models\Currency;
use Illuminate\Validation\ValidationException;

it('set as default model on create', function() {
    $model1 = Country::factory()->createQuietly(['is_default' => true]);
    $model2 = Country::factory()->create(['is_default' => true]);

    expect(Country::find($model1->getKey())->isDefault())->toBeFalse()
        ->and(Country::find($model2->getKey())->isDefault())->toBeTrue();
});

it('set as default model on update', function() {
    $model1 = Country::factory()->createQuietly(['is_default' => true]);
    $model2 = Country::factory()->createQuietly(['is_default' => false]);

    $model2->update(['is_default' => true]);

    expect(Country::find($model1->getKey())->isDefault())->toBeFalse()
        ->and(Country::find($model2->getKey())->isDefault())->toBeTrue();
});

it('updates the default model', function() {
    $model1 = Country::factory()->createQuietly(['is_default' => true]);
    $model2 = Country::factory()->createQuietly(['is_default' => false]);

    Country::clearDefaultModels();
    Country::updateDefault($model2->getKey());

    expect(Country::find($model1->getKey())->isDefault())->toBeFalse()
        ->and(Country::getDefaultKey())->toBe($model2->getKey());
});

it('gets the default model', function() {
    Country::factory()->createQuietly(['is_default' => true]);
    Country::factory()->createQuietly(['is_default' => false]);

    $default = Country::getDefault();
    expect($default)->toEqual(Country::getDefault())
        ->and($default->isDefault())->toBeTrue()
        ->and($default->defaultableKeyName())->toBe('country_id');
});

it('clears specific default model', function() {
    Country::factory()->createQuietly(['is_default' => true]);
    Country::getDefault();

    expect(Country::$defaultModels[Country::class])->not()->toBeEmpty();

    Country::clearDefaultModel();

    expect(Country::$defaultModels)->toBeEmpty();
});

it('clears all default models', function() {
    Country::factory()->createQuietly(['is_default' => true]);
    Currency::factory()->createQuietly(['is_default' => true]);
    Country::getDefault();
    Currency::getDefault();

    expect(Country::$defaultModels[Country::class])->not()->toBeEmpty()
        ->and(Currency::$defaultModels[Currency::class])->not()->toBeEmpty();

    Country::clearDefaultModels();

    expect(Country::$defaultModels)->toBeEmpty();
});

it('throws validation exception when making default without switchable', function() {
    $model = Country::factory()->createQuietly(['status' => false]);

    expect(fn() => $model->makeDefault())->toThrow(ValidationException::class);
});

it('returns defaultable column name', function() {
    $model = new class extends Model
    {
        use Defaultable;

        public const DEFAULTABLE_COLUMN = 'const_is_default';
    };

    expect($model->defaultableGetColumn())->toBe('const_is_default');
});

it('returns defaultable attribute name value', function() {
    $model = new class extends Model
    {
        use Defaultable;

        protected $attributes = ['name' => 'default_name'];
    };

    expect($model->defaultableName())->toBe('default_name');
});

it('applies defaultable scope correctly', function() {
    Country::factory()->createQuietly(['is_default' => true]);
    Country::factory()->createQuietly(['is_default' => false]);

    $defaultModelQuery = Country::make()->defaultable()->whereIsDefault();
    $nonDefaultModelQuery = Country::make()->defaultable()->whereNotDefault();

    expect($defaultModelQuery->toSql())->toContain('where `countries`.`is_default` = ?')
        ->and($nonDefaultModelQuery->toSql())->toContain('where `countries`.`is_default` != ?');
});
