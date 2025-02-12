<?php

namespace Igniter\Tests\Flame\Database\Concerns;

use BadMethodCallException;
use Carbon\Carbon;
use Carbon\Exceptions\InvalidFormatException;
use Igniter\Flame\Database\Concerns\HasAttributes;
use Igniter\Flame\Database\Model;
use Igniter\Tests\Flame\Database\Fixtures\TestEnum;
use Illuminate\Database\Eloquent\Casts\Attribute;

it('adds attribute casts to model', function() {
    $model = new class extends Model
    {
        use HasAttributes;
    };

    $model->addCasts(['attribute' => 'castType']);
    expect($model->getCasts())->toHaveKey('attribute', 'castType');
});

it('retrieves attribute value', function() {
    $model = new class extends Model
    {
        use HasAttributes;

        public $attributes = [
            'attribute' => 'value',
        ];

        public $casts = [
            'serialized_attribute' => 'serialize',
        ];
    };
    $model->serialized_attribute = ['key' => 'value'];

    expect($model->getAttribute('attribute'))->toBe('value')
        ->and($model->getAttribute('serialized_attribute'))->toBe(['key' => 'value'])
        ->and($model->attributesToArray())->toHaveKey('serialized_attribute', ['key' => 'value']);
});

it('retrieves attribute value using beforeGetAttribute event', function() {
    $model = new class extends Model
    {
        use HasAttributes;

        public $attributes = ['attribute' => 'value'];
    };
    $model->bindEvent('model.beforeGetAttribute', function($key) {
        return 'beforeGetAttribute';
    });

    expect($model->getAttribute('attribute'))->toBe('beforeGetAttribute');
});

it('retrieves attribute value using getAttribute event', function() {
    $model = new class extends Model
    {
        use HasAttributes;

        public $attributes = ['attribute' => 'value'];
    };
    $model->bindEvent('model.getAttribute', function($key, $value) {
        return 'getAttribute';
    });

    expect($model->getAttribute('attribute'))->toBe('getAttribute');
});

it('returns null for non-existent attribute', function() {
    $model = new class extends Model
    {
        use HasAttributes;
    };

    expect($model->getAttribute('nonexistent'))->toBeNull()
        ->and($model->getAttribute('getKey'))->toBeNull();
});

it('throws exception for empty attribute key', function() {
    $model = new class extends Model
    {
        use HasAttributes;
    };

    expect(fn() => $model->setAttribute('', 'value'))->toThrow(BadMethodCallException::class);
});

it('converts attributes to array', function() {
    $model = new class extends Model
    {
        use HasAttributes;

        public $attributes = ['attribute' => 'value'];
    };

    expect($model->attributesToArray())->toHaveKey('attribute', 'value');
});

it('fires event before getting attribute', function() {
    $model = new class extends Model
    {
        use HasAttributes;

        public $attributes = ['attribute' => 'value'];
    };
    $model->bindEvent('model.beforeGetAttribute', function($key) {
        return 'eventValue';
    });

    expect($model->attributesToArray())->toHaveKey('attribute', 'eventValue');
});

it('fires event after getting attribute', function() {
    $model = new class extends Model
    {
        use HasAttributes;

        public $attributes = ['attribute' => 'value'];
    };
    $model->bindEvent('model.getAttribute', function($key) {
        return 'eventValue';
    });
    expect($model->attributesToArray())->toHaveKey('attribute', 'eventValue');
});

it('sets attribute using mutator', function() {
    $model = new class extends Model
    {
        use HasAttributes;

        public $attributes = ['customAttribute' => 'value'];

        public function customAttribute(): Attribute
        {
            return Attribute::make(
                get: fn(string $value) => ucfirst($value),
                set: fn(string $value) => strtolower($value),
            );
        }
    };
    $model->setAttribute('customAttribute', 'value');

    expect($model->getAttributes())->toHaveKey('customAttribute', 'value');
});

it('sets attribute using event', function() {
    $model = new class extends Model
    {
        use HasAttributes;

        public $attributes = ['customAttribute' => 'value'];
    };
    $model->bindEvent('model.beforeSetAttribute', function($key, $value) {
        return 'eventValue';
    });

    $model->setAttribute('customAttribute', 'value');
    expect($model->getAttributes())->toHaveKey('customAttribute', 'eventValue');
});

it('sets enum attribute value', function() {
    $model = new class extends Model
    {
        use HasAttributes;

        public $attributes = ['enum_attribute' => 'value'];

        protected $casts = ['enum_attribute' => TestEnum::class];
    };
    $model->setAttribute('enum_attribute', TestEnum::VALUE1);

    expect($model->enum_attribute)->toBe(TestEnum::VALUE1);
});

it('sets encrypted attribute value', function() {
    $model = new class extends Model
    {
        use HasAttributes;

        public $attributes = ['enumAttribute' => 'value'];

        protected $casts = [
            'encrypted_attribute' => 'encrypted',
        ];
    };
    $value = 'secret';
    $model->setAttribute('encrypted_attribute', $value);
    expect($model->encrypted_attribute)->toBe($value)
        ->and(array_get($model->getAttributes(), 'encrypted_attribute'))->not->toBe($value);
});

it('sets date attribute value', function() {
    $model = new class extends Model
    {
        use HasAttributes;

        protected $attributes = [];

        protected $casts = ['date_attribute' => 'date'];
    };
    $date = Carbon::now();
    $model->setAttribute('date_attribute', $date);

    expect(array_get($model->getAttributes(), 'date_attribute'))->toBe($date->format($model->getDateFormat()))
        ->and(fn() => $model->setAttribute('date_attribute', 'invalid_date'))->toThrow(InvalidFormatException::class);
});

it('sets nested json attribute value', function() {
    $model = new class extends Model
    {
        use HasAttributes;

        protected $attributes = [];

        protected $casts = ['json_attribute' => 'json'];
    };
    $model->setAttribute('json_attribute->key', 'value');

    expect($model->json_attribute)->toBe(['key' => 'value']);
});

it('sets time format', function() {
    $model = new class extends Model
    {
        use HasAttributes;

        protected $attributes = [];

        protected $casts = ['time_attribute' => 'time'];
    };
    $model->setTimeFormat('H:i');
    $model->setAttribute('time_attribute', '23:59:59');

    expect($model->getTimeFormat())->toBe('H:i');
});

it('converts datetime object to storable string', function() {
    $model = new class extends Model
    {
        use HasAttributes;
    };
    $model->setTimeFormat($format = 'H:i');

    expect($model->fromTime(now()))->toBe(now()->format($format))
        ->and($model->fromTime(new \DateTime))->toBe((new \DateTime)->format($format))
        ->and($model->fromTime(time()))->toBe(now()->format($format))
        ->and($model->fromTime('23:59:59'))->toBe('23:59')
        ->and($model->fromTime('23:59'))->toBe('23:59');
});
