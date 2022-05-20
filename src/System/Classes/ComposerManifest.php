<?php

namespace Igniter\System\Classes;

use Igniter\Flame\Support\Facades\File;
use Illuminate\Foundation\PackageManifest;

class ComposerManifest extends PackageManifest
{
    public function extensions()
    {
        return collect($this->getManifest())->where('type', 'tastyigniter-extension')->all();
    }

    public function getVersion($code)
    {
        return collect($this->getManifest())->where('code', $code)->value('version');
    }

    public function build()
    {
        $packages = [];

        if ($this->files->exists($path = $this->vendorPath.'/composer/installed.json')) {
            $installed = json_decode($this->files->get($path), true);
            $packages = $installed['packages'] ?? $installed;
        }

        $this->write(collect($packages)
            ->filter(function ($package) {
                return array_has($package, 'extra.tastyigniter-extension');
            })
            ->mapWithKeys(function ($package) {
                $manifest = array_get($package, 'extra.tastyigniter-extension', []);
                $code = array_get($manifest, 'code', array_get($package, 'name'));

                $namespace = key(array_get($package, 'autoload.psr-4', []));
                $autoloadDir = current(array_get($package, 'autoload.psr-4', []));
                $directory = str_before(dirname(File::fromClass($namespace.'Extension')), '/'.rtrim($autoloadDir, '/'));

                $manifest['code'] = $code;
                $manifest['type'] = 'tastyigniter-extension';
                $manifest['namespace'] = $namespace;
                $manifest['version'] = array_get($package, 'version');
                $manifest['description'] = array_get($package, 'description');
                $manifest['author'] = array_get($package, 'authors.0.name');
                $manifest['homepage'] = array_get($package, 'homepage');
                $manifest['directory'] = $directory;

                return [$code => $manifest];
            })
            ->filter()
            ->all());
    }
}
