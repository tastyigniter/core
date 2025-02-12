<?php

namespace Igniter\Tests\Flame\Database\Casts;

use Igniter\Flame\Database\Casts\Serialize;
use Illuminate\Database\Eloquent\Model;

it('returns null when value is not set', function() {
    $model = new class extends Model
    {
        protected $casts = ['key' => Serialize::class];
    };
    $model->key = ['foo' => 'bar'];
    expect($model->key)->toBe(['foo' => 'bar']);
});

it('serializes value correctly', function() {
    $model = mock(Model::class);
    $cast = new Serialize();
    $value = ['foo' => 'bar'];
    $result = $cast->serialize($model, 'key', $value, []);
    expect($result)->toBe(serialize($value));
});
