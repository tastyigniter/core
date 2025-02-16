<?php

declare(strict_types=1);

namespace Igniter\Flame\Flash;

use Illuminate\Support\ServiceProvider;

class FlashServiceProvider extends ServiceProvider
{
    /**
     * Register the service provider.
     */
    public function register(): void
    {
        $this->app->bind(FlashStore::class);

        $this->app->singleton('flash', FlashBag::class);
    }
}
