<?php

namespace Igniter\System\Providers;

use Igniter\System\Classes\UpdateManager;
use Illuminate\Console\Events\CommandStarting;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\Event;

class EventServiceProvider extends \Illuminate\Foundation\Support\Providers\EventServiceProvider
{
    public function boot()
    {
        $this->loadConfiguration();
        $this->handleConsoleSchedule();

        // Allow system based cache clearing
        $this->handleCacheCleared();
    }

    protected function handleConsoleSchedule()
    {
        Event::listen('console.schedule', function (Schedule $schedule) {
            // Check for system updates every 12 hours
            $schedule->call(function () {
                resolve(UpdateManager::class)->requestUpdateListAndNotify();
            })->name('System Updates Checker')->cron('0 */12 * * *')->evenInMaintenanceMode();

            // Cleanup activity log
            $schedule->command('activitylog:cleanup')->name('Activity Log Cleanup')->daily();
        });
    }

    protected function handleCacheCleared()
    {
        Event::listen('cache:cleared', function () {
            \Igniter\System\Helpers\CacheHelper::clearInternal();
        });

        Event::listen(\Illuminate\Console\Events\CommandFinished::class, function ($event) {
            if ($event->command === 'clear-compiled') {
                \Igniter\System\Helpers\CacheHelper::clearCompiled();
            }
        });
    }

    protected function loadConfiguration()
    {
        Event::listen('currency.beforeRegister', function () {
            app('config')->set('currency.default', setting('default_currency_code'));
            app('config')->set('currency.converter', setting('currency_converter.api', 'openexchangerates'));
            app('config')->set('currency.converters.openexchangerates.apiKey', setting('currency_converter.oer.apiKey'));
            app('config')->set('currency.converters.fixerio.apiKey', setting('currency_converter.fixerio.apiKey'));
            app('config')->set('currency.ratesCacheDuration', setting('currency_converter.refreshInterval'));
            app('config')->set('currency.model', \Igniter\System\Models\Currency::class);
        });

        $this->app->resolving('translator.localization', function ($localization, $app) {
            $app['config']->set('localization.locale', setting('default_language', $app['config']['app.locale']));
            $app['config']->set('localization.supportedLocales', setting('supported_languages', []) ?: ['en']);
            $app['config']->set('localization.detectBrowserLocale', (bool)setting('detect_language', false));
        });

        $this->app->resolving('geocoder', function ($geocoder, $app) {
            $app['config']->set('geocoder.default', setting('default_geocoder'));

            $region = $app['country']->getCountryCodeById(setting('country_id'));
            $app['config']->set('geocoder.providers.google.region', $region);
            $app['config']->set('geocoder.providers.nominatim.region', $region);

            $app['config']->set('geocoder.providers.google.apiKey', setting('maps_api_key'));
            $app['config']->set('geocoder.precision', setting('geocoder_boundary_precision', 8));
        });

        Event::listen(CommandStarting::class, function () {
            config()->set('system.activityRecordsTTL', (int)setting('activity_log_timeout', 60));
        });
    }
}