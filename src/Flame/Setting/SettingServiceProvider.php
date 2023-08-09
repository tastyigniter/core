<?php

namespace Igniter\Flame\Setting;

use Igniter\Flame\Setting\Middleware\SaveSetting;
use Illuminate\Support\ServiceProvider;

class SettingServiceProvider extends ServiceProvider
{
    protected $defer = true;

    /**
     * Register the service provider.
     * @return void
     */
    public function register()
    {
        $this->app->singleton('system.setting', function ($app) {
            $store = new DatabaseSettingStore($app['db'], $app['cache.store']);
            $store->setCacheKey('igniter.setting.system');
            $store->setExtraColumns(['sort' => 'config']);

            return $store;
        });

        $this->app->singleton('system.parameter', function ($app) {
            $store = new DatabaseSettingStore($app['db'], $app['cache.store']);
            $store->setCacheKey('igniter.setting.parameters');
            $store->setExtraColumns(['sort' => 'prefs']);

            return $store;
        });
    }

    public function provides()
    {
        return ['system.setting', 'system.parameter'];
    }
}
