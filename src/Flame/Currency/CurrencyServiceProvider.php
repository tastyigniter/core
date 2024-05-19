<?php

namespace Igniter\Flame\Currency;

use Igniter\Flame\Currency\Middleware\CurrencyMiddleware;
use Illuminate\Support\ServiceProvider;

class CurrencyServiceProvider extends ServiceProvider
{
    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->registerMiddlewareAlias();

        $this->registerCurrency();
        $this->registerCurrencyCommands();

        $this->registerConverter();
    }

    protected function registerMiddlewareAlias()
    {
        $this->app[\Illuminate\Routing\Router::class]->aliasMiddleware(
            'currency', CurrencyMiddleware::class
        );
    }

    /**
     * Register currency provider.
     *
     * @return void
     */
    public function registerCurrency()
    {
        $this->app->bind(Currency::class, 'currency');

        $this->app->singleton('currency', function($app) {
            $this->app['events']->dispatch('currency.beforeRegister', [$this]);

            return new Currency(
                $app->config->get('igniter-currency', []),
                $app['cache']
            );
        });
    }

    /**
     * Register currency commands.
     *
     * @return void
     */
    public function registerCurrencyCommands()
    {
        $this->commands([
            Console\Cleanup::class,
            Console\Update::class,
        ]);
    }

    protected function registerConverter()
    {
        $this->app->singleton('currency.converter', function($app) {
            return new Converter($app);
        });
    }
}
