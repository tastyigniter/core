<?php

declare(strict_types=1);

namespace Igniter\Tests\Flame\Database\Relations;

use Igniter\Admin\Models\Status;
use Igniter\Admin\Models\StatusHistory;

it('associates and dissociates model correctly', function() {
    $status = new class extends Status
    {
        public $relation = [
            'morphMany' => [
                'objects' => [StatusHistory::class, 'name' => 'object', 'delete' => true],
            ],
        ];

        public function getMorphClass(): string
        {
            return 'status';
        }
    };
    $status->save();

    $statusHistory = StatusHistory::factory()->create();
    $builder = $status->objects();
    $builder->add($statusHistory);

    expect($status->objects->count())->toBe(1);

    $builder->remove($statusHistory);

    expect($status->objects->count())->toBe(0);
});

it('associates model when parent does not exists', function() {
    Status::flushEventListeners();
    $status = new class extends Status
    {
        public $relation = [
            'morphMany' => [
                'objects' => [StatusHistory::class, 'name' => 'object'],
            ],
        ];

        public function getMorphClass(): string
        {
            return 'status';
        }
    };
    $statusHistory = StatusHistory::factory()->create();
    $builder = $status->objects();
    $builder->add($statusHistory);
    $status->save();

    expect($status->objects()->count())->toBe(1);
});

it('does not associates or dissociates model when event returns false', function() {
    Status::flushEventListeners();
    $status = new class extends Status
    {
        public $relation = [
            'morphMany' => [
                'objects' => [StatusHistory::class, 'name' => 'object'],
            ],
        ];

        public function getMorphClass(): string
        {
            return 'status';
        }
    };
    $status->save();

    $statusHistory = StatusHistory::factory()->create();
    $builder = $status->objects();
    $status->bindEvent('model.relation.beforeAdd', fn($relationName, $model) => false);
    $status->bindEvent('model.relation.beforeRemove', fn($relationName) => false);

    expect($builder->add($statusHistory))->toBeNull()
        ->and($builder->remove($statusHistory))->toBeNull();
});

it('dissociates model returns null when model is not removable', function() {
    Status::flushEventListeners();
    $status = new class extends Status
    {
        public $relation = [
            'morphMany' => [
                'objects' => [StatusHistory::class, 'name' => 'object'],
            ],
        ];

        public function getMorphClass(): string
        {
            return 'status';
        }
    };
    $statusHistory = StatusHistory::factory()->create();
    $builder = $status->objects();

    expect($builder->remove($statusHistory))->toBeNull();
});

it('dissociates model deletes related model', function() {
    Status::flushEventListeners();
    $status = new class extends Status
    {
        public $relation = [
            'morphMany' => [
                'objects' => [StatusHistory::class, 'name' => 'object', 'delete' => true],
            ],
        ];

        public function getMorphClass(): string
        {
            return 'status';
        }
    };
    $status->save();
    $statusHistory = StatusHistory::factory()->create();
    $builder = $status->objects();
    $builder->add($statusHistory);
    $builder->setSimpleValue(null);
    $status->save();

    expect(StatusHistory::find($statusHistory->getKey()))->toBeNull();
});

it('sets simple value with null', function() {
    $status = new class extends Status
    {
        public $relation = [
            'morphMany' => [
                'objects' => [StatusHistory::class, 'name' => 'object'],
            ],
        ];

        public function getMorphClass(): string
        {
            return 'status';
        }
    };
    $status->save();
    $status->objects()->setSimpleValue(null);
    $status->save();

    expect($status->fresh()->objects->count())->toBe(0);
});

it('sets simple value with model instance', function() {
    $status = new class extends Status
    {
        public $relation = [
            'morphMany' => [
                'objects' => [StatusHistory::class, 'name' => 'object'],
            ],
        ];

        public function getMorphClass(): string
        {
            return 'status';
        }
    };
    $status->save();

    $statusHistory = StatusHistory::factory()->create();
    $status->objects()->setSimpleValue($statusHistory);
    $status->save();

    expect($status->fresh()->objects->count())->toBe(1);

    $status->unsetRelation('objects');

    expect($status->objects()->getSimpleValue())->toContain($statusHistory->getKey());
});

it('sets simple value with collection of models', function() {
    $status = new class extends Status
    {
        public $relation = [
            'morphMany' => [
                'objects' => [StatusHistory::class, 'name' => 'object'],
            ],
        ];

        public function getMorphClass(): string
        {
            return 'status';
        }
    };
    $status->save();

    $statusHistories = StatusHistory::factory()->count(2)->create();
    $status->objects()->setSimpleValue($statusHistories);
    $status->save();

    expect($status->fresh()->objects->count())->toBe(2);
});

it('sets simple value with array of ids', function() {
    $status = new class extends Status
    {
        public $relation = [
            'morphMany' => [
                'objects' => [StatusHistory::class, 'name' => 'object'],
            ],
        ];

        public function getMorphClass(): string
        {
            return 'status';
        }
    };
    $status->save();

    $statusHistories = StatusHistory::factory()->count(2)->create();
    $status->objects()->setSimpleValue($statusHistories->pluck('status_history_id')->all());
    $status->save();

    expect($status->fresh()->objects->count())->toBe(2)
        ->and($status->objects()->getSimpleValue())->toContain($statusHistories->first()->getKey());
});
