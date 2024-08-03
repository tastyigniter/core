<?php

namespace Igniter\System\Classes;

use Composer\Autoload\ClassLoader;
use Composer\Composer;
use Composer\Config\JsonConfigSource;
use Composer\DependencyResolver\Request;
use Composer\Installer;
use Composer\IO\IOInterface;
use Composer\IO\NullIO;
use Composer\Json\JsonFile;
use Composer\Package\Locker;
use Composer\Util\Platform;
use Igniter\Flame\Composer\Factory;
use Igniter\Flame\Exception\ComposerException;
use Igniter\Flame\Exception\SystemException;
use Igniter\Flame\Support\Facades\File;
use Igniter\System\Helpers\SystemHelper;
use Illuminate\Support\Collection;
use Seld\JsonLint\DuplicateKeyException;
use Seld\JsonLint\JsonParser;
use Throwable;

/**
 * ComposerManager Class
 */
class ComposerManager
{
    protected const REPOSITORY_HOST = 'satis.tastyigniter.com';

    /** The primary composer instance. */
    protected ?ClassLoader $loader = null;

    protected ?string $storagePath = null;

    protected mixed $prevErrorHandler = null;

    protected ?string $workingDir = null;

    protected ?Collection $installedPackages = null;

    public function initialize()
    {
        $this->storagePath = storage_path('igniter/composer');
    }

    /**
     * Similar function to including vendor/autoload.php.
     *
     * @param string $vendorPath Absolute path to the vendor directory.
     *
     * @return void
     */
    public function autoload(string $vendorPath)
    {
        $dir = $vendorPath.'/composer';

        if (file_exists($file = $dir.'/autoload_namespaces.php')) {
            foreach (File::getRequire($file) as $namespace => $path) {
                $this->getLoader()->set($namespace, $path);
            }
        }

        if (file_exists($file = $dir.'/autoload_psr4.php')) {
            foreach (File::getRequire($file) as $namespace => $path) {
                $this->getLoader()->setPsr4($namespace, $path);
            }
        }

        if (file_exists($file = $dir.'/autoload_classmap.php')) {
            if ($classMap = File::getRequire($file)) {
                $this->getLoader()->addClassMap($classMap);
            }
        }

        if (file_exists($file = $dir.'/autoload_files.php')) {
            foreach (File::getRequire($file) as $includeFile) {
                $relativeFile = $this->stripVendorDir($includeFile, $vendorPath);
                require $includeFile;
                $this->includeFilesPool[$relativeFile] = true;
            }
        }
    }

    public function getPackageVersion(string $name): ?string
    {
        return array_get($this->loadInstalledPackages()->get($name, []), 'version');
    }

    public function getPackageName(string $name): ?string
    {
        return array_get($this->loadInstalledPackages()->get($name, []), 'name');
    }

    public function listInstalledPackages()
    {
        return $this->loadInstalledPackages();
    }

    public function getExtensionManifest(string $path)
    {
        return $this->formatPackageManifest($path);
    }

    public function getThemeManifest(string $path)
    {
        return $this->formatPackageManifest($path, 'theme');
    }

    public function getLoader()
    {
        if (is_null($this->loader) && File::isFile($path = base_path('vendor/autoload.php'))) {
            $this->loader = require $path;
        }

        return $this->loader;
    }

    protected function preloadIncludeFilesPool()
    {
        $result = [];
        $vendorPath = base_path().'/vendor';

        if (file_exists($file = $vendorPath.'/composer/autoload_files.php')) {
            $includeFiles = require $file;
            foreach ($includeFiles as $includeFile) {
                $relativeFile = $this->stripVendorDir($includeFile, $vendorPath);
                $result[$relativeFile] = true;
            }
        }

        return $result;
    }

    /**
     * Removes the vendor directory from a path.
     */
    protected function stripVendorDir(string $path, string $vendorDir): string
    {
        $path = realpath($path);
        $vendorDir = realpath($vendorDir);

        if (str_starts_with($path, $vendorDir)) {
            $path = substr($path, strlen($vendorDir));
        }

        return $path;
    }

    protected function loadInstalledPackages(): Collection
    {
        if ($this->installedPackages) {
            return $this->installedPackages;
        }

        $path = base_path('vendor/composer/installed.json');

        $installed = File::exists($path) ? json_decode(File::get($path), true) : [];

        // Structure of the installed.json manifest in different in Composer 2.0
        $installedPackages = $installed['packages'] ?? $installed;

        return $this->installedPackages = collect($installedPackages)
            ->whereIn('type', ['tastyigniter-package', 'tastyigniter-extension', 'tastyigniter-theme'])
            ->mapWithKeys(function($package) {
                $code = array_get($package, 'extra.tastyigniter-package.code',
                    array_get($package, 'extra.tastyigniter-extension.code',
                        array_get($package, 'extra.tastyigniter-theme.code')));

                return [$code => $package];
            });
    }

    protected function formatPackageManifest(string $path, string $type = 'extension')
    {
        $composer = File::json($path.'/composer.json') ?? [];

        if (!$manifest = array_get($composer, 'extra.tastyigniter-'.$type, [])) {
            return $manifest;
        }

        $manifest['type'] = 'tastyigniter-'.$type;
        $manifest['package_name'] = array_get($composer, 'name');
        $manifest['namespace'] = key(array_get($composer, 'autoload.psr-4', []));
        $manifest['version'] = $this->getPackageVersion($manifest['code']);
        $manifest['description'] = array_get($composer, 'description');
        $manifest['author'] = array_get($composer, 'authors.0.name');
        $manifest['homepage'] = array_get($manifest, 'homepage', array_get($composer, 'homepage'));

        return array_filter($manifest);
    }

    //
    //
    //

    public function install(?array $requirements, ?IOInterface $io = null)
    {
        $this->assertPhpIniSet();
        $this->assertHomeEnvSet();

        $io ??= new NullIO;

        $jsonPath = $this->getJsonPath();

        if (!is_null($requirements)) {
            $this->backupComposerFiles();
            $this->updateRequirements($io, $jsonPath, $requirements);
        }

        $oldWorkingDirectory = getcwd();
        chdir(dirname($jsonPath));

        try {
            $composer = $this->createComposer($io, $jsonPath);
            $composer->getEventDispatcher()->setRunScripts(false);

            $installer = Installer::create($io, $composer)->setPreferDist();

            if ($requirements) {
                $installer
                    ->setUpdate(true)
                    ->setUpdateAllowTransitiveDependencies(Request::UPDATE_LISTED_WITH_TRANSITIVE_DEPS);

                // if no lock is present, we do not do a partial update as this is not supported by the Installer
                if ($composer->getLocker()->isLocked()) {
                    $installer->setUpdateAllowList(array_keys($requirements));
                }
            }

            $this->runComposer($installer);
        } catch (Throwable $e) {
            $this->restoreComposerFiles();

            throw new ComposerException($e, $io);
        } finally {
            chdir($oldWorkingDirectory);

            // Invalidate opcache
            if (function_exists('opcache_reset')) {
                @opcache_reset();
            }
        }
    }

    public function uninstall(array $requirements, ?IOInterface $io = null)
    {
        $this->assertPhpIniSet();
        $this->assertHomeEnvSet();

        $io ??= new NullIO;
        $packages = array_map('strtolower', $requirements);

        $jsonPath = $this->getJsonPath();

        $this->backupComposerFiles();
        $this->updateRequirements($io, $jsonPath, $requirements, true);

        $oldWorkingDirectory = getcwd();
        chdir(dirname($jsonPath));

        try {
            $composer = $this->createComposer($io, $jsonPath);
            $composer->getInstallationManager()->setOutputProgress(false);

            $installer = Installer::create($io, $composer)
                ->setUpdate(true)
                ->setUpdateAllowList($packages)
                ->setRunScripts(false);

            $this->runComposer($installer);
        } catch (Throwable $e) {
            $this->restoreComposerFiles();

            throw new ComposerException($e, $io);
        } finally {
            // Change the working directory back
            chdir($oldWorkingDirectory);

            // Invalidate opcache
            if (function_exists('opcache_reset')) {
                @opcache_reset();
            }
        }
    }

    public function addAuthCredentials(string $username, string $password, string $type = 'http-basic')
    {
        $config = new JsonConfigSource(new JsonFile($this->getAuthPath()), true);

        $config->addConfigSetting($type.'.'.self::REPOSITORY_HOST, [
            'username' => $username,
            'password' => $password,
        ]);
    }

    /** @noinspection PhpUndefinedConstantInspection */
    protected function getJsonPath(): string
    {
        if (defined('IGNITER_COMPOSER_PATH')) {
            throw_unless(is_file(IGNITER_COMPOSER_PATH), new SystemException(sprintf(
                'No Composer config found at IGNITER_COMPOSER_PATH (%s).', IGNITER_COMPOSER_PATH
            )));

            return IGNITER_COMPOSER_PATH;
        }

        $jsonPath = base_path('composer.json');

        throw_unless(is_file($jsonPath), new SystemException(sprintf(
            'No Composer config found at %s', $jsonPath
        )));

        return $jsonPath;
    }

    protected function getLockPath(?string $jsonPath = null): ?string
    {
        $jsonPath ??= $this->getJsonPath();

        return pathinfo($jsonPath, PATHINFO_EXTENSION) === 'json'
            ? substr($jsonPath, 0, -4).'lock'
            : $jsonPath.'.lock';
    }

    protected function getAuthPath(): string
    {
        return base_path('auth.json');
    }

    protected function backupComposerFiles()
    {
        $jsonBackupPath = $this->storagePath.'/backups/composer.json';
        $lockBackupPath = $this->storagePath.'/backups/composer.lock';

        if (!File::isDirectory(dirname($jsonBackupPath))) {
            File::makeDirectory(dirname($jsonBackupPath), null, true);
        }

        File::copy($this->getJsonPath(), $jsonBackupPath);

        if (is_file($lockPath = $this->getLockPath())) {
            File::copy($lockPath, $lockBackupPath);
        }
    }

    protected function restoreComposerFiles()
    {
        $jsonBackupPath = $this->storagePath.'/backups/composer.json';
        $lockBackupPath = $this->storagePath.'/backups/composer.lock';

        File::copy($jsonBackupPath, $this->getJsonPath());

        if (is_file($lockBackupPath)) {
            File::copy($lockBackupPath, $this->getLockPath());
        }
    }

    protected function updateRequirements(IOInterface $io, string $jsonPath, array $requirements)
    {
        $requireKey = 'require';
        $requireDevKey = 'require-dev';

        $json = new JsonFile($jsonPath);
        $config = $json->read();

        foreach ($requirements as $package => $constraint) {
            if ($constraint === false) {
                unset($config[$requireKey][$package]);
            } else {
                $config[$requireKey][$package] = $constraint;
            }

            // Also remove the package from require-dev
            unset($config[$requireDevKey][$package]);
        }

        $json->write($config);
    }

    protected function createComposer(IOInterface $io, string $jsonPath): Composer
    {
        $file = new JsonFile($jsonPath, null, $io);
        $file->validateSchema(JsonFile::LAX_SCHEMA);
        $config = $file->read();

        $this->assertRepository($config);

        try {
            $jsonParser = new JsonParser;
            $jsonParser->parse(file_get_contents($jsonPath), JsonParser::DETECT_KEY_CONFLICTS);
        } catch (DuplicateKeyException $e) {
            $details = $e->getDetails();
            $io->writeError('<warning>Key '.$details['key'].' is a duplicate in '.$jsonPath.' at line '.$details['line'].'</warning>');
        }

        // Bypass Factory::create()'s insistence on setting $disablePlugins to 'local'
        $composer = (new Factory)->createComposer($io, $config);

        $lockFile = $this->getLockPath($jsonPath);
        $im = $composer->getInstallationManager();
        $locker = new Locker($io, new JsonFile($lockFile, null, $io), $im, file_get_contents($jsonPath));
        $composer->setLocker($locker);

        return $composer;
    }

    protected function runComposer(Installer $installer): int
    {
        // Run the installer
        $this->prevErrorHandler = set_error_handler(function(int $code, string $message, string $file, int $line) {
            // Ignore deprecated errors
            if ($code === E_USER_DEPRECATED) {
                return true;
            }

            if (isset($this->prevErrorHandler)) {
                return ($this->prevErrorHandler)($code, $message, $file, $line);
            }

            return false;
        }, E_USER_DEPRECATED);

        $status = $installer->run();

        set_error_handler($this->prevErrorHandler);

        throw_if($status !== 0, new SystemException('An error occurred'));

        return $status;
    }

    //
    // Asserts
    //

    public function assertSchema()
    {
        $json = new JsonFile($this->getJsonPath());
        $config = $json->read();

        $newConfig = $this->assertRepository($config);
        if ($config !== $newConfig) {
            $json->write($config);
        }
    }

    protected function assertPhpIniSet()
    {
        // Don't change the memory_limit, if it's already set to -1 or >= 1.5GB
        $memoryLimit = SystemHelper::phpIniValueInBytes('memory_limit');
        if ($memoryLimit !== -1 && $memoryLimit < 1024 * 1024 * 1536) {
            @ini_set('memory_limit', config('igniter-system.maxMemoryLimit', '1536M'));
        }

        if (!function_exists('set_time_limit') || !@set_time_limit(0)) {
            @ini_set('max_execution_time', 0);
        }
    }

    protected function assertHomeEnvSet()
    {
        if (!getenv('COMPOSER_HOME') && !getenv(Platform::isWindows() ? 'APPDATA' : 'HOME')) {
            $path = $this->storagePath.'/home';
            if (!File::isDirectory($path)) {
                File::makeDirectory($path, null, true);
            }

            putenv("COMPOSER_HOME=$path");
        }
    }

    protected function assertRepository(array $config): array
    {
        foreach ($config['repositories'] ?? [] as $repository) {
            if (str_contains($repository['url'], static::REPOSITORY_HOST)) {
                return $config;
            }
        }

        $config['repositories'][] = [
            'type' => 'composer',
            'url' => 'https://'.static::REPOSITORY_HOST,
            'canonical' => false,
        ];

        return $config;
    }
}
