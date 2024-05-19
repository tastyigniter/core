<?php

namespace Igniter\Main\Providers;

use Igniter\Flame\Igniter;
use Igniter\Main\Classes\ThemeManager;
use Igniter\System\Libraries\Assets;
use Illuminate\Support\ServiceProvider;

class AssetsServiceProvider extends ServiceProvider
{
    public function register()
    {
        if (!$this->app->runningInConsole() && !Igniter::runningInAdmin()) {
            $this->registerAssets();
        }
    }

    protected function registerAssets()
    {
        Assets::registerCallback(function(Assets $manager) {
            $manager->registerSourcePath(Igniter::themesPath());

            if ($activeTheme = resolve(ThemeManager::class)->getActiveTheme()) {
                $manager->addAssetsFromThemeManifest($activeTheme);
            }
        });
    }
}