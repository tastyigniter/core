<?php

namespace Igniter\System\Http\Controllers;

class Notifications extends \Igniter\Admin\Classes\AdminController
{
    public $implement = [
        \Igniter\Admin\Http\Actions\ListController::class,
    ];

    public $listConfig = [
        'list' => [
            'model' => \Igniter\System\Models\Notification::class,
            'title' => 'lang:igniter::system.notifications.text_title',
            'emptyMessage' => 'lang:igniter::system.notifications.text_empty',
            'defaultSort' => ['updated_at', 'DESC'],
            'configFile' => 'notification',
        ],
    ];

    protected $requiredPermissions = 'Admin.Notifications';

    public function onMarkAsRead()
    {
        $this->currentUser->unreadNotifications()->update(['read_at' => now()]);

        return $this->redirectBack();
    }

    public function listExtendQuery($query)
    {
        $query->whereNotifiable($this->currentUser);
    }
}
