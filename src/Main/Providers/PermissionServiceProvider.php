<?php

namespace Igniter\Main\Providers;

use Igniter\User\Classes\PermissionManager;
use Illuminate\Support\ServiceProvider;

class PermissionServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->callAfterResolving(PermissionManager::class, function($manager) {
            $manager->registerCallback(function($manager) {
                $manager->registerPermissions('System', [
                    'Admin.MediaManager' => [
                        'label' => 'igniter::main.permissions.media_manager',
                        'group' => 'igniter::admin.permissions.name',
                    ],
                    'Site.Themes' => [
                        'label' => 'igniter::main.permissions.themes',
                        'group' => 'igniter::system.permissions.name',
                    ],
                ]);
            });
        });
    }
}
