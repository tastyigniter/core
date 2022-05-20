<?php

namespace Igniter\Flame\View;

use Illuminate\View\ViewServiceProvider as ServiceProvider;

class ViewServiceProvider extends ServiceProvider
{
    /**
     * Register the Blade compiler implementation.
     *
     * @return void
     */
    public function registerBladeCompiler()
    {
//        $this->app->singleton('blade.compiler', function ($app) {
//            return tap(new BladeCompiler($app['files'], $app['config']['view.compiled']), function ($blade) {
//                $blade->component('dynamic-component', DynamicComponent::class);
//            });
//        });
    }
}
