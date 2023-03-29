<?php

namespace Igniter\System\Providers;

use Igniter\System\Classes\ExtensionManager;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

class ExtensionServiceProvider extends ServiceProvider
{
    public function register()
    {
        // Register all extensions
        resolve(ExtensionManager::class)->registerExtensions();

        // Allow extensions to use the scheduler
        Event::listen('console.schedule', function ($schedule) {
            $extensions = resolve(ExtensionManager::class)->getExtensions();
            foreach ($extensions as $extension) {
                if (method_exists($extension, 'registerSchedule')) {
                    $extension->registerSchedule($schedule);
                }
            }
        });
    }

    public function boot()
    {
        resolve(ExtensionManager::class)->bootExtensions();
    }
}