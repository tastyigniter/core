<?php

declare(strict_types=1);

namespace Igniter\Flame\Currency;

use Igniter\Flame\Currency\Middleware\CurrencyMiddleware;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\ServiceProvider;

class CurrencyServiceProvider extends ServiceProvider
{
    /**
     * Register the service provider.
     */
    public function register(): void
    {
        $this->registerMiddlewareAlias();

        $this->registerCurrency();
        $this->registerCurrencyCommands();

        $this->registerConverter();
    }

    protected function registerMiddlewareAlias()
    {
        $this->app[\Illuminate\Routing\Router::class]->aliasMiddleware(
            'currency', CurrencyMiddleware::class,
        );
    }

    /**
     * Register currency provider.
     */
    public function registerCurrency(): void
    {
        $this->app->bind(Currency::class, 'currency');

        $this->app->singleton('currency', function(Application $app): Currency {
            $this->app['events']->dispatch('currency.beforeRegister', [$this]);

            return new Currency(
                $app->config['igniter-currency'] ?? [],
                $app['cache'],
            );
        });
    }

    /**
     * Register currency commands.
     */
    public function registerCurrencyCommands(): void
    {
        $this->commands([
            Console\Cleanup::class,
            Console\Update::class,
        ]);
    }

    protected function registerConverter()
    {
        $this->app->singleton('currency.converter', function($app): Converter {
            return new Converter($app);
        });
    }
}
