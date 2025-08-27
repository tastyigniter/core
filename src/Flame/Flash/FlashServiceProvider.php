<?php

declare(strict_types=1);

namespace Igniter\Flame\Flash;

use Override;
use Illuminate\Support\ServiceProvider;

class FlashServiceProvider extends ServiceProvider
{
    /**
     * Register the service provider.
     */
    #[Override]
    public function register(): void
    {
        $this->app->bind(FlashStore::class);

        $this->app->singleton('flash', FlashBag::class);
    }
}
