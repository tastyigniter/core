<?php

namespace Igniter\Main\Providers;

use Igniter\Flame\Pagic\Model;
use Igniter\Flame\Pagic\Router;
use Igniter\Main\Classes\Theme;
use Igniter\Main\Classes\ThemeManager;
use Igniter\Main\Template\Page;
use Illuminate\Support\Facades\Event;
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
                ->each(function (Theme $theme) use ($resolver, $manager) {
                    $resolver->addSource($theme->getName(), $theme->makeFileSource());

                    if ($theme->getName() === $manager->getActiveThemeCode()) {
                        $resolver->setDefaultSourceName($theme->getName());
                    }
                });

            $model->setSource($manager->getActiveThemeCode());
        });
    }

    public function boot()
    {
        $manager = resolve(ThemeManager::class);

        $manager->bootThemes();

        Event::listen('exception.beforeRender', function ($exception, $httpCode, $request) {
            $themeViewPaths = array_get(view()->getFinder()->getHints(), 'igniter.main', []);
            config()->set('view.paths', array_merge($themeViewPaths, config('view.paths')));
        });
    }
}