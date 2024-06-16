<?php

namespace Igniter\Flame\Translation;

use Igniter\Flame\Translation\Drivers\Database;
use Illuminate\Translation\TranslationServiceProvider as BaseServiceProvider;

class TranslationServiceProvider extends BaseServiceProvider
{
    public function register()
    {
        $this->app->register(BaseServiceProvider::class);

        $this->registerLoader();

        $this->app->singleton('translator', function($app) {
            $loader = $app['translation.loader'];

            // When registering the translator component, we'll need to set the default
            // locale as well as the fallback locale. So, we'll grab the application
            // configuration so we can easily get both of these values from there.
            $locale = $app['config']['app.locale'];

            $loader->addDriver(Database::class);

            $trans = new Translator($loader, $locale);

            $trans->setFallback($app['config']['app.fallback_locale']);

            return $trans;
        });

        $this->app->singleton('translator.localization', function($app) {
            return new Localization($app['request'], $app['config']);
        });
    }

    public function registerLoader()
    {
        $this->app->singleton('translation.loader', function($app) {
            $reflection = new \ReflectionClass(BaseServiceProvider::class);
            $dir = dirname($reflection->getFileName());

            return new FileLoader($app['files'], [$dir.'/lang', $app['path.lang']]);
        });
    }
}
