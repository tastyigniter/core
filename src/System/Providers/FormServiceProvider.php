<?php

namespace Igniter\System\Providers;

use Igniter\System\Models\Settings;
use Illuminate\Support\ServiceProvider;

class FormServiceProvider extends ServiceProvider
{
    public function register()
    {
        Settings::registerCallback(function (Settings $manager) {
            $manager->registerSettingItems('core', [
                'general' => [
                    'label' => 'igniter::system.settings.text_tab_general',
                    'description' => 'igniter::system.settings.text_tab_desc_general',
                    'icon' => 'fa fa-sliders',
                    'priority' => 0,
                    'permission' => ['Site.Settings'],
                    'url' => admin_url('settings/edit/general'),
                    'form' => 'generalsettings',
                    'request' => \Igniter\System\Requests\GeneralSettings::class,
                ],
                'site' => [
                    'label' => 'igniter::system.settings.text_tab_site',
                    'description' => 'igniter::system.settings.text_tab_desc_site',
                    'icon' => 'fa fa-globe',
                    'priority' => 2,
                    'permission' => ['Site.Settings'],
                    'url' => admin_url('settings/edit/site'),
                    'form' => 'sitesettings',
                    'request' => 'Igniter\System\Requests\SiteSettings',
                ],
                'mail' => [
                    'label' => 'lang:igniter::system.settings.text_tab_mail',
                    'description' => 'lang:igniter::system.settings.text_tab_desc_mail',
                    'icon' => 'fa fa-envelope',
                    'priority' => 4,
                    'permission' => ['Site.Settings'],
                    'url' => admin_url('settings/edit/mail'),
                    'form' => 'mailsettings',
                    'request' => \Igniter\System\Requests\MailSettings::class,
                ],
                'advanced' => [
                    'label' => 'lang:igniter::system.settings.text_tab_server',
                    'description' => 'lang:igniter::system.settings.text_tab_desc_server',
                    'icon' => 'fa fa-cog',
                    'priority' => 7,
                    'permission' => ['Site.Settings'],
                    'url' => admin_url('settings/edit/advanced'),
                    'form' => 'advancedsettings',
                    'request' => \Igniter\System\Requests\AdvancedSettings::class,
                ],
            ]);
        });
    }
}