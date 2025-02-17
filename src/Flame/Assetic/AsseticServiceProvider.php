<?php

declare(strict_types=1);

namespace Igniter\Flame\Assetic;

use Illuminate\Support\ServiceProvider;

class AsseticServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(AssetManager::class);
    }
}
