<?php

namespace Igniter\System;

use Igniter\Flame\Flash\FlashBag;
use Igniter\Flame\Igniter;
use Igniter\Flame\Providers\AppServiceProvider;
use Igniter\Flame\Setting\Facades\Setting;
use Igniter\System\Models\Country;
use Igniter\System\Models\Language;
use Igniter\System\Models\RequestLog;
use Igniter\System\Template\Extension\BladeExtension;
use Igniter\User\Models\Notification;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Database\Events\MigrationsStarted;
use Illuminate\Foundation\AliasLoader;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class ServiceProvider extends AppServiceProvider
{
    /**
     * Register the service provider.
     * @return void
     */
    public function register()
    {
        $this->registerSingletons();
        $this->registerFacadeAliases();
        $this->registerBladeDirectives();

        $this->app->register(Providers\ConsoleServiceProvider::class);
        $this->app->register(Providers\ExtensionServiceProvider::class);
        $this->app->register(Providers\EventServiceProvider::class);
        $this->app->register(Providers\FormServiceProvider::class);
        $this->app->register(Providers\MailServiceProvider::class);
        $this->app->register(Providers\PaginationServiceProvider::class);
        $this->app->register(Providers\PermissionServiceProvider::class);
        $this->app->register(Providers\ValidationServiceProvider::class);
        $this->app->register(\Igniter\Admin\ServiceProvider::class);
        $this->app->register(\Igniter\Main\ServiceProvider::class);
    }

    /**
     * Bootstrap the module events.
     * @return void
     */
    public function boot()
    {
        $this->loadViewsFrom($this->root.'/resources/views/system', 'igniter.system');
        $this->loadAnonymousComponentFrom('igniter.system::_components.', 'igniter.system');
        $this->loadResourcesFrom($this->root.'/resources', 'igniter');

        $this->definePrunableModels();
        $this->defineEloquentMorphMaps();
        $this->resolveFlashSessionKey();

        $this->updateTimezone();
        $this->loadLocalizationConfiguration();
        $this->loadGeocoderConfiguration();

        $this->app['events']->listen(MigrationsStarted::class, function () {
            Schema::disableForeignKeyConstraints();
        });

        if (!Igniter::runningInAdmin()) {
            $this->app['events']->listen('exception.beforeRender', function ($exception, $httpCode, $request) {
                if ($exception instanceof ModelNotFoundException || $exception instanceof NotFoundHttpException) {
                    RequestLog::createLog();
                }
            });
        }
    }

    protected function updateTimezone()
    {
        date_default_timezone_set(Setting::get('timezone', Config::get('app.timezone', 'UTC')));
    }

    /**
     * Register singletons
     */
    protected function registerSingletons()
    {
        $this->app->singleton('assets', function () {
            return new Libraries\Assets();
        });

        $this->app->singleton('country', function ($app) {
            return new Libraries\Country;
        });

        $this->app->instance('path.uploads', base_path(Config::get('igniter-system.assets.media.path', 'assets/media/uploads')));

        $this->app->singleton(Classes\ComponentManager::class);
        $this->tapSingleton(Classes\ComposerManager::class);
        $this->app->singleton(Classes\ExtensionManager::class);
        $this->app->singleton(Classes\HubManager::class);
        $this->tapSingleton(Classes\LanguageManager::class);
        $this->app->singleton(Classes\MailManager::class);
        $this->app->singleton(Classes\UpdateManager::class);
    }

    protected function registerFacadeAliases()
    {
        $loader = AliasLoader::getInstance();

        foreach ([
            'Assets' => \Igniter\System\Facades\Assets::class,
            'Country' => \Igniter\System\Facades\Country::class,
        ] as $alias => $class) {
            $loader->alias($alias, $class);
        }
    }

    protected function defineEloquentMorphMaps()
    {
        Relation::morphMap([
            'countries' => \Igniter\System\Models\Country::class,
            'currencies' => \Igniter\System\Models\Currency::class,
            'extensions' => \Igniter\System\Models\Extension::class,
            'languages' => \Igniter\System\Models\Language::class,
            'mail_layouts' => \Igniter\System\Models\MailLayout::class,
            'mail_templates' => \Igniter\System\Models\MailTemplate::class,
            'pages' => \Igniter\System\Models\Page::class,
            'settings' => \Igniter\System\Models\Settings::class,
            'themes' => \Igniter\Main\Models\Theme::class,
        ]);
    }

    protected function registerBladeDirectives()
    {
        $this->callAfterResolving('blade.compiler', function ($compiler, $app) {
            (new BladeExtension)->register();
        });
    }

    protected function loadLocalizationConfiguration()
    {
        $this->app->resolving('translator.localization', function ($localization, $app) {
            $app['config']->set('localization.locale', Language::getDefault()?->code ?? $app['config']['app.locale']);
            $app['config']->set('localization.supportedLocales', setting('supported_languages', []) ?: ['en']);
            $app['config']->set('localization.detectBrowserLocale', (bool)setting('detect_language', false));
        });
    }

    protected function loadGeocoderConfiguration()
    {
        $this->app->resolving('geocoder', function ($geocoder, $app) {
            $app['config']->set('geocoder.default', setting('default_geocoder'));

            $region = $app['country']->getCountryCodeById(Country::getDefaultKey());
            $app['config']->set('geocoder.providers.google.region', $region);
            $app['config']->set('geocoder.providers.nominatim.region', $region);

            $app['config']->set('geocoder.providers.google.apiKey', setting('maps_api_key'));
            $app['config']->set('geocoder.precision', setting('geocoder_boundary_precision', 8));
        });
    }

    protected function resolveFlashSessionKey()
    {
        $this->app->resolving('flash', function (FlashBag $flash) {
            $flash->setSessionKey(Igniter::runningInAdmin() ? 'flash_data_admin' : 'flash_data_main');
        });
    }

    protected function definePrunableModels()
    {
        Igniter::prunableModel([
            Notification::class,
            RequestLog::class,
        ]);
    }
}
