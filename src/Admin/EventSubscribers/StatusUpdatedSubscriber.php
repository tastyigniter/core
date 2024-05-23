<?php

namespace Igniter\Admin\EventSubscribers;

use Igniter\Admin\Models\StatusHistory;
use Igniter\Admin\Notifications\StatusUpdatedNotification;
use Igniter\Cart\Models\Order;
use Igniter\Reservation\Models\Reservation;
use Illuminate\Contracts\Events\Dispatcher;

class StatusUpdatedSubscriber
{
    public function subscribe(Dispatcher $events): array
    {
        return [
            'admin.statusHistory.added' => 'handleStatusAdded',
        ];
    }

    public function handleStatusAdded(Order|Reservation $record, StatusHistory $history): void
    {
        StatusUpdatedNotification::make()->subject($history)->broadcast();
    }
}
