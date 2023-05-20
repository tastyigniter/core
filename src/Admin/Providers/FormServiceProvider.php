<?php

namespace Igniter\Admin\Providers;

use Igniter\Admin\Classes\OnboardingSteps;
use Igniter\Admin\Classes\Widgets;
use Igniter\Flame\Igniter;
use Igniter\System\Models\Settings;
use Illuminate\Support\ServiceProvider;

class FormServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->registerSystemSettings();

        if (Igniter::runningInAdmin()) {
            $this->registerDashboardWidgets();
            $this->registerBulkActionWidgets();
            $this->registerFormWidgets();
            $this->registerOnboardingSteps();
        }
    }

    protected function registerSystemSettings()
    {
        Settings::registerCallback(function (Settings $manager) {
            $manager->registerSettingItems('core', [
                'setup' => [
                    'label' => 'lang:igniter::admin.settings.text_tab_setup',
                    'description' => 'lang:igniter::admin.settings.text_tab_desc_setup',
                    'icon' => 'fa fa-file-invoice',
                    'priority' => 1,
                    'permission' => ['Site.Settings'],
                    'url' => admin_url('settings/edit/setup'),
                    'form' => 'setupsettings',
                    'request' => \Igniter\Admin\Requests\SetupSettingsRequest::class,
                ],
                'tax' => [
                    'label' => 'lang:igniter::admin.settings.text_tab_tax',
                    'description' => 'lang:igniter::admin.settings.text_tab_desc_tax',
                    'icon' => 'fa fa-file',
                    'priority' => 6,
                    'permission' => ['Site.Settings'],
                    'url' => admin_url('settings/edit/tax'),
                    'form' => 'taxsettings',
                    'request' => 'Igniter\Admin\Requests\TaxSettingsRequest',
                ],
                'user' => [
                    'label' => 'lang:igniter::admin.settings.text_tab_user',
                    'description' => 'lang:igniter::admin.settings.text_tab_desc_user',
                    'icon' => 'fa fa-user',
                    'priority' => 3,
                    'permission' => ['Site.Settings'],
                    'url' => admin_url('settings/edit/user'),
                    'form' => 'usersettings',
                    'request' => \Igniter\Admin\Requests\UserSettingsRequest::class,
                ],
            ]);
        });
    }

    /*
     * Register dashboard widgets
     */
    protected function registerDashboardWidgets()
    {
        resolve(Widgets::class)->registerDashboardWidgets(function (Widgets $manager) {
            $manager->registerDashboardWidget(\Igniter\System\DashboardWidgets\Cache::class, [
                'label' => 'Cache Usage',
                'context' => 'dashboard',
            ]);

            $manager->registerDashboardWidget(\Igniter\System\DashboardWidgets\News::class, [
                'label' => 'Latest News',
                'context' => 'dashboard',
            ]);

            $manager->registerDashboardWidget(\Igniter\Admin\DashboardWidgets\Statistics::class, [
                'label' => 'Statistics widget',
                'context' => 'dashboard',
            ]);

            $manager->registerDashboardWidget(\Igniter\Admin\DashboardWidgets\Onboarding::class, [
                'label' => 'Onboarding widget',
                'context' => 'dashboard',
            ]);

            $manager->registerDashboardWidget(\Igniter\Admin\DashboardWidgets\Charts::class, [
                'label' => 'Charts widget',
                'context' => 'dashboard',
            ]);
        });
    }

    protected function registerBulkActionWidgets()
    {
        resolve(Widgets::class)->registerBulkActionWidgets(function (Widgets $manager) {
            $manager->registerBulkActionWidget(\Igniter\Admin\BulkActionWidgets\Status::class, [
                'code' => 'status',
            ]);

            $manager->registerBulkActionWidget(\Igniter\Admin\BulkActionWidgets\Delete::class, [
                'code' => 'delete',
            ]);
        });
    }

    /**
     * Register widgets
     */
    protected function registerFormWidgets()
    {
        resolve(Widgets::class)->registerFormWidgets(function (Widgets $manager) {
            $manager->registerFormWidget(\Igniter\Admin\FormWidgets\CodeEditor::class, [
                'label' => 'Code editor',
                'code' => 'codeeditor',
            ]);

            $manager->registerFormWidget(\Igniter\Admin\FormWidgets\ColorPicker::class, [
                'label' => 'Color picker',
                'code' => 'colorpicker',
            ]);

            $manager->registerFormWidget(\Igniter\Admin\FormWidgets\Connector::class, [
                'label' => 'Connector',
                'code' => 'connector',
            ]);

            $manager->registerFormWidget(\Igniter\Admin\FormWidgets\DataTable::class, [
                'label' => 'Data Table',
                'code' => 'datatable',
            ]);

            $manager->registerFormWidget(\Igniter\Admin\FormWidgets\DatePicker::class, [
                'label' => 'Date picker',
                'code' => 'datepicker',
            ]);

            $manager->registerFormWidget(\Igniter\Admin\FormWidgets\MarkdownEditor::class, [
                'label' => 'Markdown Editor',
                'code' => 'markdowneditor',
            ]);

            $manager->registerFormWidget(\Igniter\Admin\FormWidgets\PermissionEditor::class, [
                'label' => 'Permission Editor',
                'code' => 'permissioneditor',
            ]);

            $manager->registerFormWidget(\Igniter\Admin\FormWidgets\RecordEditor::class, [
                'label' => 'Record Editor',
                'code' => 'recordeditor',
            ]);

            $manager->registerFormWidget(\Igniter\Admin\FormWidgets\Relation::class, [
                'label' => 'Relationship',
                'code' => 'relation',
            ]);

            $manager->registerFormWidget(\Igniter\Admin\FormWidgets\Repeater::class, [
                'label' => 'Repeater',
                'code' => 'repeater',
            ]);

            $manager->registerFormWidget(\Igniter\Admin\FormWidgets\RichEditor::class, [
                'label' => 'Rich editor',
                'code' => 'richeditor',
            ]);

            $manager->registerFormWidget(\Igniter\Admin\FormWidgets\StatusEditor::class, [
                'label' => 'Status Editor',
                'code' => 'statuseditor',
            ]);

            $manager->registerFormWidget(\Igniter\Admin\FormWidgets\ScheduleEditor::class, [
                'label' => 'Schedule Editor',
                'code' => 'scheduleeditor',
            ]);

            $manager->registerFormWidget(\Igniter\Admin\FormWidgets\StockEditor::class, [
                'label' => 'Stock Editor',
                'code' => 'stockeditor',
            ]);
        });
    }

    protected function registerOnboardingSteps()
    {
        OnboardingSteps::registerCallback(function (OnboardingSteps $manager) {
            $manager->registerSteps([
                'admin::settings' => [
                    'label' => 'igniter::admin.dashboard.onboarding.label_settings',
                    'description' => 'igniter::admin.dashboard.onboarding.help_settings',
                    'icon' => 'fa-gears',
                    'url' => admin_url('settings'),
                    'complete' => [\Igniter\System\Models\Settings::class, 'onboardingIsComplete'],
                ],
                'admin::locations' => [
                    'label' => 'igniter::admin.dashboard.onboarding.label_locations',
                    'description' => 'igniter::admin.dashboard.onboarding.help_locations',
                    'icon' => 'fa-store',
                    'url' => admin_url('locations'),
                    'complete' => [\Igniter\Admin\Models\Location::class, 'onboardingIsComplete'],
                ],
                'admin::themes' => [
                    'label' => 'igniter::admin.dashboard.onboarding.label_themes',
                    'description' => 'igniter::admin.dashboard.onboarding.help_themes',
                    'icon' => 'fa-paint-brush',
                    'url' => admin_url('themes'),
                    'complete' => [\Igniter\Main\Models\Theme::class, 'onboardingIsComplete'],
                ],
                'admin::extensions' => [
                    'label' => 'igniter::admin.dashboard.onboarding.label_extensions',
                    'description' => 'igniter::admin.dashboard.onboarding.help_extensions',
                    'icon' => 'fa-plug',
                    'url' => admin_url('extensions'),
                    'complete' => [\Igniter\System\Models\Extension::class, 'onboardingIsComplete'],
                ],
                'admin::payments' => [
                    'label' => 'igniter::admin.dashboard.onboarding.label_payments',
                    'description' => 'igniter::admin.dashboard.onboarding.help_payments',
                    'icon' => 'fa-credit-card',
                    'url' => admin_url('payments'),
                    'complete' => [\Igniter\Admin\Models\Payment::class, 'onboardingIsComplete'],
                ],
                'admin::menus' => [
                    'label' => 'igniter::admin.dashboard.onboarding.label_menus',
                    'description' => 'igniter::admin.dashboard.onboarding.help_menus',
                    'icon' => 'fa-cutlery',
                    'url' => admin_url('menus'),
                ],
                'admin::mail' => [
                    'label' => 'igniter::admin.dashboard.onboarding.label_mail',
                    'description' => 'igniter::admin.dashboard.onboarding.help_mail',
                    'icon' => 'fa-envelope',
                    'url' => admin_url('settings/edit/mail'),
                ],
            ]);
        });
    }
}