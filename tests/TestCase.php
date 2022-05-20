<?php

namespace Tests;

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
        $app['config']->set('auth.guards.admin', [
            'driver' => 'igniter',
            'provider' => 'admin-users',
        ]);

        $app['config']->set('auth.providers.admin-users', ['driver' => 'igniter']);

        $viewPaths = $app['config']->get('view.paths');
        $viewPaths[] = __DIR__.'/_fixtures/views/';

        $app['config']->set('view.paths', $viewPaths);
    }

    protected function resolveApplicationConfiguration($app)
    {
        parent::resolveApplicationConfiguration($app);

        $configs = [
            'auth', 'cart', 'currency', 'geocoder', 'system',
        ];

        foreach ($configs as $config) {
            $app['config']->set("igniter.$config", require(__DIR__."/../config/{$config}.php"));
        }
    }
}
