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
            $manager = resolve(ThemeManager::class);

            $router::$templateClass = Page::class;
            $router->setTheme($manager->getActiveThemeCode());

            $this->loadSourceFromThemes($manager);
        });

        Model::extend(function (Model $model) {
            $model->setSource(resolve(ThemeManager::class)->getActiveThemeCode());
        });
    }

    public function boot()
    {
        resolve(ThemeManager::class)->bootThemes();

        Event::listen('exception.beforeRender', function ($exception, $httpCode, $request) {
            $themeViewPaths = array_get(view()->getFinder()->getHints(), 'igniter.main', []);
            config()->set('view.paths', array_merge($themeViewPaths, config('view.paths')));
        });
    }

    public function loadSourceFromThemes(mixed $manager): void
    {
        collect($manager->listThemes())
            ->filter(function (Theme $theme) {
                return !Page::getSourceResolver()->hasSource($theme->getName());
            })
            ->each(function (Theme $theme) use ($manager) {
                Page::getSourceResolver()->addSource($theme->getName(), $theme->makeFileSource());

                if ($theme->getName() === $manager->getActiveThemeCode()) {
                    Page::getSourceResolver()->setDefaultSourceName($theme->getName());
                }
            });
    }
}