<?php

declare(strict_types=1);

namespace Igniter\Flame\Assetic;

class AsseticServiceProvider extends \Illuminate\Support\ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(AssetManager::class);
    }
}
