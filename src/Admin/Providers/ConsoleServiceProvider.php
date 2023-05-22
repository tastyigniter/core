<?php

namespace Igniter\Admin\Providers;

use Igniter\Admin\Console\Commands\AllocatorCommand;
use Igniter\Admin\Console\Commands\ClearUserStateCommand;
use Igniter\Admin\EventSubscribers\ConsoleSubscriber;
use Igniter\Flame\Providers\ConsoleServiceProvider as BaseConsoleServiceProvider;

class ConsoleServiceProvider extends BaseConsoleServiceProvider
{
    protected $subscribe = [
        ConsoleSubscriber::class,
    ];

    protected $commands = [
        'assignable.allocator' => AllocatorCommand::class,
        'user-state.clear' => ClearUserStateCommand::class,
    ];
}