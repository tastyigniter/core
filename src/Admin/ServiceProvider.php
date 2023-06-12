<?php

namespace Igniter\Admin;

use Igniter\Admin\EventSubscribers\AssigneeUpdatedSubscriber;
use Igniter\Admin\EventSubscribers\DefineOptionsFormFieldsSubscriber;
use Igniter\Admin\EventSubscribers\StatusUpdatedSubscriber;
use Igniter\Admin\Helpers\Admin as AdminHelper;
use Igniter\Flame\Igniter;
use Igniter\Flame\Providers\AppServiceProvider;
use Igniter\System\Libraries\Assets;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Foundation\AliasLoader;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Route;

class ServiceProvider extends AppServiceProvider
{
    /**
     * Bootstrap the service provider.
     * @return void
     */
    public function boot()
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
    }

    /**
     * Register the service provider.
     * @return void
     */
    public function register()
    {
        $this->registerEventSubscribers();

        $this->registerSingletons();
        $this->registerFacadeAliases();

        $this->app->register(Providers\MailServiceProvider::class);
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
        $this->app->singleton('admin.helper', function () {
            return new AdminHelper;
        });

        $this->app->singleton('admin.auth', function () {
            return resolve('auth')->guard(config('igniter-auth.guards.admin', 'web'));
        });

        $this->app->singleton('admin.menu', function ($app) {
            return new Classes\Navigation('igniter.admin::_partials');
        });

        $this->app->singleton('admin.template', function ($app) {
            return new Classes\Template;
        });

        $this->app->singleton('admin.location', function ($app) {
            return new \Igniter\Admin\Classes\Location;
        });

        $this->app->singleton(Classes\OnboardingSteps::class);
        $this->app->singleton(Classes\PaymentGateways::class);
        $this->app->singleton(Classes\PermissionManager::class);
        $this->app->singleton(Classes\Widgets::class);
    }

    protected function registerFacadeAliases()
    {
        $loader = AliasLoader::getInstance();

        foreach ([
                     'AdminHelper' => \Igniter\Admin\Facades\AdminHelper::class,
                     'AdminMenu' => \Igniter\Admin\Facades\AdminMenu::class,
                     'Template' => \Igniter\Admin\Facades\Template::class,
                 ] as $alias => $class) {
            $loader->alias($alias, $class);
        }
    }

    protected function registerAssets()
    {
        Assets::registerCallback(function (Assets $manager) {
            $manager->registerSourcePath(public_path('vendor/igniter'));

            $manager->addFromManifest($this->root.'/resources/views/admin/_meta/assets.json', 'admin');
        });
    }

    protected function defineEloquentMorphMaps()
    {
        Relation::morphMap([
            'addresses' => \Igniter\Admin\Models\Address::class,
            'assignable_logs' => \Igniter\Admin\Models\AssignableLog::class,
            'categories' => \Igniter\Admin\Models\Category::class,
            'customer_groups' => \Igniter\Main\Models\CustomerGroup::class,
            'customers' => \Igniter\Main\Models\Customer::class,
            'ingredients' => \Igniter\Admin\Models\Ingredient::class,
            'location_areas' => \Igniter\Admin\Models\LocationArea::class,
            'locations' => \Igniter\Admin\Models\Location::class,
            'mealtimes' => \Igniter\Admin\Models\Mealtime::class,
            'menu_categories' => \Igniter\Admin\Models\MenuCategory::class,
            'menu_item_option_values' => \Igniter\Admin\Models\MenuItemOptionValue::class,
            'menu_option_values' => \Igniter\Admin\Models\MenuOptionValue::class,
            'menu_options' => \Igniter\Admin\Models\MenuOption::class,
            'menus' => \Igniter\Admin\Models\Menu::class,
            'menus_specials' => \Igniter\Admin\Models\MenuSpecial::class,
            'orders' => \Igniter\Admin\Models\Order::class,
            'payment_logs' => \Igniter\Admin\Models\PaymentLog::class,
            'payments' => \Igniter\Admin\Models\Payment::class,
            'reservations' => \Igniter\Admin\Models\Reservation::class,
            'status_history' => \Igniter\Admin\Models\StatusHistory::class,
            'statuses' => \Igniter\Admin\Models\Status::class,
            'stocks' => \Igniter\Admin\Models\Stock::class,
            'stock_history' => \Igniter\Admin\Models\StockHistory::class,
            'tables' => \Igniter\Admin\Models\Table::class,
            'user_groups' => \Igniter\Admin\Models\UserGroup::class,
            'users' => \Igniter\Admin\Models\User::class,
            'working_hours' => \Igniter\Admin\Models\WorkingHour::class,
        ]);
    }

    protected function defineRoutes()
    {
        if (app()->routesAreCached()) {
            return;
        }

        Route::group([], function ($router) {
            (new Classes\RouteRegistrar($router))->all();
        });
    }
}
