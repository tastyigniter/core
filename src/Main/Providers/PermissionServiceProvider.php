<?php

namespace Igniter\Main\Providers;

use Igniter\User\Classes\PermissionManager;
use Illuminate\Support\ServiceProvider;

class PermissionServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->callAfterResolving(PermissionManager::class, function ($manager) {
            $manager->registerCallback(function ($manager) {
                $manager->registerPermissions('System', [
                    'Admin.MediaManager' => [
                        'label' => 'igniter::main.permissions.media_manager',
                        'group' => 'admin',
                    ],
                    'Site.Themes' => [
                        'label' => 'igniter::main.permissions.themes',
                        'group' => 'advanced',
                    ],
                ]);
            });
        });
    }
}
