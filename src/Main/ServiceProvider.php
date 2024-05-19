<?php

namespace Igniter\Main;

use Igniter\Flame\Igniter;
use Igniter\Flame\Providers\AppServiceProvider;
use Igniter\Main\Classes\MediaLibrary;
use Igniter\Main\Classes\RouteRegistrar;
use Igniter\Main\Classes\ThemeManager;
use Igniter\Main\Http\Middleware\CheckMaintenance;
use Igniter\Main\Template\Extension\BladeExtension;
use Igniter\System\Classes\ComponentManager;
use Igniter\System\Models\Settings;
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

        $this->app->booted(function() {
            View::share('site_name', Settings::get('site_name'));
            View::share('site_logo', Settings::get('site_logo'));

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
        $this->registerBladeDirectives();

        Igniter::loadControllersFrom(igniter_path('src/Main/Http/Controllers'), 'Igniter\\Main\\Http\\Controllers');

        Route::pushMiddlewareToGroup('igniter', CheckMaintenance::class);

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
        resolve(ComponentManager::class)->registerCallback(function(ComponentManager $manager) {
            $manager->registerComponent(\Igniter\Main\Components\BlankComponent::class, [
                'code' => 'blankComponent',
                'name' => 'Blank Component',
            ]);

            $manager->registerComponent(\Igniter\Main\Components\ViewBag::class, [
                'code' => 'viewBag',
                'name' => 'ViewBag Component',
                'description' => 'Stores custom template properties.',
            ]);
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

        Route::group([], function($router) {
            (new RouteRegistrar($router))->all();
        });
    }

    protected function registerBladeDirectives()
    {
        $this->callAfterResolving('blade.compiler', function($compiler, $app) {
            (new BladeExtension)->register();
        });
    }
}
