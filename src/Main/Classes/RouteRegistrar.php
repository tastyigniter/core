<?php

namespace Igniter\Main\Classes;

use Igniter\Flame\Pagic\Router;
use Illuminate\Routing\Router as IlluminateRouter;

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
            ->middleware(config('igniter.routes.middleware'))
            ->domain(config('igniter.routes.domain'))
            ->name('igniter.main.assets')
            ->group(function (IlluminateRouter $router) {
                $uri = config('igniter.routes.assetsCombinerUri', '_assets').'/{asset}';
                $router->get($uri, 'AssetController');
            });
    }

    public function forThemePages()
    {
        $this->router
            ->middleware(config('igniter.routes.middleware'))
            ->domain(config('igniter.routes.domain'))
            ->name('igniter.theme.')
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

    protected function getThemePageRoutes()
    {
        return resolve(Router::class)->getRouteMap();
    }
}
