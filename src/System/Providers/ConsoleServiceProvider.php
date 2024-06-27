<?php

namespace Igniter\System\Providers;

use Igniter\Flame\Providers\ConsoleServiceProvider as BaseConsoleServiceProvider;
use Igniter\System\Console;
use Igniter\System\EventSubscribers\ConsoleSubscriber;

class ConsoleServiceProvider extends BaseConsoleServiceProvider
{
    protected $subscribe = [
        ConsoleSubscriber::class,
    ];

    protected $commands = [
        'util' => Console\Commands\IgniterUtil::class,
        'up' => Console\Commands\IgniterUp::class,
        'down' => Console\Commands\IgniterDown::class,
        'package-discover' => Console\Commands\IgniterPackageDiscover::class,
        'install' => Console\Commands\IgniterInstall::class,
        'update' => Console\Commands\IgniterUpdate::class,
        'passwd' => Console\Commands\IgniterPasswd::class,
        'extension.install' => Console\Commands\ExtensionInstall::class,
        'extension.refresh' => Console\Commands\ExtensionRefresh::class,
        'extension.remove' => Console\Commands\ExtensionRemove::class,
        'theme.install' => Console\Commands\ThemeInstall::class,
        'theme.remove' => Console\Commands\ThemeRemove::class,
        'theme.vendor-publish' => Console\Commands\ThemeVendorPublish::class,
        'language.install' => Console\Commands\LanguageInstall::class,
    ];
}
