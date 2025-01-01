<?php

namespace Igniter\Flame\Flash;

use Illuminate\Support\ServiceProvider;

class FlashServiceProvider extends ServiceProvider
{
    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->app->bind(FlashStore::class);

        $this->app->singleton('flash', FlashBag::class);
    }
}
