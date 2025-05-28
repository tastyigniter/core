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

    expect($status->status_history()->count())->toBe(1);

    $builder->remove($statusHistory);

    expect($status->status_history()->count())->toBe(0)
        ->and($builder->getForeignKey())->toBe('status_history.status_id')
        ->and($builder->getOtherKey())->toBe('status_id');
});

it('associates model when parent does not exists', function() {
    Status::flushEventListeners();
    $status = Status::factory()->make();
    $statusHistory = StatusHistory::factory()->create();
    $builder = $status->status_history();
    $builder->add($statusHistory);

    $status->save();

    expect($status->status_history()->count())->toBe(1);
});

it('does not associates or dissociates model when event returns false', function() {
    Status::flushEventListeners();
    $status = Status::factory()->create();
    $statusHistory = StatusHistory::factory()->create();
    $builder = $status->status_history();
    $status->bindEvent('model.relation.beforeAdd', fn() => false);
    $status->bindEvent('model.relation.beforeRemove', fn() => false);

    expect($builder->add($statusHistory))->toBeNull()
        ->and($builder->remove($statusHistory))->toBeNull();
});

it('dissociates model returns null when model is not removable', function() {
    Status::flushEventListeners();
    $status = Status::factory()->create();
    $statusHistory = StatusHistory::factory()->create();
    $builder = $status->status_history();

    expect($builder->remove($statusHistory))->toBeNull();
});

it('dissociates model deletes related model', function() {
    Status::flushEventListeners();
    $status = new class(['status_name' => 'Test']) extends Status
    {
        public $relation = [
            'hasMany' => [
                'status_history' => [StatusHistory::class, 'delete' => true],
            ],
        ];
    };
    $statusHistory = StatusHistory::factory()->create();
    $builder = $status->status_history();
    $builder->remove($statusHistory);

    expect(StatusHistory::find($statusHistory->getKey()))->toBeNull();

    $status = Status::factory()->create();
    $status->relation['hasMany']['status_history'] = [StatusHistory::class, 'delete' => true];
    $statusHistory = StatusHistory::factory()->create();
    $builder = $status->status_history();
    $builder->add($statusHistory);
    $builder->setSimpleValue(null);

    $status->save();

    expect(StatusHistory::find($statusHistory->getKey()))->toBeNull();
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
    $status->status_history = null;
    $status->save();
    $status->refresh();

    expect($status->status_history->count())->toBe(0);
});

it('sets simple value with model instance', function() {
    Status::flushEventListeners();
    $status = Status::factory()->create();
    $statusHistory = StatusHistory::factory()->create();
    $status->status_history = $statusHistory;
    $status->save();
    $status->refresh();

    expect($status->status_history()->getSimpleValue())->toContain($statusHistory->status_history_id);

    $status->unsetRelation('status_history');

    expect($status->status_history()->getSimpleValue())->toContain($statusHistory->status_history_id);
});

it('sets simple value with collection of models', function() {
    Status::flushEventListeners();
    $status = Status::factory()->create();
    $statusHistories = StatusHistory::factory()->count(2)->create();
    $status->status_history = $statusHistories;
    $status->save();
    $status->refresh();

    expect($status->status_history->count())->toBe(2);
});

it('sets simple value with array of ids', function() {
    Status::flushEventListeners();
    $status = Status::factory()->create();
    $statusHistories = StatusHistory::factory()->count(2)->create();
    $status->status_history = $statusHistories->pluck('status_history_id')->all();
    $status->save();
    $status->refresh();

    expect($status->status_history->count())->toBe(2);
});
