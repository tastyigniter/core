<?php

declare(strict_types=1);

namespace Igniter\Flame\Providers;

use Override;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as IlluminateEventServiceProvider;

abstract class ConsoleServiceProvider extends IlluminateEventServiceProvider
{
    protected $commands = [];

    #[Override]
    public function register(): void
    {
        parent::register();

        foreach ($this->commands as $command => $class) {
            if (is_string($command)) {
                $key = 'command.igniter.'.$command;
                $this->app->singleton($key, $class);
            } else {
                $key = $class;
                $this->app->singleton($class);
            }

            $this->commands($key);
        }
    }
}
