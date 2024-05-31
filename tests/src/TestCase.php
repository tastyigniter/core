<?php

namespace Igniter\Tests;

use Igniter\Flame\Igniter;
use Igniter\Main\Classes\ThemeManager;
use Igniter\System\Classes\PackageManifest;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\View;
use Livewire\LivewireServiceProvider;

abstract class TestCase extends \Orchestra\Testbench\TestCase
{
    use DatabaseTransactions;

    protected function getPackageProviders($app)
    {
        return [
            LivewireServiceProvider::class,
            \Igniter\Flame\ServiceProvider::class,
        ];
    }

    protected function defineEnvironment($app)
    {
        $viewPaths = $app['config']->get('view.paths');
        $viewPaths[] = __DIR__.'/../resources/views/';

        $app['config']->set('view.paths', $viewPaths);

        Igniter::loadControllersFrom(__DIR__.'/Admin/Fixtures/Controllers', 'Tests\\Admin\\Fixtures\\Controllers');
        View::addNamespace('tests.admin', __DIR__.'/../resources/views');

        ThemeManager::addDirectory(__DIR__.'/../resources/themes');
        $app['config']->set('igniter-system.defaultTheme', 'tests-theme');
    }

    protected function defineDatabaseMigrations()
    {
        $this->artisan('igniter:up');
    }

    protected function resolveApplicationConfiguration($app)
    {
        parent::resolveApplicationConfiguration($app);

        $app->afterResolving(PackageManifest::class, function($instance) {
            $instance->vendorPath = __DIR__.'/../../vendor';
        });

        $configs = ['currency', 'geocoder', 'system'];

        foreach ($configs as $config) {
            $app['config']->set("igniter.$config", require(__DIR__."/../../config/{$config}.php"));
        }
    }
}
