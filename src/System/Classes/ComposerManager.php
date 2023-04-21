<?php

namespace Igniter\System\Classes;

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
use Igniter\System\Helpers\SystemHelper;
use Igniter\Flame\Support\Facades\File;
use Seld\JsonLint\DuplicateKeyException;
use Seld\JsonLint\JsonParser;
use Throwable;

/**
 * ComposerManager Class
 */
class ComposerManager
{
    protected const REPOSITORY_URL = 'https://satis.tastyigniter.com';

    protected $logs = [];

    /**
     * The output interface implementation.
     *
     * @var \Illuminate\Console\OutputStyle
     */
    protected $logsOutput;

    /**
     * @var \Composer\Autoload\ClassLoader The primary composer instance.
     */
    protected $loader;

    protected $storagePath;

    protected $prevErrorHandler;

    protected $workingDir;

    protected $namespacePool = [];

    protected $psr4Pool = [];

    protected $classMapPool = [];

    protected $includeFilesPool = [];

    protected $installedPackages = [];

    public function initialize()
    {
        $this->storagePath = storage_path('igniter/composer');
        $this->loader = require base_path('/vendor/autoload.php');
        $this->preloadPools();
    }

    /**
     * Similar function to including vendor/autoload.php.
     *
     * @param string $vendorPath Absoulte path to the vendor directory.
     *
     * @return void
     */
    public function autoload($vendorPath)
    {
        $dir = $vendorPath.'/composer';

        if (file_exists($file = $dir.'/autoload_namespaces.php')) {
            $map = require $file;
            foreach ($map as $namespace => $path) {
                if (isset($this->namespacePool[$namespace])) {
                continue;
                }
                $this->loader->set($namespace, $path);
                $this->namespacePool[$namespace] = true;
            }
        }

        if (file_exists($file = $dir.'/autoload_psr4.php')) {
            $map = require $file;
            foreach ($map as $namespace => $path) {
                if (isset($this->psr4Pool[$namespace])) {
                continue;
                }
                $this->loader->setPsr4($namespace, $path);
                $this->psr4Pool[$namespace] = true;
            }
        }

        if (file_exists($file = $dir.'/autoload_classmap.php')) {
            $classMap = require $file;
            if ($classMap) {
                $classMapDiff = array_diff_key($classMap, $this->classMapPool);
                $this->loader->addClassMap($classMapDiff);
                $this->classMapPool += array_fill_keys(array_keys($classMapDiff), true);
            }
        }

        if (file_exists($file = $dir.'/autoload_files.php')) {
            $includeFiles = require $file;
            foreach ($includeFiles as $includeFile) {
                $relativeFile = $this->stripVendorDir($includeFile, $vendorPath);
                if (isset($this->includeFilesPool[$relativeFile])) {
                continue;
                }
                require $includeFile;
                $this->includeFilesPool[$relativeFile] = true;
            }
        }
    }

    public function getPackageVersion($name)
    {
        return array_get($this->loadInstalledPackages()->get($name, []), 'version');
    }

    public function getPackageName($name)
    {
        return array_get($this->loadInstalledPackages()->get($name, []), 'name');
    }

    public function listInstalledPackages()
    {
        return $this->loadInstalledPackages();
    }

    public function getConfig($path, $type = 'extension')
    {
        $composer = json_decode(File::get($path.'/composer.json'), true) ?? [];

        if (!$config = array_get($composer, 'extra.tastyigniter-'.$type, [])) {
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

    protected function preloadPools()
    {
        $this->classMapPool = array_fill_keys(array_keys($this->loader->getClassMap()), true);
        $this->namespacePool = array_fill_keys(array_keys($this->loader->getPrefixes()), true);
        $this->psr4Pool = array_fill_keys(array_keys($this->loader->getPrefixesPsr4()), true);
        $this->includeFilesPool = $this->preloadIncludeFilesPool();
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
     *
     * @param string $path
     *
     * @return string
     */
    protected function stripVendorDir($path, $vendorDir)
    {
        $path = realpath($path);
        $vendorDir = realpath($vendorDir);

        if (str_starts_with($path, $vendorDir)) {
            $path = substr($path, strlen($vendorDir));
        }

        return $path;
    }

    protected function loadInstalledPackages()
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
            ->mapWithKeys(function ($package) {
                $code = array_get($package, 'extra.tastyigniter-package.code',
                    array_get($package, 'extra.tastyigniter-extension.code',
                        array_get($package, 'extra.tastyigniter-theme.code',
                            array_get($package, 'name'))));

                return [$code => $package];
            });
    }

    //
    //
    //

    public function install(?array $requirements, ?IOInterface $io = null): void
    {
        $this->assertPhpIniSet();
        $this->assertHomeEnvSet();

        $io ??= new NullIO();

        $jsonPath = $this->getJsonPath();

        if (!is_null($requirements)) {
            $this->backupComposerFiles();
            $this->updateRequirements($io, $jsonPath, $requirements);
        }

        $oldWorkingDirectory = getcwd();
        chdir(dirname($jsonPath));

        try {
            $composer = $this->createComposer($io, $jsonPath);

            $installer = Installer::create($io, $composer)
                ->setPreferDist()
                ->setRunScripts(false);

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

    public function uninstall(array $requirements, ?IOInterface $io = null): void
    {
        $this->assertPhpIniSet();
        $this->assertHomeEnvSet();

        $io ??= new NullIO();
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

    public function addAuthCredentials(string $username, string $password, string $type = 'http-basic'): void
    {
        $hostname = parse_url(self::REPOSITORY_URL, PHP_URL_HOST);

        $config = new JsonConfigSource(new JsonFile($this->getAuthPath()), true);

        $config->addConfigSetting($type.'.'.$hostname, [
            'username' => $username,
            'password' => $password,
        ]);
    }

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

    protected function getLockPath(string $jsonPath = null): ?string
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

    protected function backupComposerFiles(): void
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

    protected function restoreComposerFiles(): void
    {
        $jsonBackupPath = $this->storagePath.'/backups/composer.json';
        $lockBackupPath = $this->storagePath.'/backups/composer.lock';

        File::copy($jsonBackupPath, $this->getJsonPath());

        if (is_file($lockBackupPath)) {
            File::copy($lockBackupPath, $this->getLockPath());
        }
    }

    protected function updateRequirements(IOInterface $io, string $jsonPath, array $requirements): void
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
            $jsonParser = new JsonParser();
            $jsonParser->parse(file_get_contents($jsonPath), JsonParser::DETECT_KEY_CONFLICTS);
        } catch (DuplicateKeyException $e) {
            $details = $e->getDetails();
            $io->writeError('<warning>Key '.$details['key'].' is a duplicate in '.$jsonPath.' at line '.$details['line'].'</warning>');
        }

        // Bypass Factory::create()'s insistence on setting $disablePlugins to 'local'
        $composer = (new Factory())->createComposer($io, $config);

        $lockFile = $this->getLockPath($jsonPath);
        $im = $composer->getInstallationManager();
        $locker = new Locker($io, new JsonFile($lockFile, null, $io), $im, file_get_contents($jsonPath));
        $composer->setLocker($locker);

        return $composer;
    }

    protected function runComposer(Installer $installer): int
    {
        // Run the installer
        $this->prevErrorHandler = set_error_handler(function (int $code, string $message, string $file, int $line) {
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

    protected function assertPhpIniSet(): void
    {
        // Don't change the memory_limit, if it's already set to -1 or >= 1.5GB
        $memoryLimit = SystemHelper::phpIniValueInBytes('memory_limit');
        if ($memoryLimit !== -1 && $memoryLimit < 1024 * 1024 * 1536) {
            @ini_set('memory_limit', config('igniter.system.maxMemoryLimit', '1536M'));
        }

        if (!function_exists('set_time_limit') || !@set_time_limit(0)) {
            @ini_set('max_execution_time', 0);
        }
    }

    protected function assertHomeEnvSet(): void
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
        foreach ($config['repositories'] ?? [] as $index => $repository) {
            if (rtrim($repository['url'], '/') === static::REPOSITORY_URL) {
                unset($config['repositories'][$index]);
            }
        }

        $config['repositories'][] = [
            'type' => 'composer',
            'url' => static::REPOSITORY_URL,
            'canonical' => false,
        ];

        return $config;
    }
}
