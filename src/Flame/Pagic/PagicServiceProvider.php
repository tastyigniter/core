<?php

declare(strict_types=1);

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
     */
    public function boot(): void
    {
        Model::setSourceResolver($this->app['pagic.resolver']);

        Model::setEventDispatcher($this->app['events']);

        Model::setCacheManager($this->app['cache']);

        $this->app->terminating(function() {
            Model::clearExtendedClasses();
            Model::clearBootedModels();
            Finder::clearInternalCache();
        });
    }

    /**
     * Register the service provider.
     */
    public function register(): void
    {
        $this->app->singleton('pagic.resolver', function(): SourceResolver {
            return new SourceResolver;
        });

        $this->app->singleton(Router::class);

        $this->app->singleton(FileCache::class, function(): FileCache {
            return new FileCache(config('igniter-pagic.parsedTemplateCachePath'));
        });

        $this->app->bind(Environment::class, 'pagic');

        $this->app->singleton('pagic', function(): Environment {
            return new Environment(new Loader, [
                'debug' => config('app.debug', false),
                'cache' => new FileCache(config('view.compiled')),
                'templateClass' => Template::class,
            ]);
        });
    }
}
