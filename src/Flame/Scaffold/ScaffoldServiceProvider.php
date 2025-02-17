<?php

declare(strict_types=1);

namespace Igniter\Flame\Scaffold;

use Igniter\Flame\Scaffold\Console\MakeComponent;
use Igniter\Flame\Scaffold\Console\MakeController;
use Igniter\Flame\Scaffold\Console\MakeExtension;
use Igniter\Flame\Scaffold\Console\MakeModel;
use Illuminate\Contracts\Foundation\Application;
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
        $this->app->singleton($command, function(Application $app): MakeExtension {
            return new Console\MakeExtension($app['files']);
        });
    }

    protected function registerMakeComponentCommand($command)
    {
        $this->app->singleton($command, function(Application $app): MakeComponent {
            return new Console\MakeComponent($app['files']);
        });
    }

    protected function registerMakeControllerCommand($command)
    {
        $this->app->singleton($command, function(Application $app): MakeController {
            return new Console\MakeController($app['files']);
        });
    }

    protected function registerMakeModelCommand($command)
    {
        $this->app->singleton($command, function(Application $app): MakeModel {
            return new Console\MakeModel($app['files']);
        });
    }
}
