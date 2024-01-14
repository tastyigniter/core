<?php

namespace Igniter\System\Classes;

use Igniter\Flame\Exception\ApplicationException;
use Igniter\Flame\Exception\SystemException;
use Igniter\Flame\Igniter;
use Igniter\Flame\Support\Facades\File;
use Igniter\System\Helpers\SystemHelper;
use Igniter\System\Models\Extension;
use ZipArchive;

/**
 * Modules class for TastyIgniter.
 * Provides utility functions for working with modules.
 */
class ExtensionManager
{
    /** Used for storing extension information objects. */
    protected array $extensions = [];

    protected array $disabledExtensions = [];

    /** Cache of registration method results. */
    protected array $registrationMethodCache = [];

    /** Array of extensions and their directory paths. */
    protected array $paths = [];

    /** Used Set whether extensions have been booted. */
    protected bool $booted = false;

    /** Used Set whether extensions have been registered. */
    protected bool $registered = false;

    protected static array $directories = [];

    public function __construct(protected PackageManifest $packageManifest)
    {
        $this->disabledExtensions = $this->packageManifest->disabledAddons();
        $this->loadExtensions();

        if (!Igniter::autoloadExtensions()) {
            $this->disableWithMissingDependencies();
        }
    }

    public static function addDirectory(string $directory)
    {
        self::$directories[] = $directory;
    }

    /**
     * Return the path to the extension and its specified folder.
     */
    public function path(string $extension, ?string $folder = null): string
    {
        $path = array_get($this->paths(), $extension);

        if (!is_null($folder)) {
            return $path.'/'.$folder;
        }

        return $path.'/';
    }

    /**
     * Return an associative array of files within one or more extensions.
     */
    public function files(?string $extensionName = null, ?string $subFolder = null): bool|array
    {
        $files = [];
        traceLog('Deprecated method, remove before v5');

        return $files;
    }

    /**
     * Search a extension folder for files.
     */
    public function filesPath(string $extensionName, ?string $path = null): array
    {
        traceLog('Deprecated method, remove before v5');

        return [];
    }

    /**
     * Returns an array of the folders in which extensions may be stored.
     */
    public function folders(): array
    {
        $paths = [];

        $directories = self::$directories;
        if (File::isDirectory($extensionsPath = Igniter::extensionsPath())) {
            array_unshift($directories, $extensionsPath);
        }

        foreach ($directories as $directory) {
            foreach (File::glob($directory.'/*/*/{extension,composer}.json', GLOB_BRACE) as $path) {
                $paths[] = dirname($path);
            }
        }

        return $paths;
    }

    /**
     * Returns a list of all extensions in the system.
     */
    public function listExtensions(): array
    {
        return array_keys($this->paths());
    }

    /**
     * Scans extensions to locate any dependencies that are not currently
     * installed. Returns an array of extension codes that are needed.
     */
    public function findMissingDependencies(): array
    {
        $result = $missing = [];
        foreach ($this->extensions as $code => $extension) {
            if (!$required = $this->getDependencies($extension)) {
                continue;
            }

            foreach ($required as $require) {
                if ($this->hasExtension($require)) {
                    continue;
                }

                if (!in_array($require, $missing)) {
                    $missing[] = $require;
                    $result[$code][] = $require;
                }
            }
        }

        return $result;
    }

    /**
     * Checks all extensions and their dependencies, if not met extensions
     * are disabled.
     */
    protected function disableWithMissingDependencies()
    {
        foreach ($this->extensions as $code => $extension) {
            if (!$required = $this->getDependencies($extension)) {
                continue;
            }

            $disable = false;
            foreach ($required as $require) {
                $extensionObj = $this->findExtension($require);
                if (!$extensionObj || $extensionObj->disabled) {
                    $disable = true;
                }
            }

            // Only disable extension with missing dependencies.
            if ($disable && !$extension->disabled) {
                $this->updateInstalledExtensions($code, false);
            }
        }
    }

    /**
     * Returns the extension codes that are required by the supplied extension.
     */
    public function getDependencies(string|BaseExtension $extension): array|false
    {
        if (is_string($extension) && (!$extension = $this->findExtension($extension))) {
            return false;
        }

        return array_keys($extension->listRequires());
    }

    /**
     * Sorts extensions, in the order that they should be actioned,
     * according to their given dependencies. Least required come first.
     */
    public function listByDependencies(?array $extensions = null): array
    {
        if (!is_array($extensions)) {
            $extensions = $this->getExtensions();
        }

        $result = [];
        $checklist = $extensions;

        $loopCount = 0;
        while (count($checklist) > 0) {
            if (++$loopCount > 999) {
                throw new ApplicationException('Too much recursion');
            }

            foreach ($checklist as $code => $extension) {
                $depends = $this->getDependencies($extension) ?: [];
                $depends = array_filter($depends, function ($dependCode) use ($extensions) {
                    return isset($extensions[$dependCode]);
                });

                $depends = array_diff($depends, $result);
                if (count($depends) > 0) {
                    continue;
                }

                $result[] = $code;
                unset($checklist[$code]);
            }
        }

        return $result;
    }

    /**
     * Create a Directory Map of all extensions
     */
    public function paths(): array
    {
        return $this->paths;
    }

    /**
     * Finds all available extensions and loads them in to the $extensions array.
     */
    public function loadExtensions(): array
    {
        $this->extensions = [];

        foreach ($this->folders() as $path) {
            $this->loadExtension($path);
        }

        $this->packageManifest->manifest = null;
        foreach ($this->packageManifest->extensions() as $config) {
            if (!File::exists($path = $this->packageManifest->getPackagePath(array_get($config, 'installPath')))) {
                logger()->warning('Extension not found: '.$path);
                continue;
            }

            $this->loadExtensionFromPackageManifest($path, $config);
        }

        return $this->extensions;
    }

    /**
     * Loads a single extension in to the manager.
     * @param string $path Eg: base_path().'/extensions/vendor/extension';
     * @throws \Igniter\Flame\Exception\SystemException
     */
    public function loadExtension(string $path): BaseExtension
    {
        $config = SystemHelper::extensionConfigFromFile($path);
        $namespace = array_get($config, 'namespace');
        $class = $namespace.'Extension';
        $identifier = array_get($config, 'code', $this->getIdentifier($namespace));

        throw_unless(
            $this->checkName($identifier),
            new SystemException('Extension code can only contain alphabets: '.$identifier),
        );

        if (isset($this->extensions[$identifier])) {
            return $this->extensions[$identifier];
        }

        if (File::isDirectory($path.'/src') && $loader = resolve(ComposerManager::class)->getLoader()) {
            $loader->setPsr4($namespace, $path.'/src');
        }

        $extension = $this->resolveExtension($identifier, $path, $class);

        // Check for disabled extensions
        if ($this->isDisabled($identifier)) {
            $extension->disabled = true;
        }

        $this->extensions[$identifier] = $extension;
        $this->paths[$identifier] = $path;

        return $extension;
    }

    protected function loadExtensionFromPackageManifest(string $path, array $config): BaseExtension
    {
        $code = array_get($config, 'code');
        $identifier = $this->getIdentifier($code);

        throw_unless(
            $this->checkName($identifier),
            new SystemException('Extension code can only contain alphabets: '.$identifier),
        );

        if (isset($this->extensions[$identifier])) {
            return $this->extensions[$identifier];
        }

        $class = array_get($config, 'namespace').'Extension';
        $extension = $this->resolveExtension($identifier, $path, $class);

        $extension->extensionMeta($config);

        // Check for disabled extensions
        if ($this->isDisabled($identifier)) {
            $extension->disabled = true;
        }

        $this->extensions[$identifier] = $extension;
        $this->paths[$identifier] = $path;

        return $extension;
    }

    public function resolveExtension(string $identifier, ?string $path, ?string $class)
    {
        throw_if(
            !$path || !File::isDirectory($path) || $path === base_path(),
            SystemException::class, 'Extension directory not found: '.$path
        );

        if (!$class || !class_exists($class)) {
            throw new \LogicException("Missing Extension class '$class' in '$identifier', create the Extension class to override extensionMeta() method.");
        }

        if (!is_subclass_of($class, BaseExtension::class)) {
            throw new \LogicException("Extension class '$class' must extend '".BaseExtension::class."'.");
        }

        return app()->resolveProvider($class);
    }

    /**
     * Returns an array with all registered extensions
     * The index is the extension name, the value is the extension object.
     */
    public function getExtensions(): array
    {
        $extensions = [];
        foreach ($this->extensions as $code => $extension) {
            if (!$extension->disabled) {
                $extensions[$code] = $extension;
            }
        }

        return $extensions;
    }

    /**
     * Returns a extension registration class based on its name.
     */
    public function findExtension(string $code): ?BaseExtension
    {
        if (!$this->hasExtension($code)) {
            return null;
        }

        return $this->extensions[$code];
    }

    /**
     * Checks to see if an extension name is well formed.
     */
    public function checkName(string $code): ?string
    {
        return (str_starts_with($code, '_') || preg_match('/\s/', $code)) ? null : $code;
    }

    public function getIdentifier(string $namespace): string
    {
        $namespace = trim($namespace, '\\');

        return strtolower(str_replace('\\', '.', $namespace));
    }

    public function getNamePath(string $code): string
    {
        return str_replace('.', '/', $code);
    }

    public function getExtensionPath(string $code, string $path = ''): string
    {
        return ($this->paths[$code] ?? null).$path;
    }

    /**
     * Checks to see if an extension has been registered.
     */
    public function hasExtension(string $code): bool
    {
        return isset($this->extensions[$code]);
    }

    public function hasVendor(string $path): bool
    {
        return array_key_exists($path, array_undot($this->paths()));
    }

    /**
     * Returns a flat array of extensions namespaces and their paths
     */
    public function namespaces(): array
    {
        $classNames = [];

        foreach ($this->paths as $namespace => $path) {
            $namespace = str_replace('.', '\\', $namespace);
            $classNames[$namespace] = $path;
        }

        return $classNames;
    }

    /**
     * Determines if an extension is disabled by looking at the installed extensions config.
     */
    public function isDisabled(string $code): bool
    {
        return !$this->checkName($code) || array_get($this->disabledExtensions, $code, !Igniter::autoloadExtensions());
    }

    /**
     * Spins over every extension object and collects the results of a method call.
     */
    public function getRegistrationMethodValues(string $methodName): array
    {
        if (isset($this->registrationMethodCache[$methodName])) {
            return $this->registrationMethodCache[$methodName];
        }

        $results = [];
        $extensions = $this->getExtensions();
        foreach ($extensions as $id => $extension) {
            if (!is_callable([$extension, $methodName])) {
                continue;
            }

            $results[$id] = $extension->{$methodName}();
        }

        return $this->registrationMethodCache[$methodName] = $results;
    }

    public function updateInstalledExtensions(string $code, bool $enable = true): bool
    {
        $code = $this->getIdentifier($code);

        if ($enable) {
            array_pull($this->disabledExtensions, $code);
        } else {
            $this->disabledExtensions[$code] = true;
        }

        $this->packageManifest->writeDisabled($this->disabledExtensions);

        if ($extension = $this->findExtension($code)) {
            $extension->disabled = $enable === false;
        }

        return true;
    }

    /**
     * Delete extension the filesystem
     */
    public function removeExtension(?string $extCode = null): bool
    {
        if (!$extensionPath = $this->getExtensionPath($extCode)) {
            return false;
        }

        // Delete the specified extension folder.
        if (File::isDirectory($extensionPath)) {
            File::deleteDirectory($extensionPath);
        }

        $vendorPath = dirname($extensionPath);

        // Delete the specified extension vendor folder if it has no extension.
        if (File::isDirectory($vendorPath) && !count(File::directories($vendorPath))) {
            File::deleteDirectory($vendorPath);
        }

        return true;
    }

    /**
     * Extract uploaded extension zip folder
     */
    public function extractExtension(string $zipPath): string
    {
        $extensionCode = null;
        $extractTo = Igniter::extensionsPath();

        $zip = new ZipArchive;
        if ($zip->open($zipPath) === true) {
            $extensionDir = $zip->getNameIndex(0);

            if (!$this->checkName($extensionDir)) {
                throw new SystemException('Extension name can not have spaces.');
            }

            if ($zip->locateName($extensionDir.'Extension.php') === false) {
                throw new SystemException('Extension registration class was not found.');
            }

            throw_if(
                File::exists($configFile = $extensionDir.'extension.json'),
                new SystemException("extension.json files are no longer supported, please convert to composer.json: $configFile")
            );

            $meta = @json_decode($zip->getFromName($extensionDir.'composer.json'));
            if (!$meta || !strlen($meta->code)) {
                throw new SystemException(lang('igniter::system.extensions.error_config_no_found'));
            }

            $extensionCode = $meta->code;
            $extractToPath = $extractTo.'/'.$this->getNamePath($meta->code);
            $zip->extractTo($extractToPath);
            $zip->close();
        }

        return $extensionCode;
    }

    /**
     * Install a new or existing extension by code
     */
    public function installExtension(string $code, ?string $version = null): bool
    {
        $model = Extension::firstOrNew(['name' => $code]);
        if (!$model->applyExtensionClass()) {
            return false;
        }

        if (!$extension = $this->findExtension($model->name)) {
            return false;
        }

        // Register and boot the extension to make
        // its services available before migrating
        $extension->disabled = false;
        app()->register($extension);

        // set extension migration to the latest version
        resolve(UpdateManager::class)->migrateExtension($model->name);

        $model->version = $version ?? $this->packageManifest->getVersion($model->name) ?? $model->version;
        $model->save();

        $this->updateInstalledExtensions($model->name);

        return true;
    }

    /**
     * Uninstall a new or existing extension by code
     */
    public function uninstallExtension(string $code, bool $purgeData = false): bool
    {
        if ($purgeData) {
            resolve(UpdateManager::class)->purgeExtension($code);
        }

        $this->updateInstalledExtensions($code, false);

        return true;
    }

    /**
     * Delete a single extension by code
     */
    public function deleteExtension(string $code, bool $purgeData = true): void
    {
        if ($purgeData) {
            Extension::where('name', $code)->delete();

            resolve(UpdateManager::class)->purgeExtension($code);
        }

        // Remove extensions files from filesystem
        $composerManager = resolve(ComposerManager::class);
        if ($packageName = $composerManager->getPackageName($code)) {
            $composerManager->uninstall([$packageName => false]);
        }

        $this->removeExtension($code);
    }
}
