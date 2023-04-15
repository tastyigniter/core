<?php

namespace Igniter\Main;

use Igniter\Admin\Classes\PermissionManager;
use Igniter\Admin\Classes\Widgets;
use Igniter\Flame\Igniter;
use Igniter\Flame\Pagic\Model;
use Igniter\Flame\Pagic\Router;
use Igniter\Flame\Providers\AppServiceProvider;
use Igniter\Flame\Setting\Facades\Setting;
use Igniter\Main\Classes\MediaLibrary;
use Igniter\Main\Classes\RouteRegistrar;
use Igniter\Main\Classes\Theme;
use Igniter\Main\Classes\ThemeManager;
use Igniter\Main\Template\Page;
use Igniter\System\Classes\ComponentManager;
use Igniter\System\Libraries\Assets;
use Illuminate\Foundation\AliasLoader;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\View;

class ServiceProvider extends AppServiceProvider
{
    /**
     * Bootstrap the service provider.
     *
     * @return void
     */
    public function boot()
    {
        $this->loadViewsFrom($this->root.'/resources/views/main', 'igniter.main');

        View::share('site_name', Setting::get('site_name'));
        View::share('site_logo', Setting::get('site_logo'));

        resolve(ThemeManager::class)->bootThemes();

        $this->bootMenuItemEvents();
        $this->defineRoutes();
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
        $this->registerPagicSource();

        if (!Igniter::runningInAdmin()) {
            $this->registerAssets();
            $this->registerCombinerEvent();
        } else {
            $this->registerFormWidgets();
            $this->registerPermissions();
        }
    }

    /**
     * Register components.
     */
    protected function registerComponents()
    {
        resolve(ComponentManager::class)->registerComponents(function ($manager) {
            $manager->registerComponent(\Igniter\Main\Components\ViewBag::class, 'viewBag');
        });
    }

    protected function registerSingletons()
    {
        $this->app->singleton('main.auth', function () {
            return resolve('auth')->guard(config('igniter.auth.guards.web', 'web'));
        });

        $this->tapSingleton(MediaLibrary::class);
        $this->tapSingleton(ThemeManager::class);
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
            if (Igniter::runningInAdmin()) {
                return;
            }

            $manager->registerSourcePath(Igniter::themesPath());

            resolve(ThemeManager::class)->addAssetsFromActiveThemeManifest($manager);
        });
    }

    protected function registerCombinerEvent()
    {
        if ($this->app->runningInConsole() || Igniter::runningInAdmin()) {
            return;
        }

        Event::listen('assets.combiner.beforePrepare', function (Assets $combiner, $assets) {
            resolve(ThemeManager::class)->applyAssetVariablesOnCombinerFilters(
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
            if ($item->type == 'theme-page') {
                return Page::resolveMenuItem($item, $url, $theme);
            }
        });
    }

    protected function registerFormWidgets()
    {
        resolve(Widgets::class)->registerFormWidgets(function (Widgets $manager) {
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

    protected function registerPagicSource()
    {
        $this->callAfterResolving(Router::class, function ($router) {
            $router::$templateClass = Page::class;
            $router->setTheme(resolve(ThemeManager::class)->getActiveThemeCode());
        });

        Model::extend(function (Model $model) {
            $manager = resolve(ThemeManager::class);

            $resolver = $model->getSourceResolver();
            collect($manager->listThemes())
                ->filter(function (Theme $theme) use ($resolver) {
                    return !$resolver->hasSource($theme->getName());
                })
                ->each(function (Theme $theme) use ($resolver) {
                    $resolver->addSource($theme->getName(), $theme->makeFileSource());
                });

            $activeTheme = $manager->getActiveThemeCode();
            $resolver->setDefaultSourceName($activeTheme);
        });
    }

    protected function defineRoutes()
    {
        if (app()->routesAreCached()) {
            return;
        }

        Route::group([], function ($router) {
            (new RouteRegistrar($router))->all();
        });
    }
}
