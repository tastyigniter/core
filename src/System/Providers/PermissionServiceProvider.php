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
                        'label' => 'igniter::system.permissions.notifications', 'group' => 'system',
                    ],
                    'Admin.Extensions' => [
                        'label' => 'igniter::system.permissions.extensions', 'group' => 'system',
                    ],
                    'Admin.MailTemplates' => [
                        'label' => 'igniter::system.permissions.mail_templates', 'group' => 'system',
                    ],
                    'Site.Countries' => [
                        'label' => 'igniter::system.permissions.countries', 'group' => 'system',
                    ],
                    'Site.Currencies' => [
                        'label' => 'igniter::system.permissions.currencies', 'group' => 'system',
                    ],
                    'Site.Languages' => [
                        'label' => 'igniter::system.permissions.languages', 'group' => 'system',
                    ],
                    'Site.Settings' => [
                        'label' => 'igniter::system.permissions.settings', 'group' => 'system',
                    ],
                    'Site.Updates' => [
                        'label' => 'igniter::system.permissions.updates', 'group' => 'system',
                    ],
                    'Admin.SystemLogs' => [
                        'label' => 'igniter::system.permissions.system_logs', 'group' => 'system',
                    ],
                ]);
            });
        });
    }
}
