<?php

namespace Igniter\Main\Providers;

use Igniter\Admin\Classes\PermissionManager;
use Illuminate\Support\ServiceProvider;

class PermissionServiceProvider extends ServiceProvider
{
    public function register()
    {
        resolve(PermissionManager::class)->registerCallback(function ($manager) {
            $manager->registerPermissions('System', [
                'Admin.MediaManager' => [
                    'label' => 'igniter::main.permissions.media_manager', 'group' => 'igniter::main.permissions.name',
                ],
                'Site.Themes' => [
                    'label' => 'igniter::main.permissions.themes', 'group' => 'igniter::main.permissions.name',
                ],
            ]);
        });
    }
}