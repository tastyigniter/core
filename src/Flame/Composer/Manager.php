<?php

namespace Igniter\Flame\Composer;

use Closure;
use Composer\Autoload\ClassLoader;
use Composer\Config\JsonConfigSource;
use Composer\Json\JsonFile;
use Igniter\Flame\Support\Facades\File;
use Illuminate\Support\Collection;
use Illuminate\Support\Composer;
use Symfony\Component\Console\Output\OutputInterface;
use Throwable;

/**
 * Manager Class
 */
class Manager
{
    protected const REPOSITORY_HOST = 'satis.tastyigniter.com';

    /** The primary composer instance. */
    protected ?ClassLoader $loader = null;

    protected mixed $prevErrorHandler = null;

    protected ?Collection $installedPackages = null;

    public function __construct(
        protected string $workingPath,
        protected string $storagePath,
        protected ?Composer $composer = null,
    ) {}

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
        if (is_null($this->loader) && File::isFile($path = $this->workingPath.'/vendor/autoload.php')) {
            $this->loader = require $path;
        }

        return $this->loader;
    }

    protected function loadInstalledPackages(): Collection
    {
        if ($this->installedPackages) {
            return $this->installedPackages;
        }

        $path = $this->workingPath.'/vendor/composer/installed.json';
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

    public function install(?array $requirements, Closure|OutputInterface|null $output = null)
    {
        $this->backupComposerFiles();

        try {
            $packages = array_map('strtolower', $requirements);
            $this->composer->requirePackages($packages, false, $output);
        } catch (Throwable $e) {
            $this->restoreComposerFiles();

            throw $e;
        }
    }

    public function uninstall(array $requirements, Closure|OutputInterface|null $output = null)
    {
        $this->backupComposerFiles();

        try {
            $packages = array_map('strtolower', $requirements);
            $this->composer->removePackages($packages, false, $output);
        } catch (Throwable $e) {
            $this->restoreComposerFiles();

            throw $e;
        }
    }

    public function addAuthCredentials(string $username, string $password, string $type = 'http-basic')
    {
        $config = new JsonConfigSource(new JsonFile($this->workingPath.'/auth.json'), true);

        $config->addConfigSetting($type.'.'.self::REPOSITORY_HOST, [
            'username' => $username,
            'password' => $password,
        ]);
    }

    protected function backupComposerFiles()
    {
        $jsonBackupPath = $this->storagePath.'/backups/composer.json';
        $lockBackupPath = $this->storagePath.'/backups/composer.lock';

        if (!File::isDirectory(dirname($jsonBackupPath))) {
            File::makeDirectory(dirname($jsonBackupPath), null, true);
        }

        File::copy($this->workingPath.'/composer.json', $jsonBackupPath);

        if (File::isFile($lockPath = $this->workingPath.'/composer.lock')) {
            File::copy($lockPath, $lockBackupPath);
        }
    }

    protected function restoreComposerFiles()
    {
        $jsonBackupPath = $this->storagePath.'/backups/composer.json';
        $lockBackupPath = $this->storagePath.'/backups/composer.lock';

        File::copy($jsonBackupPath, $this->workingPath.'/composer.json');

        if (File::isFile($lockBackupPath)) {
            File::copy($lockBackupPath, $this->workingPath.'/composer.lock');
        }
    }

    //
    // Asserts
    //

    public function assertSchema()
    {
        $this->composer->modify(function(array $composer) {
            $newConfig = $this->assertRepository($composer);
            if ($composer !== $newConfig) {
                $composer = $newConfig;
            }

            return $composer;
        });
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
