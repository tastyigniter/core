<?php

declare(strict_types=1);

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
     */
    public function register(): void
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
}
