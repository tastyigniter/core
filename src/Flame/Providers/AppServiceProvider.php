<?php

namespace Igniter\Flame\Providers;

use Igniter\Flame\Filesystem\Filesystem;
use Illuminate\Support\ServiceProvider;
use Illuminate\View\Compilers\BladeCompiler;

abstract class AppServiceProvider extends ServiceProvider
{
    protected $root = __DIR__.'/../../..';

    public function loadAnonymousComponentFrom(string $directory, ?string $prefix = null)
    {
        $this->callAfterResolving(BladeCompiler::class, function($blade) use ($directory, $prefix) {
            $blade->anonymousComponentNamespace($directory, $prefix);
        });
    }

    public function loadResourcesFrom(string $path, ?string $namespace = null)
    {
        $this->callAfterResolving(Filesystem::class, function(Filesystem $files) use ($path, $namespace) {
            $files->addPathSymbol($namespace, $path);
        });
    }

    protected function tapSingleton($className)
    {
        $this->app->singleton($className, function() use ($className) {
            return tap(new $className, function($manager) {
                $manager->initialize();
            });
        });
    }
}
