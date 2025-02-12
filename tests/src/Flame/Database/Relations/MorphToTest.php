<?php

namespace Igniter\Tests\Flame\Database\Relations;

use Igniter\Admin\Models\StatusHistory;
use Igniter\System\Models\Currency;

it('sets simple value with null', function() {
    $statusHistory = StatusHistory::factory()->make([
        'object_id' => null,
        'object_type' => null,
    ]);
    $statusHistory->object()->setSimpleValue(null);

    expect($statusHistory->object_id)->toBeNull();
});

it('sets simple value with model instance', function() {
    StatusHistory::flushEventListeners();
    $statusHistory = StatusHistory::factory()->make([
        'object_id' => null,
        'object_type' => null,
    ]);
    $currency = Currency::factory()->make();
    $statusHistory->object()->setSimpleValue($currency);
    $currency->save();
    $statusHistory->save();

    expect($statusHistory->object_id)->toBe($currency->getKey())
        ->and($statusHistory->object()->getSimpleValue())->toBe([$currency->getKey(), 'currencies']);
});

it('sets simple value with array of id and class', function() {
    $statusHistory = StatusHistory::factory()->make([
        'object_id' => null,
        'object_type' => null,
    ]);
    $currency = Currency::factory()->create();
    $statusHistory->object()->setSimpleValue([$currency->getKey(), 'currencies']);
    $statusHistory->save();

    expect($statusHistory->object_id)->toBe($currency->getKey())
        ->and($statusHistory->object()->getSimpleValue())->toBe([$currency->getKey(), 'currencies']);
});

it('sets simple value with id', function() {
    $statusHistory = StatusHistory::factory()->make([
        'object_id' => null,
        'object_type' => 'currencies',
    ]);
    $currency = Currency::factory()->create();
    $statusHistory->object()->setSimpleValue($currency->getKey());
    $statusHistory->save();

    expect($statusHistory->object_id)->toBe($currency->getKey())
        ->and($statusHistory->object()->getSimpleValue())->toBe([$currency->getKey(), 'currencies']);
});
