<?php

namespace Igniter\Tests\Admin\Notifications;

use Igniter\Admin\Models\Status;
use Igniter\Admin\Models\StatusHistory;
use Igniter\Admin\Notifications\StatusUpdatedNotification;
use Igniter\Cart\Models\Order;
use Igniter\Reservation\Models\Reservation;
use Igniter\User\Models\Customer;
use Igniter\User\Models\User;
use Igniter\User\Models\UserGroup;

it('returns recipients, customer excluding the current authenticated user', function() {
    $customer = Customer::factory()->create();
    $assignee = User::factory()->create(['status' => true]);
    $assignee2 = User::factory()->create(['status' => true]);
    $order = Order::factory()->for($customer, 'customer')->create();
    $status = Status::factory()->create();
    $assigneeGroup = UserGroup::factory()->create();
    $assigneeGroup->users()->attach([$assignee, $assignee2]);
    $order->assignee_group()->associate($assigneeGroup)->save();
    $history = StatusHistory::factory()->create([
        'object_id' => $order->getKey(),
        'object_type' => $order->getMorphClass(),
        'status_id' => $status->getKey(),
        'notify' => true,
    ]);
    $this->actingAs($assignee, 'igniter-admin');

    $notification = StatusUpdatedNotification::make()->subject($history);

    $recipients = $notification->getRecipients();

    expect($recipients)->toHaveCount(2);
});

it('returns correct title for order', function() {
    $order = Order::factory()->create();
    $status = Status::factory()->create();
    $history = StatusHistory::factory()->create([
        'object_id' => $order->getKey(),
        'object_type' => $order->getMorphClass(),
        'status_id' => $status->getKey(),
        'notify' => true,
    ]);
    $notification = StatusUpdatedNotification::make()->subject($history);

    $title = $notification->getTitle();

    expect($title)->toBe(lang('igniter.cart::default.orders.notify_status_updated_title'));
});

it('returns correct title for reservation', function() {
    $reservation = Reservation::factory()->create();
    $status = Status::factory()->create();
    $history = StatusHistory::factory()->create([
        'object_id' => $reservation->getKey(),
        'object_type' => $reservation->getMorphClass(),
        'status_id' => $status->getKey(),
        'notify' => true,
    ]);
    $notification = StatusUpdatedNotification::make()->subject($history);

    $title = $notification->getTitle();

    expect($title)->toBe(lang('igniter.reservation::default.notify_status_updated_title'));
});

it('returns correct URL for order', function() {
    $order = Order::factory()->create();
    $status = Status::factory()->create();
    $history = StatusHistory::factory()->create([
        'object_id' => $order->getKey(),
        'object_type' => $order->getMorphClass(),
        'status_id' => $status->getKey(),
        'notify' => true,
    ]);
    $notification = StatusUpdatedNotification::make()->subject($history);

    $url = $notification->getUrl();

    expect($url)->toBe(admin_url('orders/edit/'.$order->getKey()));
});

it('returns correct message for order', function() {
    $user = User::factory()->create(['status' => true]);
    $order = Order::factory()->create();
    $status = Status::factory()->create();
    $history = StatusHistory::factory()->create([
        'user_id' => $user->getKey(),
        'object_id' => $order->getKey(),
        'object_type' => $order->getMorphClass(),
        'status_id' => $status->getKey(),
        'notify' => true,
    ]);
    $notification = StatusUpdatedNotification::make()->subject($history);

    $message = $notification->getMessage();

    expect($message)->toBe(sprintf(
        lang('igniter.cart::default.orders.notify_status_updated'),
        $user->full_name,
        $order->getKey(),
        $status->status_name,
    ));
});

it('returns correct message for reservation when no user', function() {
    $reservation = Reservation::factory()->create();
    $status = Status::factory()->create();
    $history = StatusHistory::factory()->create([
        'object_id' => $reservation->getKey(),
        'object_type' => $reservation->getMorphClass(),
        'status_id' => $status->getKey(),
        'notify' => true,
    ]);
    $notification = StatusUpdatedNotification::make()->subject($history);

    $message = $notification->getMessage();

    expect($message)->toBe(sprintf(
        lang('igniter.reservation::default.notify_status_updated'),
        lang('igniter::admin.text_system'),
        $reservation->getKey(),
        $status->status_name,
    ));
});

it('returns correct message for reservation', function() {
    $user = User::factory()->create(['status' => true]);
    $reservation = Reservation::factory()->create();
    $status = Status::factory()->create();
    $history = StatusHistory::factory()->create([
        'user_id' => $user->getKey(),
        'object_id' => $reservation->getKey(),
        'object_type' => $reservation->getMorphClass(),
        'status_id' => $status->getKey(),
        'notify' => true,
    ]);
    $notification = StatusUpdatedNotification::make()->subject($history);

    $message = $notification->getMessage();

    expect($message)->toBe(sprintf(
        lang('igniter.reservation::default.notify_status_updated'),
        $user->full_name,
        $reservation->getKey(),
        $status->status_name,
    ));
});

it('returns correct icon', function() {
    $notification = StatusUpdatedNotification::make();

    $icon = $notification->getIcon();

    expect($icon)->toBe('fa-clipboard-check');
});

it('returns correct alias', function() {
    $notification = StatusUpdatedNotification::make();

    $alias = $notification->getAlias();

    expect($alias)->toBe('status-updated');
});
