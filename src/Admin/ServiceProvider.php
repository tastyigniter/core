<?php

declare(strict_types=1);

namespace Igniter\Admin;

use Igniter\Admin\Classes\Navigation;
use Igniter\Admin\Classes\Template;
use Igniter\Admin\DashboardWidgets\Charts;
use Igniter\Admin\DashboardWidgets\Statistics;
use Igniter\Admin\Helpers\AdminHelper;
use Igniter\Flame\Providers\AppServiceProvider;
use Igniter\Flame\Support\Facades\File;
use Igniter\Flame\Support\Facades\Igniter;
use Igniter\System\Libraries\Assets;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Foundation\AliasLoader;
use Illuminate\Support\Facades\Route;

class ServiceProvider extends AppServiceProvider
{
    /**
     * Bootstrap the service provider.
     */
    public function boot(): void
    {
        $this->loadViewsFrom($this->root.'/resources/views/admin', 'igniter.admin');
        $this->loadAnonymousComponentFrom('igniter.admin::_components.', 'igniter.admin');

        if ($this->app->runningInConsole()) {
            $this->publishes([
                $this->root.'/public' => public_path('vendor/igniter'),
            ], ['igniter-assets', 'laravel-assets']);
        }

        $this->defineRoutes();
        $this->defineEloquentMorphMaps();
        $this->clearStaticCacheOnTerminate();
    }

    /**
     * Register the service provider.
     */
    public function register(): void
    {
        $this->registerSingletons();
        $this->registerFacadeAliases();

        Igniter::loadControllersFrom(igniter_path('src/Admin/Http/Controllers'), 'Igniter\\Admin\\Http\\Controllers');

        $this->app->register(Providers\EventServiceProvider::class);
        $this->app->register(Providers\FormServiceProvider::class);
        $this->app->register(Providers\MenuItemServiceProvider::class);
        $this->app->register(Providers\PermissionServiceProvider::class);

        if (Igniter::runningInAdmin()) {
            $this->registerAssets();
        }
    }

    /**
     * Register singletons
     */
    protected function registerSingletons()
    {
        $this->app->singleton(AdminHelper::class);

        $this->app->singleton('admin.menu', function($app): Navigation {
            return new Classes\Navigation('igniter.admin::_partials');
        });

        $this->app->singleton('admin.template', function($app): Template {
            return new Classes\Template;
        });

        $this->app->singleton(Classes\OnboardingSteps::class);
        $this->app->singleton(Classes\Widgets::class);
    }

    protected function registerFacadeAliases()
    {
        $loader = AliasLoader::getInstance();

        foreach ([
            'AdminMenu' => \Igniter\Admin\Facades\AdminMenu::class,
            'Template' => \Igniter\Admin\Facades\Template::class,
        ] as $alias => $class) {
            $loader->alias($alias, $class);
        }
    }

    protected function registerAssets()
    {
        $this->app->resolving('assets', function(Assets $manager) {
            $manager->registerSourcePath(public_path('vendor/igniter'));
            $manager->registerSourcePath(File::symbolizePath('igniter::/'));

            $manager->addFromManifest($this->root.'/resources/views/admin/_meta/assets.json');
        });
    }

    protected function defineEloquentMorphMaps()
    {
        Relation::morphMap([
            'status_history' => \Igniter\Admin\Models\StatusHistory::class,
            'statuses' => \Igniter\Admin\Models\Status::class,
        ]);
    }

    protected function defineRoutes()
    {
        if (!app()->routesAreCached()) {
            Route::group([], function($router) {
                (new Classes\RouteRegistrar($router))->all();
            });
        }
    }

    protected function clearStaticCacheOnTerminate()
    {
        $this->app->terminating(function() {
            Charts::clearRegisteredDatasets();
            Statistics::clearRegisteredCards();
        });
    }
}
