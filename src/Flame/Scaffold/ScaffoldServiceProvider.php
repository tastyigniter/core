<?php

namespace Igniter\Flame\Scaffold;

use Illuminate\Support\ServiceProvider;

class ScaffoldServiceProvider extends ServiceProvider
{
    protected $defer = true;

    /**
     * The commands to be registered.
     *
     * @var array
     */
    protected $commands = [
        'MakeExtension' => 'command.make.igniter.extension',
        'MakeComponent' => 'command.make.igniter.component',
        'MakeController' => 'command.make.igniter.controller',
        'MakeModel' => 'command.make.igniter.model',
    ];

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->registerCommands($this->commands);
    }

    /**
     * Register the given commands.
     *
     * @return void
     */
    protected function registerCommands(array $commands)
    {
        foreach ($commands as $class => $command) {
            $this->{"register{$class}Command"}($command);
        }

        $this->commands(array_values($commands));
    }

    protected function registerMakeExtensionCommand($command)
    {
        $this->app->singleton($command, function($app) {
            return new Console\MakeExtension($app['files']);
        });
    }

    protected function registerMakeComponentCommand($command)
    {
        $this->app->singleton($command, function($app) {
            return new Console\MakeComponent($app['files']);
        });
    }

    protected function registerMakeControllerCommand($command)
    {
        $this->app->singleton($command, function($app) {
            return new Console\MakeController($app['files']);
        });
    }

    protected function registerMakeModelCommand($command)
    {
        $this->app->singleton($command, function($app) {
            return new Console\MakeModel($app['files']);
        });
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return array_values($this->commands);
    }
}
