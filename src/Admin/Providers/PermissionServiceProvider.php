<?php

namespace Igniter\Admin\Providers;

use Igniter\User\Classes\PermissionManager;
use Illuminate\Support\ServiceProvider;

class PermissionServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->callAfterResolving(PermissionManager::class, function($manager) {
            $manager->registerCallback(function($manager) {
                $manager->registerPermissions('Admin', [
                    'Admin.Dashboard' => [
                        'label' => 'igniter::admin.permissions.dashboard',
                        'group' => 'igniter::admin.permissions.name',
                    ],
                    'Admin.Statuses' => [
                        'label' => 'igniter::admin.permissions.statuses',
                        'group' => 'igniter::admin.permissions.name',
                    ],
                ]);
            });
        });
    }
}
