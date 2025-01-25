<?php

namespace Igniter\Tests;

use Igniter\Flame\Support\Facades\Igniter;
use Igniter\Main\Classes\ThemeManager;
use Igniter\System\Classes\ComponentManager;
use Igniter\System\Classes\PackageManifest;
use Igniter\Tests\System\Fixtures\TestComponent;
use Igniter\Tests\System\Fixtures\TestComponentWithLifecycle;
use Igniter\Tests\System\Fixtures\TestLivewireComponent;
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

        Igniter::loadControllersFrom(__DIR__.'/Fixtures/Controllers', 'Igniter\\Tests\\Fixtures\\Controllers');
        Igniter::loadResourcesFrom(__DIR__.'/../resources', 'igniter.tests');
        View::addNamespace('tests.admin', __DIR__.'/../resources/views');

        ThemeManager::addDirectory(__DIR__.'/../resources/themes');
        $app['config']->set('igniter-system.defaultTheme', 'tests-theme');

        resolve(ComponentManager::class)->registerCallback(function(ComponentManager $manager) {
            $manager->registerComponent(TestComponent::class, TestComponent::componentMeta());
            $manager->registerComponent(TestComponentWithLifecycle::class, TestComponentWithLifecycle::componentMeta());
            $manager->registerComponent(TestLivewireComponent::class, TestLivewireComponent::componentMeta());
        });
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
