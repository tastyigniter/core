<?php

namespace Igniter;

class Igniter
{
    /**
     * The base path for themes.
     *
     * @var string
     */
    protected static $themesPath;

    /**
     * The base path for assets.
     *
     * @var string
     */
    protected static $assetsPath;

    /**
     * The base path for temporary directory.
     *
     * @var string
     */
    protected static $tempPath;

    /**
     * The request execution context (main, admin)
     *
     * @var string
     */
    protected static $appContext;

    /**
     * Indicates if the application has a valid database
     * connection and "settings" table.
     *
     * @var string
     */
    protected static $hasDatabase;

    protected static $migrationPaths = [
        'igniter.system' => __DIR__.'/System/Database/Migrations',
        'igniter.admin' => __DIR__.'/Admin/Database/Migrations',
        'igniter.main' => __DIR__.'/Main/Database/Migrations',
    ];

    /**
     * Sets the execution context
     *
     * @param string $context
     *
     * @return static
     */
    public static function setAppContext($context)
    {
        static::$appContext = $context;

        return new static;
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
     * Set the assets path for the application.
     *
     * @param string $path
     *
     * @return $this
     */
    public static function useAssetsPath($path)
    {
        static::$assetsPath = $path;

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
        return static::$appContext ?: static::resolveAppContext();
    }

    /**
     * Returns true if a database connection is present.
     * @return bool
     */
    public static function hasDatabase()
    {
        try {
            $hasDatabase = is_null(static::$hasDatabase)
                ? resolve('db.connection')->getSchemaBuilder()->hasTable('settings')
                : static::$hasDatabase;
        }
        catch (\Exception $ex) {
            $hasDatabase = false;
        }

        return static::$hasDatabase = $hasDatabase;
    }

    /**
     * Get the path to the themes directory.
     *
     * @return string
     */
    public static function themesPath()
    {
        return static::$themesPath ?: config('igniter.system.themesPath', base_path('themes'));
    }

    /**
     * Get the path to the themes directory.
     *
     * @return string
     */
    public static function assetsPath()
    {
        return static::$assetsPath ?: config('igniter.system.assetsPath', base_path('assets'));
    }

    /**
     * Get the path to the themes directory.
     *
     * @return string
     */
    public static function tempPath()
    {
        return static::$tempPath ?: config('igniter.system.tempPath', base_path('storage/temp'));
    }

    /**
     * Register database migration namespace.
     *
     * @param string $path
     * @return void
     */
    public static function addMigrationPath($path, $group)
    {
        static::$migrationPaths[$group] = $path;
    }

    /**
     * Get the database migration namespaces.
     *
     * @return array
     */
    public static function migrationPath()
    {
        return static::$migrationPaths;
    }

    public static function rootPath()
    {
        return base_path('vendor/tastyigniter/core');
    }

    public static function resourcesPath()
    {
        return base_path('vendor/tastyigniter/core/resources');
    }

    public static function getSeedRecords($name)
    {
        return json_decode(file_get_contents(__DIR__.'/../database/records/'.$name.'.json'), true);
    }

    protected static function resolveAppContext()
    {
        $requestPath = normalize_uri(request()->path());
        $adminUri = normalize_uri(config('igniter.system.adminUri', 'admin'));

        return static::$appContext = starts_with($requestPath, $adminUri) ? 'admin' : 'main';
    }
}
