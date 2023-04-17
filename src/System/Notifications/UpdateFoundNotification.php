<?php

namespace Igniter\System\Notifications;

use Igniter\Admin\Models\User;
use Igniter\System\Classes\Notification;
use Igniter\System\Contracts\CriticalNotification;

class UpdateFoundNotification extends Notification implements CriticalNotification
{
    public function __construct(protected int $count = 0)
    {
    }

    public function getRecipients(): array
    {
        return User::isEnabled()->whereIsSuperUser()->get()->all();
    }

    public function getTitle(): string
    {
        return lang('igniter::system.updates.notify_new_update_found_title');
    }

    public function getUrl(): string
    {
        return admin_url('updates');
    }

    public function getMessage(): string
    {
        return sprintf(lang('igniter::system.updates.notify_new_update_found'), $this->count);
    }

    public function getIcon(): ?string
    {
        return 'fa-cloud-arrow-down';
    }

    public function getIconColor(): ?string
    {
        return 'success';
    }
}