<?php

namespace Igniter\Flame;

use Igniter\Flame\Filesystem\Filesystem;
use Igniter\User\Models\Customer;
use Igniter\User\Models\User;
use Illuminate\View\Factory;

class Igniter
{
    const VERSION = 'v4.0.0-beta';

    /**
     * The base path for extensions.
     *
     * @var string
     */
    protected static $extensionsPath;

    /**
     * The base path for themes.
     *
     * @var string
     */
    protected static $themesPath;

    /**
     * The base path for temporary directory.
     *
     * @var string
     */
    protected static $tempPath;

    /**
     * Indicates if the application has a valid database
     * connection and "settings" table.
     *
     * @var string
     */
    protected static $hasDatabase;

    protected static $coreMigrationPaths = [
        'igniter.system' => [__DIR__.'/../../database/migrations/system'],
        'igniter.admin' => [__DIR__.'/../../database/migrations/admin'],
        'igniter.main' => [__DIR__.'/../../database/migrations/main'],
    ];

    protected static $migrationPaths = [];

    protected static $controllerPaths = [];

    protected static array $ignoreMigrations = [];

    protected static array $prunableModels = [];

    protected static bool $disableThemeRoutes = false;

    protected static bool $autoloadExtensions = true;

    /**
     * Set the extensions path for the application.
     *
     * @param string $path
     *
     * @return $this
     */
    public static function useExtensionsPath($path)
    {
        static::$extensionsPath = $path;
    }

    /**
     * Set the themes path for the application.
     *
     * @param string $path
     *
     * @return static
     */
    public static function useThemesPath($path)
    {
        static::$themesPath = $path;

        return new static;
    }

    /**
     * Set the temporary storage path for the application.
     *
     * @param string $path
     *
     * @return $this
     */
    public static function useTempPath($path)
    {
        static::$tempPath = $path;

        return new static;
    }

    /**
     * Determine if we are running in the admin area.
     *
     * @return bool
     */
    public static function runningInAdmin()
    {
        $requestPath = str_finish(normalize_uri(request()->path()), '/');
        $adminUri = str_finish(normalize_uri(static::adminUri()), '/');

        return starts_with($requestPath, $adminUri);
    }

    /**
     * Returns true if a database connection is present.
     * @return bool
     */
    public static function hasDatabase($force = false)
    {
        try {
            if ($force || !static::$hasDatabase) {
                $schema = resolve('db.connection')->getSchemaBuilder();
                static::$hasDatabase = $schema->hasTable('settings') && $schema->hasTable('extension_settings');
            }
        } catch (\Exception) {
            static::$hasDatabase = false;
        }

        return static::$hasDatabase;
    }

    /**
     * Get the path to the extensions directory.
     *
     * @return string
     */
    public static function extensionsPath()
    {
        return static::$extensionsPath ?: config('igniter-system.extensionsPath', base_path('extensions'));
    }

    /**
     * Get the path to the themes directory.
     *
     * @return string
     */
    public static function themesPath()
    {
        return static::$themesPath ?: config('igniter-system.themesPath', base_path('themes'));
    }

    /**
     * Get the path to the storage/temp directory.
     *
     * @return string
     */
    public static function tempPath()
    {
        return static::$tempPath ?: config('igniter-system.tempPath', base_path('storage/temp'));
    }

    /**
     * Register database migration namespace.
     *
     * @return void
     */
    public static function loadMigrationsFrom(string $path, string $namespace)
    {
        static::$migrationPaths[$namespace] = $path;
    }

    public function ignoreMigrations(string $namespace = '*')
    {
        static::$ignoreMigrations[] = $namespace;

        return new static;
    }

    /**
     * Get the database migration namespaces.
     *
     * @return array
     */
    public static function migrationPath()
    {
        return !in_array('*', static::$ignoreMigrations)
            ? array_except(static::$migrationPaths, static::$ignoreMigrations)
            : [];
    }

    public static function coreMigrationPath()
    {
        return !in_array('*', static::$ignoreMigrations)
            ? array_except(static::$coreMigrationPaths, static::$ignoreMigrations)
            : [];
    }

    public static function getSeedRecords($name)
    {
        return json_decode(file_get_contents(__DIR__.'/../../database/records/'.$name.'.json'), true);
    }

    /**
     * Get the path to the cached addons.php file.
     *
     * @return string
     */
    public static function getCachedAddonsPath()
    {
        return app()->bootstrapPath().'/cache/addons.php';
    }

    /**
     * Get the path to the cached classes.php file.
     *
     * @return string
     */
    public static function getCachedClassesPath()
    {
        return app()->bootstrapPath().'/cache/classes.php';
    }

    public static function loadResourcesFrom(string $path, ?string $namespace = null)
    {
        $callback = function(Filesystem $files) use ($path, $namespace) {
            $files->addPathSymbol($namespace, $path);
        };

        app()->afterResolving(Filesystem::class, $callback);

        if (app()->resolved(Filesystem::class)) {
            $callback(resolve(Filesystem::class), app());
        }
    }

    public static function loadControllersFrom(string $path, string $namespace)
    {
        static::$controllerPaths[$namespace] = $path;
    }

    public static function loadViewsFrom(string|array $path, string $namespace)
    {
        $callback = function(Factory $view) use ($path, $namespace) {
            $view->addNamespace($namespace, $path);
        };

        app()->afterResolving('view', $callback);

        if (app()->resolved('view')) {
            $callback(app('view'), app());
        }
    }

    public static function controllerPath()
    {
        return static::$controllerPaths;
    }

    public static function uri()
    {
        return config('igniter-routes.uri');
    }

    public static function adminUri()
    {
        return config('igniter-routes.adminUri', '/admin');
    }

    public static function isUser($user)
    {
        return static::isAdminUser($user) || static::isCustomer($user);
    }

    public static function isCustomer($user)
    {
        return $user instanceof Customer;
    }

    public static function isAdminUser($user)
    {
        return $user instanceof User;
    }

    public static function version()
    {
        return static::VERSION;
    }

    public static function prunableModel(string|array $modelClass)
    {
        if (is_string($modelClass)) {
            $modelClass = [$modelClass];
        }

        static::$prunableModels = array_merge(static::$prunableModels, $modelClass);
    }

    public static function prunableModels(): array
    {
        return static::$prunableModels;
    }

    public static function disableThemeRoutes(?bool $value = null)
    {
        if (is_null($value)) {
            return static::$disableThemeRoutes;
        }

        static::$disableThemeRoutes = $value;
    }

    public static function autoloadExtensions(?bool $value = null)
    {
        if (is_null($value)) {
            return static::$autoloadExtensions;
        }

        static::$autoloadExtensions = $value;
    }
}
