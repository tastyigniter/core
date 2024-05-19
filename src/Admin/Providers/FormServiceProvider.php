<?php

namespace Igniter\Admin\Providers;

use Igniter\Admin\Classes\OnboardingSteps;
use Igniter\Admin\Classes\Widgets;
use Igniter\Flame\Igniter;
use Illuminate\Support\ServiceProvider;

class FormServiceProvider extends ServiceProvider
{
    public function register()
    {
        if (Igniter::runningInAdmin() || app()->runningUnitTests()) {
            $this->registerDashboardWidgets();
            $this->registerBulkActionWidgets();
            $this->registerFormWidgets();
            $this->registerOnboardingSteps();
        }
    }

    /*
     * Register dashboard widgets
     */
    protected function registerDashboardWidgets()
    {
        resolve(Widgets::class)->registerDashboardWidgets(function(Widgets $manager) {
            $manager->registerDashboardWidget(\Igniter\System\DashboardWidgets\Cache::class, [
                'code' => 'cache',
                'label' => 'Cache Usage',
            ]);

            $manager->registerDashboardWidget(\Igniter\System\DashboardWidgets\News::class, [
                'code' => 'news',
                'label' => 'Latest News',
            ]);

            $manager->registerDashboardWidget(\Igniter\Admin\DashboardWidgets\Statistics::class, [
                'code' => 'stats',
                'label' => 'Statistics widget',
            ]);

            $manager->registerDashboardWidget(\Igniter\Admin\DashboardWidgets\Onboarding::class, [
                'code' => 'onboarding',
                'label' => 'Onboarding widget',
            ]);

            $manager->registerDashboardWidget(\Igniter\Admin\DashboardWidgets\Charts::class, [
                'code' => 'charts',
                'label' => 'Charts widget',
            ]);
        });
    }

    protected function registerBulkActionWidgets()
    {
        resolve(Widgets::class)->registerBulkActionWidgets(function(Widgets $manager) {
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
        resolve(Widgets::class)->registerFormWidgets(function(Widgets $manager) {
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
        });
    }

    protected function registerOnboardingSteps()
    {
        OnboardingSteps::registerCallback(function(OnboardingSteps $manager) {
            $manager->registerSteps([
                'admin::settings' => [
                    'label' => 'igniter::admin.dashboard.onboarding.label_settings',
                    'description' => 'igniter::admin.dashboard.onboarding.help_settings',
                    'icon' => 'fa-gears',
                    'url' => admin_url('settings'),
                    'priority' => 10,
                    'complete' => [\Igniter\System\Models\Settings::class, 'onboardingIsComplete'],
                ],
                'admin::themes' => [
                    'label' => 'igniter::admin.dashboard.onboarding.label_themes',
                    'description' => 'igniter::admin.dashboard.onboarding.help_themes',
                    'icon' => 'fa-paint-brush',
                    'url' => admin_url('themes'),
                    'priority' => 20,
                    'complete' => [\Igniter\Main\Models\Theme::class, 'onboardingIsComplete'],
                ],
                'admin::extensions' => [
                    'label' => 'igniter::admin.dashboard.onboarding.label_extensions',
                    'description' => 'igniter::admin.dashboard.onboarding.help_extensions',
                    'icon' => 'fa-plug',
                    'url' => admin_url('extensions'),
                    'priority' => 30,
                    'complete' => [\Igniter\System\Models\Extension::class, 'onboardingIsComplete'],
                ],
                'admin::menus' => [
                    'label' => 'igniter::admin.dashboard.onboarding.label_menus',
                    'description' => 'igniter::admin.dashboard.onboarding.help_menus',
                    'icon' => 'fa-cutlery',
                    'url' => admin_url('menus'),
                    'priority' => 40,
                ],
                'admin::mail' => [
                    'label' => 'igniter::admin.dashboard.onboarding.label_mail',
                    'description' => 'igniter::admin.dashboard.onboarding.help_mail',
                    'icon' => 'fa-envelope',
                    'url' => admin_url('settings/edit/mail'),
                    'priority' => 50,
                ],
            ]);
        });
    }
}
