<?php

namespace Igniter\System\Providers;

use Facades\Igniter\System\Helpers\CacheHelper;
use Igniter\Flame\Providers\EventServiceProvider as FlameEventServiceProvider;
use Igniter\System\Models\Language;
use Igniter\System\Models\Observers\LanguageObserver;
use Illuminate\Support\Facades\Event;

class EventServiceProvider extends FlameEventServiceProvider
{
    protected $observers = [
        Language::class => LanguageObserver::class,
    ];

    public function boot()
    {
        // Allow system based cache clearing
        $this->handleCacheCleared();
    }

    protected function handleCacheCleared()
    {
        Event::listen('cache:cleared', function() {
            CacheHelper::clearInternal();
        });
    }
}
