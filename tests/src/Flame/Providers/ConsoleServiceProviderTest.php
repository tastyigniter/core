<?php

namespace Igniter\Tests\Flame\Providers;

use Igniter\Flame\Providers\ConsoleServiceProvider;
use Igniter\Tests\Flame\Providers\Fixtures\TestCommand;

it('registers commands with numeric keys', function() {
    $app = app();
    $provider = new class($app) extends ConsoleServiceProvider
    {
        protected $commands = [
            TestCommand::class,
        ];
    };

    $provider->register();

    expect($app->bound(TestCommand::class))->toBeTrue();
});
