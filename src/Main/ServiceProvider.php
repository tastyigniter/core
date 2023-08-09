<?php

namespace Igniter\Main;

use Igniter\Flame\Providers\AppServiceProvider;
use Igniter\Flame\Setting\Facades\Setting;
use Igniter\Main\Classes\MediaLibrary;
use Igniter\Main\Classes\RouteRegistrar;
use Igniter\Main\Classes\ThemeManager;
use Igniter\System\Classes\ComponentManager;
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

        $this->app->booted(function () {
            View::share('site_name', Setting::get('site_name'));
            View::share('site_logo', Setting::get('site_logo'));

            $this->defineRoutes();
        });
    }

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->registerSingletons();
        $this->registerComponents();

        $this->app->register(Providers\AssetsServiceProvider::class);
        $this->app->register(Providers\FormServiceProvider::class);
        $this->app->register(Providers\MenuItemServiceProvider::class);
        $this->app->register(Providers\PagicServiceProvider::class);
        $this->app->register(Providers\PermissionServiceProvider::class);
        $this->app->register(Providers\ThemeServiceProvider::class);
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
        $this->tapSingleton(MediaLibrary::class);
        $this->tapSingleton(ThemeManager::class);
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
