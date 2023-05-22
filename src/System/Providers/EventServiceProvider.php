<?php

namespace Igniter\System\Providers;

use Igniter\Flame\Providers\EventServiceProvider as FlameEventServiceProvider;
use Igniter\System\Models\Currency;
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
        $this->loadConfiguration();

        // Allow system based cache clearing
        $this->handleCacheCleared();
    }

    protected function handleCacheCleared()
    {
        Event::listen('cache:cleared', function () {
            \Igniter\System\Helpers\CacheHelper::clearInternal();
        });
    }

    protected function loadConfiguration()
    {
        Event::listen('currency.beforeRegister', function () {
            app('config')->set('currency.default', Currency::getDefaultKey());
            app('config')->set('currency.converter', setting('currency_converter.api', 'openexchangerates'));
            app('config')->set('currency.converters.openexchangerates.apiKey', setting('currency_converter.oer.apiKey'));
            app('config')->set('currency.converters.fixerio.apiKey', setting('currency_converter.fixerio.apiKey'));
            app('config')->set('currency.ratesCacheDuration', setting('currency_converter.refreshInterval'));
            app('config')->set('currency.model', Currency::class);
        });
    }
}