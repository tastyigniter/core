<?php

namespace Igniter\Admin\Notifications;

use Igniter\Admin\Models\Order;
use Igniter\System\Classes\Notification;

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
        $context = $this->subject instanceof Order ? 'orders' : 'reservations';

        return lang('igniter::admin.'.$context.'.notify_status_updated_title');
    }

    public function getUrl(): string
    {
        $url = $this->subject instanceof Order ? 'orders' : 'reservations';
        if ($this->subject) {
            $url .= '/edit/'.$this->subject->getKey();
        }

        return admin_url($url);
    }

    public function getMessage(): string
    {
        $context = $this->subject instanceof Order ? 'orders' : 'reservations';
        $lang = lang('igniter::admin.'.$context.'.notify_status_updated');

        $causerName = ($user = auth()->user())
            ? $user->full_name
            : lang('igniter::system.notifications.activity_system');

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
}
