<?php

declare(strict_types=1);

namespace Igniter\Flame\Providers;

use Illuminate\Console\Application;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Providers\ArtisanServiceProvider as BaseServiceProvider;

class ArtisanServiceProvider extends BaseServiceProvider
{
    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            Application::starting(function(): void {
                $this->app['events']->dispatch('console.schedule', [$this->app->make(Schedule::class)]);
            });
        }
    }
}
