<?php

namespace Igniter\Main\Providers;

use Igniter\Flame\Igniter;
use Igniter\Main\Classes\ThemeManager;
use Igniter\System\Libraries\Assets;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

class AssetsServiceProvider extends ServiceProvider
{
    public function register()
    {
        if (!Igniter::runningInAdmin()) {
            $this->registerAssets();
            $this->registerCombinerEvent();
        }
    }

    protected function registerAssets()
    {
        Assets::registerCallback(function (Assets $manager) {
            if (Igniter::runningInAdmin())
                return;

            $manager->registerSourcePath(Igniter::themesPath());

            resolve(ThemeManager::class)->addAssetsFromActiveThemeManifest($manager);
        });
    }

    protected function registerCombinerEvent()
    {
        if ($this->app->runningInConsole() || Igniter::runningInAdmin())
            return;

        Event::listen('assets.combiner.beforePrepare', function (Assets $combiner, $assets) {
            resolve(ThemeManager::class)->applyAssetVariablesOnCombinerFilters(
                array_flatten($combiner->getFilters())
            );
        });
    }
}