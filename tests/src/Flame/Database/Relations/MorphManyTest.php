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

        public function getMorphClass()
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

it('sets simple value with null', function() {
    $status = new class extends Status
    {
        public $relation = [
            'morphMany' => [
                'objects' => [StatusHistory::class, 'name' => 'object'],
            ],
        ];

        public function getMorphClass()
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

        public function getMorphClass()
        {
            return 'status';
        }
    };
    $status->save();
    $statusHistory = StatusHistory::factory()->create();
    $status->objects()->setSimpleValue($statusHistory);
    $status->save();

    expect($status->fresh()->objects->count())->toBe(1);
});

it('sets simple value with collection of models', function() {
    $status = new class extends Status
    {
        public $relation = [
            'morphMany' => [
                'objects' => [StatusHistory::class, 'name' => 'object'],
            ],
        ];

        public function getMorphClass()
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

        public function getMorphClass()
        {
            return 'status';
        }
    };
    $status->save();
    $statusHistories = StatusHistory::factory()->count(2)->create();
    $status->objects()->setSimpleValue($statusHistories->pluck('status_id')->all());
    $status->save();

    expect($status->fresh()->objects->count())->toBe(2)
        ->and($status->objects()->getSimpleValue())->toContain($statusHistories->first()->status_id);
});
