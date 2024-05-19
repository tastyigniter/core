<?php

namespace Igniter\System\Classes;

use Igniter\Flame\Support\Facades\File;
use Illuminate\Foundation\PackageManifest as BasePackageManifest;

class PackageManifest extends BasePackageManifest
{
    protected string $metaFile = '/disabled-addons.json';

    public function packages(): array
    {
        return $this->getManifest();
    }

    public function extensions(): array
    {
        return collect($this->getManifest())->where('type', 'tastyigniter-extension')->all();
    }

    public function themes(): array
    {
        return collect($this->getManifest())->where('type', 'tastyigniter-theme')->all();
    }

    public function extensionConfig(string $key): array
    {
        return collect($this->extensions())->flatMap(function($configuration) use ($key) {
            return (array)($configuration[$key] ?? []);
        })->filter()->all();
    }

    public function themeConfig(string $key): array
    {
        return collect($this->themes())->flatMap(function($configuration) use ($key) {
            return (array)($configuration[$key] ?? []);
        })->filter()->all();
    }

    public function getPackagePath(string $path)
    {
        return $this->vendorPath.'/composer/'.$path;
    }

    public function getVersion(string $code)
    {
        return collect($this->getManifest())->where('code', $code)->value('version');
    }

    public function coreVersion()
    {
        $packages = [];

        if ($this->files->exists($path = $this->vendorPath.'/composer/installed.json')) {
            $installed = json_decode($this->files->get($path), true);
            $packages = $installed['packages'] ?? $installed;
        }

        return collect($packages)
            ->filter(function($package) {
                return array_get($package, 'name') === 'tastyigniter/flame';
            })
            ->value('version');
    }

    public function build()
    {
        $packages = [];

        if ($this->files->exists($path = $this->vendorPath.'/composer/installed.json')) {
            $installed = json_decode($this->files->get($path), true);
            $packages = $installed['packages'] ?? $installed;
        }

        $this->manifest = null;

        $this->write(collect($packages)
            ->filter(function($package) {
                return array_has($package, 'extra.tastyigniter-extension') ||
                    array_has($package, 'extra.tastyigniter-theme');
            })
            ->mapWithKeys(function($package) {
                if (array_get($package, 'extra.tastyigniter-extension', [])) {
                    return $this->formatExtension($package);
                }

                if (array_get($package, 'extra.tastyigniter-theme', [])) {
                    return $this->formatTheme($package);
                }
            })
            ->filter()
            ->all());
    }

    protected function formatExtension(array $package, array $result = []): array
    {
        if (!$autoload = array_get($package, 'autoload.psr-4', [])) {
            return $result;
        }

        $directory = $this->vendorPath.'/composer/'.array_get($package, 'install-path');
        $json = json_decode(File::get($directory.'/composer.json'), true);
        $manifest = $json['extra']['tastyigniter-extension'] ?? [];

        $namespace = key($autoload);
        $guessedCode = strtolower(str_replace('\\', '.', trim($namespace, '\\')));

        $manifest['namespace'] = $namespace;
        $manifest['code'] = $code = array_get($manifest, 'code', $guessedCode);
        $manifest['type'] = 'tastyigniter-extension';
        $manifest['package_name'] = array_get($package, 'name');
        $manifest['version'] = array_get($package, 'version');
        $manifest['description'] = array_get($package, 'description');
        $manifest['author'] = array_get($package, 'authors.0.name');
        $manifest['homepage'] = array_get($package, 'homepage');
        $manifest['require'] = $this->formatRequire(array_get($package, 'require'));
        $manifest['installPath'] = array_get($package, 'install-path');

        $result[$code] = array_filter($manifest);

        return $result;
    }

    protected function formatTheme(array $package, array $result = []): array
    {
        $directory = $this->vendorPath.'/composer/'.array_get($package, 'install-path');
        $json = json_decode(File::get($directory.'/composer.json'), true);
        $manifest = $json['extra']['tastyigniter-theme'] ?? [];

        $manifest['code'] = $code = array_get($manifest, 'code');
        $manifest['type'] = 'tastyigniter-theme';
        $manifest['package_name'] = array_get($package, 'name');
        $manifest['version'] = array_get($package, 'version');
        $manifest['description'] = array_get($package, 'description');
        $manifest['author'] = array_get($package, 'authors.0.name');
        $manifest['homepage'] = array_get($package, 'homepage');
        $manifest['publish-paths'] = array_get($manifest, 'publish-paths');
        $manifest['require'] = $this->formatRequire(array_get($package, 'require'));
        $manifest['installPath'] = array_get($package, 'install-path');

        $result[$code] = array_filter($manifest);

        return $result;
    }

    protected function formatRequire(?array $require): ?array
    {
        return $require;
    }

    //
    //
    //

    public function disabledAddons(): array
    {
        $path = dirname($this->manifestPath).$this->metaFile;
        if (!is_file($path)) {
            return [];
        }

        return json_decode($this->files->get($path, true), true) ?: [];
    }

    public function writeDisabled(array $codes)
    {
        $this->files->replace(dirname($this->manifestPath).$this->metaFile, json_encode($codes));
    }
}
