<?php

namespace Igniter\Admin\EventSubscribers;

use Igniter\Admin\Models\AssignableLog;
use Igniter\Admin\Models\Reservation;
use Igniter\Admin\Notifications\AssigneeUpdatedNotification;
use Igniter\Cart\Models\Order;
use Illuminate\Contracts\Events\Dispatcher;

class AssigneeUpdatedSubscriber
{
    public function subscribe(Dispatcher $events): array
    {
        return [
            'admin.assignable.assigned' => 'handleAssigned',
        ];
    }

    public function handleAssigned(string $event, Order|Reservation $record, AssignableLog $log): void
    {
        AssigneeUpdatedNotification::make()->subject($log)->broadcast();
    }
}
