<?php

declare(strict_types=1);

namespace Igniter\Tests\Flame\Database\Relations;

use Igniter\Admin\Models\Status;
use Igniter\Admin\Models\StatusHistory;

it('associates and dissociates model correctly', function() {
    Status::flushEventListeners();
    $status = Status::factory()->create();
    $statusHistory = StatusHistory::factory()->create();
    $builder = $status->status_history();
    $builder->add($statusHistory);

    expect($status->status_history->count())->toBe(1);

    $builder->remove($statusHistory);

    expect($status->status_history->count())->toBe(0)
        ->and($builder->getForeignKey())->toBe('status_history.status_id')
        ->and($builder->getOtherKey())->toBe('status_id');
});

it('associates multiple models correctly', function() {
    Status::flushEventListeners();
    $status = Status::factory()->create();
    $statusHistories = StatusHistory::factory()->count(2)->create()->all();
    $builder = $status->status_history();
    $builder->addMany($statusHistories);

    expect($status->status_history->count())->toBe(2);
});

it('sets simple value with null', function() {
    Status::flushEventListeners();
    $status = Status::factory()->create();
    $status->status_history()->setSimpleValue(null);
    $status->save();

    expect($status->status_history->count())->toBe(0);
});

it('sets simple value with model instance', function() {
    Status::flushEventListeners();
    $status = Status::factory()->create();
    $statusHistory = StatusHistory::factory()->create();
    $status->status_history()->setSimpleValue($statusHistory);
    $status->save();

    expect($status->status_history()->getSimpleValue())->toContain($statusHistory->status_id);
});

it('sets simple value with collection of models', function() {
    Status::flushEventListeners();
    $status = Status::factory()->create();
    $statusHistories = StatusHistory::factory()->count(2)->create();
    $status->status_history()->setSimpleValue($statusHistories);
    $status->save();

    expect($status->status_history->count())->toBe(2);
});

it('sets simple value with array of ids', function() {
    Status::flushEventListeners();
    $status = Status::factory()->create();
    $statusHistories = StatusHistory::factory()->count(2)->create();
    $status->status_history()->setSimpleValue($statusHistories->pluck('status_id')->all());
    $status->save();

    expect($status->status_history->count())->toBe(2);
});
