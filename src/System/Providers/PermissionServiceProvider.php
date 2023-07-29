<?php

namespace Igniter\System\Providers;

use Igniter\User\Classes\PermissionManager;
use Illuminate\Support\ServiceProvider;

class PermissionServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->callAfterResolving(PermissionManager::class, function ($manager) {
            $manager->registerCallback(function ($manager) {
                $manager->registerPermissions('System', [
                    'Admin.Notifications' => [
                        'label' => 'igniter::system.permissions.notifications', 'group' => 'advanced',
                    ],
                    'Admin.Extensions' => [
                        'label' => 'igniter::system.permissions.extensions', 'group' => 'advanced',
                    ],
                    'Admin.MailTemplates' => [
                        'label' => 'igniter::system.permissions.mail_templates', 'group' => 'admin',
                    ],
                    'Site.Countries' => [
                        'label' => 'igniter::system.permissions.countries', 'group' => 'admin',
                    ],
                    'Site.Currencies' => [
                        'label' => 'igniter::system.permissions.currencies', 'group' => 'admin',
                    ],
                    'Site.Languages' => [
                        'label' => 'igniter::system.permissions.languages', 'group' => 'admin',
                    ],
                    'Site.Settings' => [
                        'label' => 'igniter::system.permissions.settings', 'group' => 'advanced',
                    ],
                    'Site.Updates' => [
                        'label' => 'igniter::system.permissions.updates', 'group' => 'advanced',
                    ],
                    'Admin.SystemLogs' => [
                        'label' => 'igniter::system.permissions.system_logs', 'group' => 'advanced',
                    ],
                ]);
            });
        });
    }
}
