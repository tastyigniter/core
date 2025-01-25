<?php

namespace Igniter\System\Helpers;

use Igniter\Flame\Support\Facades\File;
use Igniter\Flame\Support\Facades\Igniter;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Cache;

class CacheHelper
{
    /**
     * Execute the console command.
     */
    public function clear()
    {
        Cache::flush();
        $this->clearInternal();
    }

    public function clearInternal()
    {
        $this->clearCache();
        $this->clearView();
        $this->clearTemplates();

        $this->clearCombiner();

        $this->clearCompiled();
    }

    public function clearView()
    {
        $path = config('view.compiled');
        foreach (File::glob("{$path}/*") as $view) {
            File::delete($view);
        }
    }

    public function clearCombiner()
    {
        $this->clearDirectory('/igniter/combiner');
    }

    public function clearCache()
    {
        $path = config('igniter-pagic.parsedTemplateCachePath', storage_path('/igniter/cache'));
        if (!File::isDirectory($path)) {
            return;
        }

        foreach (File::directories($path) as $directory) {
            File::deleteDirectory($directory);
        }
    }

    public function clearTemplates() {}

    public function clearCompiled()
    {
        File::delete(Igniter::getCachedAddonsPath());
        File::delete(App::getCachedPackagesPath());
        File::delete(App::getCachedServicesPath());
    }

    public function clearDirectory($path)
    {
        if (!File::isDirectory(storage_path().$path)) {
            return;
        }

        foreach (File::directories(storage_path().$path) as $directory) {
            File::deleteDirectory($directory);
        }
    }
}
