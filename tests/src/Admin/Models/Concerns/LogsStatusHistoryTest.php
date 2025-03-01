<?php

declare(strict_types=1);

namespace Igniter\Tests\Admin\Models\Concerns;

use Igniter\Admin\Models\Status;
use Igniter\Admin\Models\StatusHistory;
use Igniter\Cart\Models\Order;
use Illuminate\Support\Facades\Event;

it('returns status name attribute correctly', function() {
    $status = Status::factory()->create();
    $model = Order::factory()->for($status, 'status')->create();

    expect($model->status_name)->toBe($status->status_name);
});

it('returns status color attribute correctly', function() {
    $status = Status::factory()->create();
    $model = Order::factory()->for($status, 'status')->create();

    expect($model->status_color)->toBe($status->status_color);
});

it('returns null for status name attribute if status is null', function() {
    $model = Order::factory()->create();

    expect($model->status_name)->toBeNull();
});

it('returns latest status history correctly', function() {
    $model = Order::factory()->create();
    $model->status_history()->create(['status_id' => 1]);

    expect($model->getLatestStatusHistory())->not->toBeNull();
});

it('returns false when adding status history if model does not exist', function() {
    $status = Status::factory()->create();
    $model = Order::factory()->make();

    expect($model->addStatusHistory($status->getKey()))->toBeFalse();
});

it('returns false when adding status history if status is null', function() {
    $model = Order::factory()->create();

    expect($model->addStatusHistory(null))->toBeFalse();
});

it('adds status history successfully', function() {
    Event::fake(['admin.statusHistory.added']);
    $status = Status::factory()->create();
    $model = Order::factory()->create();

    $history = $model->addStatusHistory($status);

    expect($history)->toBeInstanceOf(StatusHistory::class)
        ->status_id->toBe($status->getKey())
        ->object_id->toBe($model->getKey());

    Event::assertDispatched('admin.statusHistory.added');
});

it('adds status history with additional data', function() {
    $status = Status::factory()->create();
    $model = Order::factory()->create();

    $history = $model->addStatusHistory($status, ['comment' => 'Test comment']);

    expect($history->comment)->toBe('Test comment');
});

it('returns false when event admin.statusHistory.beforeAddStatus returns false', function() {
    Event::listen('admin.statusHistory.beforeAddStatus', fn(): false => false);

    $status = Status::factory()->create();
    $model = Order::factory()->create();

    expect($model->addStatusHistory($status))->toBeFalse();
});

it('returns true if model has status history', function() {
    $model = Order::factory()->create();
    $model->status_history()->create(['status_id' => 1]);

    expect($model->hasStatus())->toBeTrue();
});

it('returns false if model does not have status history', function() {
    expect(Order::factory()->create()->hasStatus())->toBeFalse();
});

it('returns true if model has specific status in history', function() {
    $status = Status::factory()->create();
    $model = Order::factory()->create();
    $model->status_history()->create(['status_id' => $status->getKey()]);

    expect($model->hasStatus($status->getKey()))->toBeTrue();
});

it('returns false if model does not have specific status in history', function() {
    $status = Status::factory()->create();
    $model = Order::factory()->create();

    expect($model->hasStatus($status->getKey()))->toBeFalse();
});

it('filters query by status id', function() {
    $status = Status::factory()->create();
    $model = Order::factory()->create();
    $query = $model->newQuery();

    $result = $model->scopeWhereStatus($query, $status->getKey());

    expect($result->toSql())->toContain('where `status_id` in (?)');
});

it('filters query by multiple status ids', function() {
    $statuses = Status::factory()->count(2)->create();
    $model = Order::factory()->create();
    $query = $model->newQuery();

    $result = $model->scopeWhereStatus($query, $statuses->pluck('id')->toArray());

    expect($result->toSql())->toContain('where `status_id` in (?, ?)');
});

it('filters query by status id greater than or equal to 1 when status id is null', function() {
    $model = Order::factory()->create();
    $query = $model->newQuery();

    $result = $model->scopeWhereStatus($query, null);

    expect($result->toSql())->toContain('where `status_id` >= ?');
});

it('filters query by status id in history', function() {
    $status = Status::factory()->create();
    $model = Order::factory()->create();
    $model->status_history()->create(['status_id' => $status->getKey()]);
    $query = $model->newQuery();

    $sql = $model->scopeWhereHasStatusInHistory($query, $status->getKey())->toSql();

    expect($sql)->toContain('where exists (select * from `status_history` where `orders`.`order_id`')
        ->and($sql)->toContain('and `status_id` = ?');
});

it('filters query by not having status id in history', function() {
    $status = Status::factory()->create();
    $model = Order::factory()->create();
    $query = $model->newQuery();

    $sql = $model->scopeDoesntHaveStatusInHistory($query, $status->getKey())->toSql();

    expect($sql)->toContain('where not exists (select * from `status_history`')
        ->and($sql)->toContain('and `status_id` = ?');
});
