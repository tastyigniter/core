<?php

namespace Igniter\Main\Providers;

use Igniter\Flame\Pagic\Router;
use Igniter\Main\Template\Page;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class PagicServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->callAfterResolving(Router::class, function (Router $router) {
            $router->setLocationRouteParameterResolver(function ($name) {
                $locationPages = [setting('menus_page'), setting('reservation_page')];
                if (!in_array($name, $locationPages) || !$this->app->bound('location')) {
                    return null;
                }

                if (!$location = $this->app['location']->current()) {
                    $location = $this->app['location']->getDefault();
                }

                return $location ? $location->permalink_slug : null;
            });
        });
    }

    public function boot()
    {
        Route::bind('_file_', function ($value) {
            return Page::resolveRouteBinding($value);
        });
    }
}