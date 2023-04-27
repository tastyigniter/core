<?php

namespace Igniter\Admin\Classes;

use Igniter\Admin\Http\Controllers\Dashboard;
use Igniter\Admin\Http\Controllers\Login;
use Igniter\Flame\Igniter;
use Igniter\Flame\Support\Facades\File;
use Illuminate\Routing\Router;
use Illuminate\Support\Str;
use ReflectionClass;

class RouteRegistrar
{
    protected $router;

    public function __construct(Router $router)
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
        $this->forAdminPages();
    }

    public function forAssets()
    {
        $this->router
            ->namespace('Igniter\System\Http\Controllers')
            ->middleware('igniter')
            ->domain(config('igniter.routes.domain'))
            ->prefix(Igniter::uri())
            ->name('igniter.admin.assets')
            ->group(function (Router $router) {
                $router->get(config('igniter.routes.assetsCombinerUri', '_assets').'/{asset}', 'AssetController');
            });
    }

    public function forAdminPages()
    {
        $this->router
            ->middleware('igniter')
            ->domain(config('igniter.routes.domain'))
            ->prefix(Igniter::uri())
            ->group(function (Router $router) {
                $router->any('/login', [Login::class, 'index'])->name('igniter.admin.login');
                $router->any('/login/reset/{slug?}', [Login::class, 'reset'])->name('igniter.admin.reset');
            });

        $this->router
            ->middleware('igniter:admin')
            ->domain(config('igniter.routes.domain'))
            ->prefix(Igniter::uri())
            ->group(function (Router $router) {
                $router->name('igniter.admin.dashboard')->any('/', [Dashboard::class, 'remap']);

                foreach ($this->getAdminPages() as $class) {
                    if ($class === Dashboard::class) {
                        continue;
                    }

                    [$name, $uri] = $this->guessRouteUri($class);
                    $router->name($name)->any('/'.$uri.'/{slug?}', [$class, 'remap'])->where('slug', '(.*)?');
                }
            });
    }

    protected function getAdminPages()
    {
        return collect(Igniter::controllerPath())
            ->flatMap(function ($path, $namespace) {
                $result = [];
                foreach (File::allFiles($path) as $file) {
                    $result[] = (string)Str::of($namespace)
                        ->append('\\', $file->getRelativePathname())
                        ->replace(['/', '.php'], ['\\', '']);
                }

                return $result;
            })
            ->filter(fn($class) => $this->isAdminPage($class));
    }

    protected function isAdminPage($class)
    {
        return is_subclass_of($class, AdminController::class)
            && !(new ReflectionClass($class))->isAbstract()
            && $class !== Login::class;
    }

    protected function guessRouteUri($class)
    {
        if (Str::startsWith($class, config('igniter.routes.coreNamespaces', []))) {
            $uri = strtolower($resource = snake_case(class_basename($class)));
            $name = strtolower(implode('.', array_slice(explode('\\', $class), 0, 2)).'.'.$resource);

            return [$name, $uri];
        }

        $resource = snake_case(class_basename($class));
        $uri = strtolower(implode('/', array_slice(explode('\\', $class), 0, 2)).'/'.$resource);
        $name = str_replace('/', '.', $uri);

        return [$name, $uri];
    }
}
