<?php

namespace Igniter\Admin\Notifications;

use Igniter\Admin\Models\Order;
use Igniter\System\Classes\Notification;

class AssigneeUpdatedNotification extends Notification
{
    public function getRecipients(): array
    {
        $recipients = [];
        foreach ($this->subject->assignable->listGroupAssignees() as $assignee) {
            if (auth()->user() && $assignee->getKey() === auth()->user()->getKey())
                continue;

            $recipients[] = $assignee;
        }

        return $recipients;
    }

    public function getTitle(): string
    {
        $context = $this->subject instanceof Order ? 'orders' : 'reservations';

        return lang('igniter::admin.'.$context.'.notify_assigned_title');
    }

    public function getUrl(): string
    {
        $url = $this->subject instanceof Order ? 'orders' : 'reservations';
        if ($this->subject)
            $url .= '/edit/'.$this->subject->getKey();

        return admin_url($url);
    }

    public function getMessage(): string
    {
        $context = $this->subject instanceof Order ? 'orders' : 'reservations';
        $lang = lang('igniter::admin.'.$context.'.notify_assigned');

        $causerName = ($user = auth()->user())
            ? $user->full_name
            : lang('igniter::system.notifications.activity_system');

        $assigneeName = '';
        if ($this->subject->assignee) {
            $assigneeName = $this->subject->assignee->full_name;
        }
        elseif ($this->subject->assignee_group) {
            $assigneeName = $this->subject->assignee_group->user_group_name;
        }

        return sprintf($lang,
            $causerName,
            optional($this->subject->object)->getKey(),
            $assigneeName,
        );
    }

    public function getIcon(): ?string
    {
        return 'fa-clipboard-user';
    }
}
