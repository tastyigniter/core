<?php

namespace Igniter\Main\Providers;

use Igniter\Flame\Pagic\Model;
use Igniter\Flame\Pagic\Router;
use Igniter\Main\Classes\Theme;
use Igniter\Main\Classes\ThemeManager;
use Igniter\Main\Template\Page;
use Illuminate\Support\ServiceProvider;

class ThemeServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->callAfterResolving(Router::class, function ($router) {
            $router::$templateClass = Page::class;
            $router->setTheme(resolve(ThemeManager::class)->getActiveThemeCode());
        });

        Model::extend(function (Model $model) {
            $manager = resolve(ThemeManager::class);

            $resolver = $model->getSourceResolver();
            collect($manager->listThemes())
                ->filter(function (Theme $theme) use ($resolver) {
                    return !$resolver->hasSource($theme->getName());
                })
                ->each(function (Theme $theme) use ($resolver) {
                    $resolver->addSource($theme->getName(), $theme->makeFileSource());
                });

            $activeTheme = $manager->getActiveThemeCode();
            $resolver->setDefaultSourceName($activeTheme);
        });
    }

    public function boot()
    {
        resolve(ThemeManager::class)->bootThemes();
    }
}