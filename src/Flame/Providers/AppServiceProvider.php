<?php

namespace Igniter\Flame\Providers;

use Illuminate\Support\ServiceProvider;

abstract class AppServiceProvider extends ServiceProvider
{
    protected $root = __DIR__.'/../../..';

    /**
     * Registers a new console (artisan) command
     *
     * @param string $key The command name
     * @param string $class The command class
     *
     * @return void
     */
    public function registerConsoleCommand($key, $class)
    {
        $key = 'command.'.$key;
        $this->app->singleton($key, function () use ($class) {
            return new $class;
        });

        $this->commands($key);
    }

    public function loadAnonymousComponentFrom(string $directory, string $prefix = null)
    {
        $this->callAfterResolving('blade.compiler', function ($blade) use ($directory, $prefix) {
            $blade->anonymousComponentNamespace($directory, $prefix);
        });
    }
}
