<?php

namespace Igniter\Admin\Providers;

use Igniter\Admin\Classes\Navigation;
use Igniter\Admin\Facades\AdminMenu;
use Igniter\Flame\Igniter;
use Illuminate\Support\ServiceProvider;

class MenuItemServiceProvider extends ServiceProvider
{
    public function register()
    {
        if (Igniter::runningInAdmin()) {
            $this->registerMainMenuItems();
            $this->registerNavMenuItems();
        }
    }

    /**
     * Register admin top menu navigation items
     */
    protected function registerMainMenuItems()
    {
        AdminMenu::registerCallback(function (Navigation $manager) {
            $menuItems = [
                'preview' => [
                    'icon' => 'fa-store',
                    'attributes' => [
                        'class' => 'nav-link front-end',
                        'title' => 'lang:igniter::admin.side_menu.storefront',
                        'href' => page_url('home'),
                        'target' => '_blank',
                    ],
                ],
                'locations' => [
                    'type' => 'partial',
                    'path' => 'locations/picker',
                    'options' => [\Igniter\Admin\Classes\UserPanel::class, 'listLocations'],
                ],
                'notifications' => [
                    'label' => 'lang:igniter::admin.text_activity_title',
                    'icon' => 'fa-bell',
                    'type' => 'dropdown',
                    'options' => [\Igniter\Admin\Classes\UserPanel::class, 'listNotifications'],
                    'partial' => 'notifications.latest',
                    'permission' => 'Admin.Notifications',
                ],
                'settings' => [
                    'icon' => 'fa-gear',
                    'attributes' => [
                        'class' => 'nav-link front-end',
                        'title' => 'lang:igniter::admin.side_menu.setting',
                        'href' => admin_url('settings'),
                    ],
                ],
                'user' => [
                    'type' => 'partial',
                    'path' => 'user_menu',
                    'options' => [\Igniter\Admin\Classes\UserPanel::class, 'listMenuLinks'],
                ],
            ];

            if (AdminLocation::listLocations()->isEmpty()) {
                unset($menuItems['locations']);
            }

            $manager->registerMainItems($menuItems);
        });
    }

    /**
     * Register admin menu navigation items
     */
    protected function registerNavMenuItems()
    {
        AdminMenu::registerCallback(function (Navigation $manager) {
            $manager->registerNavItems([
                'dashboard' => [
                    'priority' => 0,
                    'class' => 'dashboard admin',
                    'href' => admin_url('dashboard'),
                    'icon' => 'fa-tachometer-alt',
                    'title' => lang('igniter::admin.side_menu.dashboard'),
                ],
                'restaurant' => [
                    'priority' => 10,
                    'class' => 'restaurant',
                    'icon' => 'fa-gem',
                    'title' => lang('igniter::admin.side_menu.restaurant'),
                    'child' => [],
                ],
                'sales' => [
                    'priority' => 30,
                    'class' => 'sales',
                    'icon' => 'fa-file-invoice',
                    'title' => lang('igniter::admin.side_menu.sale'),
                    'child' => [
                        'statuses' => [
                            'priority' => 40,
                            'class' => 'statuses',
                            'href' => admin_url('statuses'),
                            'title' => lang('igniter::admin.side_menu.status'),
                            'permission' => 'Admin.Statuses',
                        ],
                    ],
                ],
                'marketing' => [
                    'priority' => 40,
                    'class' => 'marketing',
                    'icon' => 'fa-bullseye',
                    'title' => lang('igniter::admin.side_menu.marketing'),
                    'child' => [],
                ],
                'customers' => [
                    'priority' => 100,
                    'class' => 'customers',
                    'icon' => 'fa-user',
                    'href' => admin_url('customers'),
                    'title' => lang('igniter::admin.side_menu.customer'),
                    'permission' => 'Admin.Customers',
                ],
                'design' => [
                    'priority' => 200,
                    'class' => 'design',
                    'icon' => 'fa-paint-brush',
                    'title' => lang('igniter::admin.side_menu.design'),
                    'child' => [
                        'themes' => [
                            'priority' => 10,
                            'class' => 'themes',
                            'href' => admin_url('themes'),
                            'title' => lang('igniter::admin.side_menu.theme'),
                            'permission' => 'Site.Themes',
                        ],
                        'mail_templates' => [
                            'priority' => 20,
                            'class' => 'mail_templates',
                            'href' => admin_url('mail_templates'),
                            'title' => lang('igniter::admin.side_menu.mail_template'),
                            'permission' => 'Admin.MailTemplates',
                        ],
                    ],
                ],
                'tools' => [
                    'priority' => 400,
                    'class' => 'tools',
                    'icon' => 'fa-wrench',
                    'title' => lang('igniter::admin.side_menu.tool'),
                    'child' => [
                        'media_manager' => [
                            'priority' => 10,
                            'class' => 'media_manager',
                            'href' => admin_url('media_manager'),
                            'title' => lang('igniter::admin.side_menu.media_manager'),
                            'permission' => 'Admin.MediaManager',
                        ],
                    ],
                ],
                'system' => [
                    'priority' => 999,
                    'class' => 'system',
                    'icon' => 'fa-cog',
                    'title' => lang('igniter::admin.side_menu.system'),
                    'child' => [
                        'users' => [
                            'priority' => 0,
                            'class' => 'users',
                            'href' => admin_url('users'),
                            'title' => lang('igniter::admin.side_menu.user'),
                            'permission' => 'Admin.Staffs',
                        ],
                        'extensions' => [
                            'priority' => 10,
                            'class' => 'extensions',
                            'href' => admin_url('extensions'),
                            'title' => lang('igniter::admin.side_menu.extension'),
                            'permission' => 'Admin.Extensions',
                        ],
                        'localisation' => [
                            'priority' => 10,
                            'class' => 'localisation',
                            'href' => admin_url('currencies'),
                            'title' => lang('igniter::admin.side_menu.localisation'),
                            'permission' => ['Site.Languages', 'Site.Languages', 'Site.Countries'],
                        ],
                        'settings' => [
                            'priority' => 20,
                            'class' => 'settings',
                            'href' => admin_url('settings'),
                            'title' => lang('igniter::admin.side_menu.setting'),
                            'permission' => 'Site.Settings',
                        ],
                        'updates' => [
                            'priority' => 30,
                            'class' => 'updates',
                            'href' => admin_url('updates'),
                            'title' => lang('igniter::admin.side_menu.updates'),
                            'permission' => 'Site.Updates',
                        ],
                        'system_logs' => [
                            'priority' => 50,
                            'class' => 'system_logs',
                            'href' => admin_url('system_logs'),
                            'title' => lang('igniter::admin.side_menu.system_logs'),
                            'permission' => 'Admin.SystemLogs',
                        ],
                    ],
                ],
            ]);
        });
    }
}
