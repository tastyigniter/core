<?php

declare(strict_types=1);

namespace Igniter\Tests\Flame\Database\Relations;

use Igniter\Admin\Models\Status;
use Igniter\Admin\Models\StatusHistory;

it('associates and dissociates model correctly', function() {
    $status = new class extends Status
    {
        public $relation = [
            'morphOne' => [
                'object' => [StatusHistory::class, 'name' => 'object'],
            ],
        ];

        public function getMorphClass(): string
        {
            return 'status';
        }
    };
    $status->save();

    $statusHistory = StatusHistory::factory()->create();
    $builder = $status->object();
    $builder->add($statusHistory);

    expect($status->object)->not()->toBeNull();

    $builder->remove($statusHistory);

    expect($status->object)->toBeNull();
});

it('sets simple value with null', function() {
    $status = new class extends Status
    {
        public $relation = [
            'morphOne' => [
                'object' => [StatusHistory::class, 'name' => 'object', 'scope' => 'whereStatusIsLatest'],
            ],
        ];

        public function getMorphClass(): string
        {
            return 'status';
        }
    };
    $status->save();
    $status->object()->setSimpleValue(null);
    $status->save();

    expect($status->fresh()->object)->toBeNull()
        ->and($status->object()->setSimpleValue([]))->toBeNull();
});

it('sets simple value with model instance', function() {
    $status = new class extends Status
    {
        public $relation = [
            'morphOne' => [
                'object' => [
                    StatusHistory::class,
                    'name' => 'object',
                    'conditions' => 'status_history_id > 0',
                    'order' => 'status_history_id desc',
                ],
            ],
        ];

        public function getMorphClass(): string
        {
            return 'status';
        }
    };
    $status->save();

    $statusHistory = StatusHistory::factory()->create();
    $status->object()->setSimpleValue($statusHistory);
    $status->save();

    expect($status->fresh()->object->status_history_id)->toBe($statusHistory->status_history_id);

    $status->object()->setSimpleValue($statusHistory);
    $status->save();
});

it('sets simple value with id', function() {
    $status = new class extends Status
    {
        public $relation = [
            'morphOne' => [
                'object' => [StatusHistory::class, 'name' => 'object'],
            ],
        ];

        public function getMorphClass(): string
        {
            return 'status';
        }
    };
    $status->save();

    $statusHistory = StatusHistory::factory()->create();
    $status->object()->setSimpleValue($statusHistory->status_history_id);
    $status->save();

    expect($status->fresh()->object->status_history_id)->toBe($statusHistory->status_history_id)
        ->and($status->object()->getSimpleValue())->toBe($statusHistory->status_history_id);
});
