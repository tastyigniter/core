<?php

namespace Igniter\System\Notifications;

use Igniter\System\Contracts\StickyNotification;
use Igniter\User\Classes\Notification;
use Igniter\User\Models\User;

class UpdateFoundNotification extends Notification implements StickyNotification
{
    public function __construct(protected int $count = 0) {}

    public function getRecipients(): array
    {
        return User::whereIsEnabled()->whereIsSuperUser()->get()->all();
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
        return $this->count > 1
            ? sprintf(lang('igniter::system.updates.notify_new_updates_found'), $this->count)
            : lang('igniter::system.updates.notify_new_update_found');
    }

    public function getIcon(): ?string
    {
        return 'fa-cloud-arrow-down';
    }

    public function getIconColor(): ?string
    {
        return 'success';
    }

    public function getAlias(): string
    {
        return 'update-found';
    }
}
