<?php

namespace Igniter\System\Classes;

use Igniter\Flame\Exception\SystemException;
use Igniter\Flame\Igniter;
use Igniter\Flame\Support\Facades\File;
use Igniter\System\Helpers\SystemHelper;
use Illuminate\Support\ServiceProvider;
use ReflectionClass;

/**
 * Base Extension Class
 */
abstract class BaseExtension extends ServiceProvider
{
    /**
     * @var array
     */
    protected $config;

    /**
     * @var bool Determine if this extension should be loaded (false) or not (true).
     */
    public $disabled = false;

    public function __construct($app)
    {
        $this->app = $app;
        $this->booting(function () {
            $this->bootingExtension();
        });
    }

    public function bootingExtension()
    {
        $reflector = new ReflectionClass(get_class($this));

        $config = $this->extensionMeta();
        $extensionPath = array_get($config, 'directory', dirname($reflector->getFileName(), 2));
        $extensionNamespace = array_get($config, 'namespace', $namespace = $reflector->getNamespaceName());
        $extensionCode = array_get($config, 'code', str_replace('\\', '.', $namespace));

        // Register resources path symbol
        if (File::isDirectory($resourcesPath = $extensionPath.'/resources')) {
            Igniter::loadResourcesFrom($resourcesPath, $extensionCode);
        }

        if (File::isDirectory($langPath = $extensionPath.'/resources/lang')) {
            $this->loadTranslationsFrom($langPath, $extensionCode);
        }

        // Register migrations path
        if (File::isDirectory($migrationPath = $extensionPath.'/database/migrations')) {
            Igniter::loadMigrationsFrom($migrationPath, $extensionCode);
        }

        if ($this->disabled) {
            $this->app->bindMethod(static::class.'@boot', function () {
                return null;
            });

            return null;
        }

        // Register controller path
        if (File::isDirectory($controllerPath = $extensionPath.'/src/Http/Controllers')) {
            Igniter::loadControllersFrom($controllerPath, $extensionNamespace.'Http\\Controllers');
        }

        // Register views path
        if (File::isDirectory($viewsPath = $extensionPath.'/views') ||
            File::isDirectory($viewsPath = $extensionPath.'/resources/views')) {
            $this->loadViewsFrom($viewsPath, $extensionCode);
        }

        // Add routes, if available
        if (File::exists($routesFile = $extensionPath.'/routes.php') ||
            File::exists($routesFile = $extensionPath.'/routes/routes.php')) {
            $this->loadRoutesFrom($routesFile);
        }
    }

    /**
     * Returns information about this extension
     * @return array
     */
    public function extensionMeta()
    {
        return $this->getConfigFromFile();
    }

    /**
     * Registers any front-end components implemented in this extension.
     * The components must be returned in the following format:
     * ['path/to/class' => ['code' => 'component_code']]
     * @return array
     */
    public function registerComponents()
    {
        return [];
    }

    /**
     * Registers any payment gateway implemented in this extension.
     * The payment gateway must be returned in the following format:
     * ['path/to/class' => 'alias']
     * @return array
     */
    public function registerPaymentGateways()
    {
        return [];
    }

    /**
     * Registers back-end navigation menu items for this extension.
     * @return array
     */
    public function registerNavigation()
    {
        return [];
    }

    /**
     * Registers any back-end permissions used by this extension.
     * @return array
     */
    public function registerPermissions()
    {
        return [];
    }

    /**
     * Registers the back-end setting links used by this extension.
     * @return array
     */
    public function registerSettings()
    {
        return [];
    }

    /**
     * Registers scheduled tasks that are executed on a regular basis.
     *
     * @param string $schedule
     * @return void
     */
    public function registerSchedule($schedule)
    {
    }

    /**
     * Registers any dashboard widgets provided by this extension.
     * @return array
     */
    public function registerDashboardWidgets()
    {
        return [];
    }

    /**
     * Registers any form widgets implemented in this extension.
     * The widgets must be returned in the following format:
     * ['className1' => 'alias'],
     * ['className2' => 'anotherAlias']
     * @return array
     */
    public function registerFormWidgets()
    {
        return [];
    }

    /**
     * Registers any mail templates implemented by this extension.
     * The templates must be returned in the following format:
     * [
     *  'igniter.demo::mail.registration' => 'Registration email to customer.',
     * ]
     * The array key will be used as the template code
     * @return array
     */
    public function registerMailTemplates()
    {
        return [];
    }

    /**
     * Registers a new console (artisan) command
     *
     * @param string $key The command name
     * @param string $class The command class
     * @return void
     */
    public function registerConsoleCommand($key, $class)
    {
        $key = 'command.'.$key;

        $this->app->singleton($key, $class);

        $this->commands($key);
    }

    /**
     * Registers any validation rule implemented by this extension.
     * The widgets must be returned in the following format:
     * ['rule' => 'className1'],
     * ['rule' => 'className2']
     * @return array
     */
    public function registerValidationRules()
    {
        return [];
    }

    public function listRequires()
    {
        return SystemHelper::parsePackageCodes(array_get($this->extensionMeta(), 'require', []));
    }

    /**
     * Read configuration from Config file
     *
     * @return array|bool
     * @throws SystemException
     */
    protected function getConfigFromFile()
    {
        if (isset($this->config)) {
            return $this->config;
        }

        $className = get_class($this);
        $configPath = str_before(dirname(File::fromClass($className)), '/src');
        $extensionCode = strtolower(str_replace('/', '.', dirname(str_replace('\\', '/', $className))));

        if (File::exists($configFile = $configPath.'/extension.json')) {
            $config = json_decode(File::get($configFile), true) ?? [];
        } elseif ($packageConfig = array_get(resolve(PackageManifest::class)->extensions(), $extensionCode)) {
            $config = $packageConfig;
        } elseif (File::exists($configPath.'/composer.json')) {
            $config = resolve(ComposerManager::class)->getConfig($configPath);
        } else {
            throw new SystemException("The configuration file for extension <b>{$className}</b> does not exist. ".
                'Create the file or override extensionMeta() method in the extension class.');
        }

        return $this->config = $config;
    }

    protected function getConfigFromComposerJson($configFile)
    {
        $composer = json_decode(File::get($configFile), true) ?? [];

        if (!$config = array_get($composer, 'extra.tastyigniter-extension', [])) {
            return $config;
        }

        if (array_key_exists('description', $composer)) {
            $config['description'] = $composer['description'];
        }

        if (array_key_exists('authors', $composer)) {
            $config['author'] = $composer['authors'][0]['name'];
        }

        if (!array_key_exists('homepage', $config) && array_key_exists('homepage', $composer)) {
            $config['homepage'] = $composer['homepage'];
        }

        return $config;
    }
}
