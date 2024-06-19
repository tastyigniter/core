<?php

namespace Igniter\Admin\Classes;

use Igniter\Flame\Igniter;
use Igniter\Flame\Support\Facades\File;
use Illuminate\Routing\Router;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use ReflectionClass;

class RouteRegistrar
{
    public function __construct(protected Router $router) {}

    /**
     * Register routes for admin and frontend.
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
            ->middleware(config('igniter-routes.middleware', []))
            ->domain(config('igniter-routes.adminDomain'))
            ->prefix(Igniter::adminUri())
            ->name('igniter.admin.assets')
            ->group(function(Router $router) {
                $router->get(config('igniter-routes.assetsCombinerUri', '_assets').'/{asset}', 'AssetController');
            });
    }

    public function forAdminPages()
    {
        $this->router
            ->middleware(config('igniter-routes.adminMiddleware', []))
            ->domain(config('igniter-routes.adminDomain'))
            ->prefix(Igniter::adminUri())
            ->group(function(Router $router) {
                foreach ($this->getAdminPages() as $class) {
                    [$name, $uri] = $this->guessRouteUri($class);
                    $router->name($name)->any('/'.$uri.'/{slug?}', [$class, 'remap'])->where('slug', '(.*)?');
                }
            });
    }

    protected function getAdminPages(): Collection
    {
        return collect(Igniter::controllerPath())
            ->flatMap(function($path, $namespace) {
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

    protected function isAdminPage(string $class): bool
    {
        return is_subclass_of($class, AdminController::class)
            && !(new ReflectionClass($class))->isAbstract()
            && !$class::$skipRouteRegister;
    }

    protected function guessRouteUri(string $class): array
    {
        if (Str::startsWith($class, config('igniter-routes.coreNamespaces', []))) {
            $uri = $resource = strtolower(snake_case(class_basename($class)));
            $name = strtolower(implode('.', array_slice(explode('\\', $class), 0, 2)).'.'.$resource);

            return [$name, $uri];
        }

        $resource = strtolower(snake_case(class_basename($class)));
        $uri = strtolower(implode('/', array_slice(explode('\\', $class), 0, 2)).'/'.$resource);
        $name = str_replace('/', '.', $uri);

        if (method_exists($class, 'getSlug')) {
            $slug = $class::getSlug();

            return [$name, $slug];
        }

        return [$name, $uri];
    }
}

