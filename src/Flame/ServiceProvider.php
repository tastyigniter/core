<?php

namespace Igniter\Flame;

use Igniter\Flame\Exception\ErrorHandler;
use Igniter\Flame\Filesystem\Filesystem;
use Igniter\Igniter;
use Igniter\System\Classes\ComposerManifest;
use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Foundation\AliasLoader;
use Illuminate\Routing\Router;

class ServiceProvider extends \Illuminate\Support\ServiceProvider
{
    protected $root = __DIR__.'/../..';

    protected $providers = [
        \Igniter\Flame\ActivityLog\ActivityLogServiceProvider::class,
        \Igniter\Flame\Auth\AuthServiceProvider::class,
        \Igniter\Flame\Currency\CurrencyServiceProvider::class,
        \Igniter\Flame\Providers\ConsoleSupportServiceProvider::class,
        \Igniter\Flame\Database\DatabaseServiceProvider::class,
        \Igniter\Flame\Events\EventServiceProvider::class,
        \Igniter\Flame\Filesystem\FilesystemServiceProvider::class,
        \Igniter\Flame\Flash\FlashServiceProvider::class,
        \Igniter\Flame\Geolite\GeoliteServiceProvider::class,
        \Igniter\Flame\Html\HtmlServiceProvider::class,
        \Igniter\Flame\Providers\LogServiceProvider::class,
        \Igniter\Flame\Mail\MailServiceProvider::class,
        \Igniter\Flame\Pagic\PagicServiceProvider::class,
        \Igniter\Flame\Scaffold\ScaffoldServiceProvider::class,
        \Igniter\Flame\Setting\SettingServiceProvider::class,
        \Igniter\Flame\Translation\TranslationServiceProvider::class,
        \Igniter\Flame\Providers\UrlServiceProvider::class,
        \Igniter\Flame\View\ViewServiceProvider::class,
        \Igniter\Flame\Validation\ValidationServiceProvider::class,

        \Igniter\Flame\Providers\SystemServiceProvider::class,
        \Igniter\Flame\Providers\AdminServiceProvider::class,
        \Igniter\Flame\Providers\MainServiceProvider::class,
//        \Igniter\Flame\Providers\ExtensionServiceProvider::class,
    ];

    protected $configFiles = [
        'auth', 'cart', 'currency', 'geocoder', 'system',
    ];

    public function register()
    {
        $this->loadTranslationsFrom($this->root.'/resources/lang/', 'igniter');

        $this->bindPathsInContainer();

        $this->app[\Illuminate\Contracts\Http\Kernel::class]
            ->pushMiddleware(\Igniter\Flame\Setting\Middleware\SaveSetting::class);

        $this->app->make(Router::class)->middlewareGroup('web', [
            \Igniter\Flame\Translation\Middleware\Localization::class,
        ]);

        $this->registerSingletons();
        $this->registerFacadeAliases();
        $this->registerErrorHandler();
        $this->registerProviders();
    }

    public function boot()
    {
        $this->app->booted(function () {
            $this->loadRoutesFrom($this->root.'/routes/routes.php');
        });

        $this->publishConfigFiles();

        $this->publishes([
            $this->root.'/resources/lang' => app()->langPath().'/vendor/igniter',
        ], 'igniter-translations');
    }

    protected function publishConfigFiles()
    {
        collect($this->configFiles)->each(function ($config) {
            $this->publishes([$this->root.'/config/'.$config.'.php' => config_path('igniter/'.$config.'.php')], 'igniter');
        });
    }

    protected function registerProviders()
    {
        collect($this->providers)->each(function ($provider) {
            $this->app->register($provider);
        });
    }

    protected function registerSingletons()
    {
        $this->app->singleton('string', function () {
            return new \Igniter\Flame\Support\Str;
        });

        $this->app->instance(ComposerManifest::class, new ComposerManifest(
            new Filesystem,
            $this->app->basePath(),
            $this->app->bootstrapPath().'/cache/addons.php'
        ));
    }

    protected function registerFacadeAliases()
    {
        $loader = AliasLoader::getInstance();

        foreach ([
            'File' => \Igniter\Flame\Support\Facades\File::class,
            'Flash' => \Igniter\Flame\Flash\Facades\Flash::class,
            'Form' => \Igniter\Flame\Html\FormFacade::class,
            'Html' => \Igniter\Flame\Html\HtmlFacade::class,
            'Model' => \Igniter\Flame\Database\Model::class,
            'Parameter' => \Igniter\Flame\Setting\Facades\Parameter::class,
            'Setting' => \Igniter\Flame\Setting\Facades\Setting::class,
            'Str' => \Igniter\Flame\Support\Str::class,
            'SystemException' => \Igniter\Flame\Exception\SystemException::class,
            'ApplicationException' => \Igniter\Flame\Exception\ApplicationException::class,
            'AjaxException' => \Igniter\Flame\Exception\AjaxException::class,
            'ValidationException' => \Igniter\Flame\Exception\ValidationException::class,
        ] as $alias => $class) {
            $loader->alias($alias, $class);
        }
    }

    /*
     * Error handling for uncaught Exceptions
     */

    protected function registerErrorHandler()
    {
        $this->callAfterResolving(ExceptionHandler::class, function ($handler) {
            new ErrorHandler($handler);
        });
    }

    protected function bindPathsInContainer()
    {
        $this->app->instance('path.themes', Igniter::themesPath());
        $this->app->instance('path.assets', Igniter::assetsPath());
        $this->app->instance('path.temp', Igniter::tempPath());
    }
}
