<?php

namespace Igniter\Flame\Providers;

use Igniter\System\Classes\ExtensionManager;
use Illuminate\Support\ServiceProvider;

class ExtensionServiceProvider extends ServiceProvider
{
    public function register()
    {
        ExtensionManager::instance()->registerExtensions();
    }

    public function boot()
    {
        ExtensionManager::instance()->bootExtensions();
    }
}
