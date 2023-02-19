<?php

namespace Tests;

use Igniter\Flame\Igniter;
use Igniter\Main\Classes\ThemeManager;

abstract class TestCase extends \Orchestra\Testbench\TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
    }

    protected function getPackageProviders($app)
    {
        return [
            \Igniter\Flame\ServiceProvider::class,
        ];
    }

    protected function defineEnvironment($app)
    {
        $viewPaths = $app['config']->get('view.paths');
        $viewPaths[] = __DIR__.'/_fixtures/views/';

        $app['config']->set('view.paths', $viewPaths);

        Igniter::loadControllersFrom(__DIR__.'/Fixtures/Controllers', 'Tests\\Fixtures\\Controllers');

        ThemeManager::addDirectory(__DIR__.'/_fixtures/themes');
        $app['config']->set('igniter.system.defaultTheme', 'tests-theme');
    }

    protected function defineDatabaseMigrations()
    {
        $this->artisan('igniter:up')->run();
    }

    protected function resolveApplicationConfiguration($app)
    {
        parent::resolveApplicationConfiguration($app);

        $configs = [
            'auth', 'currency', 'geocoder', 'system',
        ];

        foreach ($configs as $config) {
            $app['config']->set("igniter.$config", require(__DIR__."/../config/{$config}.php"));
        }
    }
}
