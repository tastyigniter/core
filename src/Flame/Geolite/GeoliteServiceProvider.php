<?php

declare(strict_types=1);

namespace Igniter\Flame\Geolite;

use GuzzleHttp\Client;
use Illuminate\Foundation\AliasLoader;
use Illuminate\Support\ServiceProvider;

class GeoliteServiceProvider extends ServiceProvider
{
    /**
     * Register the service provider.
     */
    public function register(): void
    {
        $this->app->bind('geocoder', Geocoder::class);
        $this->app->bind(Geolite::class, 'geolite');

        $this->app->singleton('geocoder', function($app): Geocoder {
            return new Geocoder($app);
        });

        $this->app->singleton('geolite', function(): Geolite {
            return new Geolite;
        });

        $this->app->singleton('geocoder.client', function(): Client {
            return new Client;
        });

        $aliasLoader = AliasLoader::getInstance();
        $aliasLoader->alias('Geocoder', Facades\Geocoder::class);
        $aliasLoader->alias('Geolite', Facades\Geolite::class);
    }
}
