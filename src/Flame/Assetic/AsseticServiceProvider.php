<?php

namespace Igniter\Flame\Assetic;

class AsseticServiceProvider extends \Illuminate\Support\ServiceProvider
{
    public function register()
    {
        $this->app->singleton(AssetManager::class);
    }
}
