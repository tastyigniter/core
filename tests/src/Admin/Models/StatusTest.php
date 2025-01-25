<?php

namespace Igniter\Tests\Admin\Models;

use Igniter\Admin\Models\Status;

it('returns dropdown options for order statuses', function() {
    $status1 = Status::factory()->create(['status_for' => 'order']);
    $status2 = Status::factory()->create(['status_for' => 'order']);
    $status3 = Status::factory()->create(['status_for' => 'reservation']);

    $options = Status::getDropdownOptionsForOrder();

    expect($options)->toHaveKey($status1->status_id)
        ->and($options)->toHaveKey($status2->status_id)
        ->and($options)->not->toHaveKey($status3->status_id);
});

it('returns dropdown options for reservation statuses', function() {
    $status1 = Status::factory()->create(['status_for' => 'reservation']);
    $status2 = Status::factory()->create(['status_for' => 'reservation']);
    $status3 = Status::factory()->create(['status_for' => 'order']);

    $options = Status::getDropdownOptionsForReservation();

    expect($options)->toHaveKey($status1->status_id)
        ->and($options)->toHaveKey($status2->status_id)
        ->and($options)->not->toHaveKey($status3->status_id);
});

it('returns only order statuses', function() {
    Status::where('status_for', 'order')->update(['status_for' => 'reservation']);
    Status::factory()->create(['status_for' => 'order']);
    Status::factory()->create(['status_for' => 'order']);
    Status::factory()->create(['status_for' => 'reservation']);

    $statuses = Status::isForOrder()->get();

    expect($statuses)->toHaveCount(2)
        ->and($statuses->pluck('status_for')->unique())->toContain('order');
});

it('returns only reservation statuses', function() {
    Status::where('status_for', 'reservation')->update(['status_for' => 'order']);
    Status::factory()->create(['status_for' => 'reservation']);
    Status::factory()->create(['status_for' => 'reservation']);
    Status::factory()->create(['status_for' => 'order']);

    $statuses = Status::isForReservation()->get();

    expect($statuses)->toHaveCount(2)
        ->and($statuses->pluck('status_for')->unique())->toContain('reservation');
});

it('lists all statuses keyed by status_id', function() {
    $status1 = Status::factory()->create(['status_for' => 'order']);
    $status2 = Status::factory()->create(['status_for' => 'reservation']);

    $statuses = Status::listStatuses();

    expect($statuses->keys())->toContain($status1->status_id)
        ->and($statuses->keys())->toContain($status2->status_id);
});
