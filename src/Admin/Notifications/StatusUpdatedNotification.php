<?php

namespace Igniter\Admin\Notifications;

use Igniter\Cart\Models\Order;
use Igniter\User\Classes\Notification;

class StatusUpdatedNotification extends Notification
{
    public function getRecipients(): array
    {
        $recipients = [];
        $orderOrReservation = $this->subject->object;
        foreach ($orderOrReservation->listGroupAssignees() as $assignee) {
            if (auth()->user() && $assignee->getKey() === auth()->user()->getKey()) {
                continue;
            }

            $recipients[] = $assignee;
        }

        $statusHistory = $orderOrReservation->getLatestStatusHistory();
        if ($orderOrReservation->customer && $statusHistory && $statusHistory->notify) {
            $recipients[] = $orderOrReservation->customer;
        }

        return $recipients;
    }

    public function getTitle(): string
    {
        return $this->subject->object instanceof Order
            ? lang('igniter.cart::default.orders.notify_status_updated_title')
            : lang('igniter.reservation::default.notify_status_updated_title');
    }

    public function getUrl(): string
    {
        $url = $this->subject->object->getMorphClass();
        $url .= '/edit/'.$this->subject->object->getKey();

        return admin_url($url);
    }

    public function getMessage(): string
    {
        $lang = $this->subject->assignable instanceof Order
            ? lang('igniter.cart::default.orders.notify_status_updated')
            : lang('igniter.reservation::default.notify_status_updated');

        $causerName = $this->subject->user
            ? $this->subject->user->full_name
            : lang('igniter::admin.text_system');

        return sprintf($lang,
            $causerName,
            optional($this->subject->object)->getKey(),
            optional($this->subject->status)->status_name,
        );
    }

    public function getIcon(): ?string
    {
        return 'fa-clipboard-check';
    }

    public function getAlias(): string
    {
        return 'status-updated';
    }
}
