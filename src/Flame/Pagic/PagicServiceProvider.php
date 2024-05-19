<?php

namespace Igniter\Flame\Pagic;

use Igniter\Flame\Pagic\Cache\FileSystem as FileCache;
use Igniter\Flame\Pagic\Source\SourceResolver;
use Illuminate\Support\ServiceProvider;

/**
 * Class PagicServiceProvider
 */
class PagicServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application events.
     *
     * @return void
     */
    public function boot()
    {
        Model::setSourceResolver($this->app['pagic.resolver']);

        Model::setEventDispatcher($this->app['events']);

        Model::setCacheManager($this->app['cache']);
    }

    /**
     * Register the service provider.
     * @return void
     */
    public function register()
    {
        $this->app->singleton('pagic.resolver', function() {
            return new SourceResolver;
        });

        $this->app->singleton(Router::class);

        $this->app->singleton(FileCache::class, function() {
            return new FileCache(config('igniter-pagic.parsedTemplateCachePath'));
        });

        $this->app->singleton('pagic', function() {
            return new Environment(new Loader, [
                'debug' => config('app.debug', false),
                'cache' => new FileCache(config('view.compiled')),
                'templateClass' => Template::class,
            ]);
        });
    }
}
