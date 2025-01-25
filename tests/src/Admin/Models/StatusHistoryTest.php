<?php

namespace Igniter\Tests\Admin\Models;

use Igniter\Admin\Models\Status;
use Igniter\Admin\Models\StatusHistory;
use Igniter\Cart\Models\Order;
use Illuminate\Support\Facades\Event;

it('returns true if status already exists for the model', function() {
    $order = Order::factory()->create();
    $status = Status::factory()->create();
    StatusHistory::factory()->create([
        'object_id' => $order->getKey(),
        'object_type' => $order->getMorphClass(),
        'status_id' => $status->getKey(),
    ]);

    $exists = StatusHistory::alreadyExists($order, $status->getKey());

    expect($exists)->toBeTrue();
});

it('returns false if object type is not order', function() {
    $statusHistory = new StatusHistory([
        'object_type' => 'some_other_type',
    ]);

    expect($statusHistory->isForOrder())->toBeFalse();
});

it('applies related scope correctly', function() {
    $model = Order::factory()->create();
    StatusHistory::factory()->create([
        'object_id' => $model->getKey(),
        'object_type' => $model->getMorphClass(),
    ]);

    $query = StatusHistory::applyRelated($model);

    expect($query->count())->toBe(1);
});

it('filters by latest status correctly', function() {
    $statusId = 1;
    StatusHistory::factory()->create([
        'status_id' => $statusId,
        'created_at' => now()->subDay(),
    ]);
    StatusHistory::factory()->create([
        'status_id' => $statusId,
        'created_at' => now(),
    ]);

    $query = StatusHistory::whereStatusIsLatest($statusId);

    expect($query->first()->created_at->isToday())->toBeTrue();
});

it('creates a new status history record', function() {
    $status = Status::factory()->create();
    $order = Order::factory()->create();

    $history = StatusHistory::createHistory($status, $order);

    expect($history)->toBeInstanceOf(StatusHistory::class)
        ->and($history->status_id)->toBe($status->getKey())
        ->and($history->object_id)->toBe($order->getKey())
        ->and($history->object_type)->toBe($order->getMorphClass());
});

it('returns false if beforeAddStatus event returns false', function() {
    Event::listen('admin.statusHistory.beforeAddStatus', function() {
        return false;
    });

    $status = Status::factory()->create();
    $order = Order::factory()->create();

    $history = StatusHistory::createHistory($status, $order);

    expect($history)->toBeFalse();
});

it('updates the object status and status_updated_at', function() {
    $status = Status::factory()->create();
    $order = Order::factory()->create();

    StatusHistory::createHistory($status, $order);

    $order->refresh();

    expect($order->status_id)->toBe($status->getKey())
        ->and($order->status_updated_at)->not->toBeNull();
});

it('creates history with options', function() {
    $status = Status::factory()->create();
    $order = Order::factory()->create();
    $options = [
        'staff_id' => 1,
        'comment' => 'Test comment',
        'notify' => true,
    ];

    $history = StatusHistory::createHistory($status->getKey(), $order, $options);

    expect($history->user_id)->toBe(1)
        ->and($history->comment)->toBe('Test comment')
        ->and($history->notify)->toBeTrue();
});
