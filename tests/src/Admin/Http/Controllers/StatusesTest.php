<?php

declare(strict_types=1);

namespace Igniter\Tests\Admin\Http\Controllers;

use Igniter\Admin\Models\Status;

it('loads statuses page', function() {
    actingAsSuperUser()
        ->get(route('igniter.admin.statuses'))
        ->assertOk();
});

it('loads create status page', function() {
    actingAsSuperUser()
        ->get(route('igniter.admin.statuses', ['slug' => 'create']))
        ->assertOk();
});

it('loads edit status page', function() {
    $status = Status::factory()->create();

    actingAsSuperUser()
        ->get(route('igniter.admin.statuses', ['slug' => 'edit/'.$status->getKey()]))
        ->assertOk();
});

it('loads status preview page', function() {
    $status = Status::factory()->create();

    actingAsSuperUser()
        ->get(route('igniter.admin.statuses', ['slug' => 'preview/'.$status->getKey()]))
        ->assertOk();
});

it('creates status', function() {
    actingAsSuperUser()
        ->post(route('igniter.admin.statuses', ['slug' => 'create']), [
            'Status' => [
                'status_name' => 'New Status',
                'status_for' => 'order',
                'status_color' => '#000000',
                'status_comment' => 'This is a new status',
                'notify_customer' => 1,
            ],
        ], [
            'X-Requested-With' => 'XMLHttpRequest',
            'X-IGNITER-REQUEST-HANDLER' => 'onSave',
        ]);

    expect(Status::where('status_name', 'New Status')->exists())->toBeTrue();
});

it('updates status', function() {
    $status = Status::factory()->create();

    actingAsSuperUser()
        ->post(route('igniter.admin.statuses', ['slug' => 'edit/'.$status->getKey()]), [
            'Status' => [
                'status_name' => 'Updated Status',
                'status_for' => 'order',
                'status_color' => '#000000',
                'status_comment' => 'This is an updated status',
                'notify_customer' => 1,
            ],
        ], [
            'X-Requested-With' => 'XMLHttpRequest',
            'X-IGNITER-REQUEST-HANDLER' => 'onSave',
        ]);

    expect(Status::where('status_name', 'Updated Status')->exists())->toBeTrue();
});

it('deletes status', function() {
    $status = Status::factory()->create();

    actingAsSuperUser()
        ->post(route('igniter.admin.statuses', ['slug' => 'edit/'.$status->getKey()]), [], [
            'X-Requested-With' => 'XMLHttpRequest',
            'X-IGNITER-REQUEST-HANDLER' => 'onDelete',
        ]);

    expect(Status::where('status_id', $status->getKey())->exists())->toBeFalse();
});
