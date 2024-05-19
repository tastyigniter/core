<?php

namespace Igniter\Main\Providers;

use Igniter\Flame\Igniter;
use Igniter\Flame\Pagic\Model;
use Igniter\Flame\Pagic\Router;
use Igniter\Flame\Support\Facades\File;
use Igniter\Main\Classes\MainController;
use Igniter\Main\Classes\SupportConfigurableComponent;
use Igniter\Main\Classes\Theme;
use Igniter\Main\Classes\ThemeManager;
use Igniter\Main\Template\Page;
use Igniter\System\Classes\ComponentManager;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;
use Livewire\LivewireServiceProvider;

class ThemeServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->app->register(LivewireServiceProvider::class);

        $this->callAfterResolving(Router::class, function ($router) {
            $router::$templateClass = Page::class;
            $router->setTheme(Theme::getActiveCode());
        });

        Model::extend(function (Model $model) {
            $model->setSource(Theme::getActiveCode());
        });

        Livewire::componentHook(SupportConfigurableComponent::class);
    }

    public function boot()
    {
        $this->app->booted(function () {
            resolve(ComponentManager::class)->bootComponents();

            if (!Igniter::hasDatabase(true)) {
                return;
            }

            ($manager = resolve(ThemeManager::class))->bootThemes();
            $this->registerThemesViewNamespace($manager->listThemes());

            Event::listen('main.controller.beforeRemap', function (MainController $controller) {
                $controller->getTheme()?->loadThemeFile();
            });
        });
    }

    protected function registerThemesViewNamespace(array $themes)
    {
        foreach ($themes as $theme) {
            if (File::isDirectory($theme->getSourcePath())) {
                $this->loadViewsFrom($theme->getSourcePath(), $theme->getName());

                if ($theme->hasParent()) {
                    $this->loadViewsFrom($theme->getSourcePath(), $theme->getParent()->getName());
                }
            }
        }
    }
}
