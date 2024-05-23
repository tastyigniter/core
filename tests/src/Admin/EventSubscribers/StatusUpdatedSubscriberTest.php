<?php

namespace Tests\Admin\EventSubscribers;

use Igniter\Admin\EventSubscribers\StatusUpdatedSubscriber;
use Igniter\Admin\Models\StatusHistory;
use Igniter\Admin\Notifications\StatusUpdatedNotification;
use Igniter\Cart\Models\Order;
use Igniter\User\Models\User;
use Illuminate\Support\Facades\Notification;
use Mockery;

test('it handles status added', function() {
    $user = new User();
    $orderMock = Mockery::mock(Order::class);
    $orderMock->shouldReceive('reloadRelations')->once();
    $orderMock->shouldReceive('listGroupAssignees')->andReturn([$user]);
    $orderMock->shouldReceive('getLatestStatusHistory')->andReturnNull();
    $orderMock->shouldReceive('extendableGet');
    $orderMock->shouldReceive('getKey')->andReturn(1);

    $statusHistoryMock = Mockery::mock(StatusHistory::class);
    $statusHistoryMock->shouldReceive('extendableGet')->with('object')->andReturn($orderMock);
    $statusHistoryMock->shouldReceive('extendableGet')->with('notify')->andReturn(true);

    $orderMock->shouldReceive('mailSend')->once()->with('igniter.cart::mail.order_update', 'customer');

    Notification::fake();

    $subscriber = new StatusUpdatedSubscriber();
    $subscriber->handleStatusAdded($orderMock, $statusHistoryMock);

    Notification::assertSentTo([$user], StatusUpdatedNotification::class);
});
