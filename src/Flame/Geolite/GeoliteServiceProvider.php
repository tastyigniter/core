<?php

namespace Igniter\Flame\Geolite;

use Illuminate\Foundation\AliasLoader;
use Illuminate\Support\ServiceProvider;

class GeoliteServiceProvider extends ServiceProvider
{
    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->app->bind('geocoder', Geocoder::class);
        $this->app->bind(Geolite::class, 'geolite');

        $this->app->singleton('geocoder', function($app) {
            return new Geocoder($app);
        });

        $this->app->singleton('geolite', function() {
            return new Geolite;
        });

        $aliasLoader = AliasLoader::getInstance();
        $aliasLoader->alias('Geocoder', Facades\Geocoder::class);
        $aliasLoader->alias('Geolite', Facades\Geolite::class);
    }
}
