<?php

namespace Igniter\Tests\Flame\Database\Traits;

use Igniter\Flame\Database\Traits\Purgeable;
use Igniter\Flame\Pagic\Model;
use Igniter\System\Models\Country;
use Igniter\System\Models\Currency;
use LogicException;

it('throws exception if purgeable property is not defined', function() {
    $this->expectException(LogicException::class);
    $this->expectExceptionMessage('You must define a $purgeable property in ');

    new class extends Model
    {
        use Purgeable;
    };
});

it('purges specified attributes', function() {
    $model = new class extends Country
    {
        use Purgeable;

        protected $purgeable = ['custom_attribute'];
    };
    $model->fill([
        'country_name' => 'United States',
        'custom_attribute' => 'value',
    ]);
    $model->custom_attribute = 'value';
    $model->save();

    expect($model->getAttributes())->not->toHaveKey('custom_attribute');
});

it('returns purgeable attributes', function() {
    $model = new class extends Country
    {
        use Purgeable;

        protected $purgeable = ['custom_attribute'];
    };
    $model->fill([
        'country_name' => 'United States',
        'custom_attribute' => 'value',
    ]);

    expect($model->purgeAttributes(['custom_attribute']))->toBe(['country_name' => 'United States'])
        ->and($model->getOriginalPurgeValue('custom_attribute'))->toBe('value');
});

it('restores purged values', function() {
    $model = new class extends Country
    {
        use Purgeable;

        protected $purgeable = ['custom_attribute'];
    };
    $model->fill([
        'country_name' => 'United States',
        'custom_attribute' => 'value',
    ]);
    $model->save();

    $model->restorePurgedValues();

    expect($model->getAttributes())->toHaveKey('custom_attribute', 'value');
});

it('returns true if relation is purgeable', function() {
    $model = new class extends Currency
    {
        public function getPurgeableAttributes()
        {
            return ['country'];
        }
    };
    $result = $model->setAttribute('country', 'value');

    expect($result)->not->toBeNull();
});
