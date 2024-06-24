<?php

namespace Igniter\System\Classes;

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
                return [
                    $package['name'] => [
                        'code' => array_get($package, 'extra.tastyigniter-theme.code')
                            ?: array_get($package, 'extra.tastyigniter-extension.code'),
                        'type' => array_has($package, 'extra.tastyigniter-theme')
                            ? 'tastyigniter-theme'
                            : 'tastyigniter-extension',
                        'installPath' => $this->vendorPath
                            .DIRECTORY_SEPARATOR
                            .'composer'
                            .DIRECTORY_SEPARATOR
                            .array_get($package, 'install-path'),
                    ],
                ];
            })
            ->filter()
            ->all());
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
