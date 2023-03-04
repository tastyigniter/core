<?php

namespace Igniter\Admin\EventSubscribers;

use Igniter\Admin\Models\Order;
use Igniter\Admin\Models\Reservation;
use Igniter\Admin\Models\StatusHistory;
use Igniter\Admin\Notifications\StatusUpdatedNotification;
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
        $record->reloadRelations();

        if ($history->notify) {
            $mailView = ($record instanceof Reservation)
                ? 'igniter.admin::_mail.reservation_update' : 'igniter.admin::_mail.order_update';

            $record->mailSend($mailView, 'customer');
        }

        StatusUpdatedNotification::make()->subject($history)->broadcast();
    }
}
