<?php

namespace Igniter\Flame\Providers;

use Igniter\Admin\Classes\PermissionManager;
use Igniter\Admin\Classes\Widgets;
use Igniter\Flame\Pagic\Cache\FileSystem as FileCache;
use Igniter\Flame\Pagic\Environment;
use Igniter\Flame\Pagic\Loader;
use Igniter\Flame\Pagic\Parsers\FileParser;
use Igniter\Flame\Setting\Facades\Setting;
use Igniter\Igniter;
use Igniter\Main\Classes\ThemeManager;
use Igniter\Main\Template\Page;
use Igniter\System\Classes\ComponentManager;
use Igniter\System\Libraries\Assets;
use Igniter\System\Models\Settings;
use Igniter\System\Template\Extension\BladeExtension;
use Illuminate\Foundation\AliasLoader;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\View;

class MainServiceProvider extends AppServiceProvider
{
    /**
     * Bootstrap the service provider.
     *
     * @return void
     */
    public function boot()
    {
        $this->loadViewsFrom($this->root.'/resources/views/main', 'igniter.main');

        $this->publishes([
            $this->root.'/resources/lang/main' => app()->langPath().'/vendor/igniter/main',
        ], 'igniter-main-translations');

        View::share('site_name', Setting::get('site_name'));
        View::share('site_logo', Setting::get('site_logo'));

        ThemeManager::instance()->bootThemes();

        $this->bootMenuItemEvents();
    }

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->registerSingletons();
        $this->registerFacadeAliases();
        $this->registerComponents();
        $this->registerAssets();
        $this->registerPagicParser();
        $this->registerCombinerEvent();
        $this->registerFormWidgets();
        $this->registerPermissions();
        $this->registerSystemSettings();
    }

    /**
     * Register components.
     */
    protected function registerComponents()
    {
        ComponentManager::instance()->registerComponents(function ($manager) {
            $manager->registerComponent(\Igniter\Main\Components\ViewBag::class, 'viewBag');
        });
    }

    protected function registerSingletons()
    {
//        App::singleton('auth', function () {
//            return new Customer;
//        });
    }

    protected function registerFacadeAliases()
    {
        $loader = AliasLoader::getInstance();

        foreach ([
            'Auth' => \Igniter\Main\Facades\Auth::class,
        ] as $alias => $class) {
            $loader->alias($alias, $class);
        }
    }

    protected function registerAssets()
    {
        Assets::registerCallback(function (Assets $manager) {
            if (Igniter::runningInAdmin())
                return;

            $manager->registerSourcePath(Igniter::themesPath());

            ThemeManager::addAssetsFromActiveThemeManifest($manager);
        });
    }

    protected function registerCombinerEvent()
    {
        if ($this->app->runningInConsole() || Igniter::runningInAdmin())
            return;

        Event::listen('assets.combiner.beforePrepare', function (Assets $combiner, $assets) {
            ThemeManager::applyAssetVariablesOnCombinerFilters(
                array_flatten($combiner->getFilters())
            );
        });
    }

    /**
     * Registers events for menu items.
     */
    protected function bootMenuItemEvents()
    {
        Event::listen('pages.menuitem.listTypes', function () {
            return [
                'theme-page' => 'igniter::main.pages.text_theme_page',
            ];
        });

        Event::listen('pages.menuitem.getTypeInfo', function ($type) {
            return Page::getMenuTypeInfo($type);
        });

        Event::listen('pages.menuitem.resolveItem', function ($item, $url, $theme) {
            if ($item->type == 'theme-page')
                return Page::resolveMenuItem($item, $url, $theme);
        });
    }

    protected function registerFormWidgets()
    {
        Widgets::instance()->registerFormWidgets(function (Widgets $manager) {
            $manager->registerFormWidget(\Igniter\Main\FormWidgets\Components::class, [
                'label' => 'Components',
                'code' => 'components',
            ]);

            $manager->registerFormWidget(\Igniter\Main\FormWidgets\MapArea::class, [
                'label' => 'Map Area',
                'code' => 'maparea',
            ]);

            $manager->registerFormWidget(\Igniter\Main\FormWidgets\MapView::class, [
                'label' => 'Map View',
                'code' => 'mapview',
            ]);

            $manager->registerFormWidget(\Igniter\Main\FormWidgets\MediaFinder::class, [
                'label' => 'Media finder',
                'code' => 'mediafinder',
            ]);

            $manager->registerFormWidget(\Igniter\Main\FormWidgets\TemplateEditor::class, [
                'label' => 'Template editor',
                'code' => 'templateeditor',
            ]);
        });
    }

    protected function registerPermissions()
    {
        PermissionManager::instance()->registerCallback(function ($manager) {
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

    protected function registerSystemSettings()
    {
        Settings::registerCallback(function (Settings $manager) {
            $manager->registerSettingItems('core', [
                'media' => [
                    'label' => 'igniter::main.settings.text_tab_media_manager',
                    'description' => 'igniter::main.settings.text_tab_desc_media_manager',
                    'icon' => 'fa fa-image',
                    'priority' => 5,
                    'permission' => ['Site.Settings'],
                    'url' => admin_url('settings/edit/media'),
                    'form' => '@/models/main/mediasettings',
                    'request' => \Igniter\Main\Requests\MediaSettings::class,
                ],
            ]);
        });
    }

    protected function registerPagicParser()
    {
        FileParser::setCache(new FileCache(config('igniter.system.parsedTemplateCachePath')));

        App::singleton('pagic.environment', function () {
            $pagic = new Environment(new Loader, [
                'cache' => new FileCache(config('view.compiled')),
            ]);

            $pagic->addExtension(new BladeExtension());

            return $pagic;
        });
    }
}
