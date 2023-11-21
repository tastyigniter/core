<?php

namespace Igniter\Main\Classes;

use Igniter\Flame\Igniter;
use Igniter\Flame\Pagic\Router;
use Illuminate\Routing\Router as IlluminateRouter;
use Illuminate\Support\Collection;

class RouteRegistrar
{
    protected IlluminateRouter $router;

    public function __construct(IlluminateRouter $router)
    {
        $this->router = $router;
    }

    /**
     * Register routes for admin and frontend.
     *
     * @return void
     */
    public function all()
    {
        $this->forAssets();
        $this->forThemePages();
    }

    public function forAssets()
    {
        $this->router
            ->namespace('Igniter\System\Http\Controllers')
            ->middleware('igniter')
            ->domain(config('igniter-routes.domain'))
            ->name('igniter.main.assets')
            ->prefix(Igniter::uri())
            ->group(function (IlluminateRouter $router) {
                $router->get(config('igniter-routes.assetsCombinerUri', '_assets').'/{asset}', 'AssetController');
            });
    }

    public function forThemePages()
    {
        $this->router
            ->middleware('igniter')
            ->domain(config('igniter-routes.domain'))
            ->name('igniter.theme.')
            ->prefix(Igniter::uri())
            ->group(function (IlluminateRouter $router) {
                foreach ($this->getThemePageRoutes() as $parts) {
                    $route = $router->pagic($parts['uri'], $parts['route'])
                        ->defaults('_file_', $parts['file']);

                    foreach ($parts['defaults'] ?? [] as $key => $value) {
                        $route->defaults($key, $value);
                    }

                    foreach ($parts['constraints'] ?? [] as $key => $value) {
                        $route->where($key, $value);
                    }
                }
            });
    }

    protected function getThemePageRoutes(): array|Collection
    {
        if (Igniter::$disableThemeRoutes) {
            return [];
        }

        return resolve(Router::class)->getRouteMap();
    }
}
