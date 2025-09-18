<?php

declare(strict_types=1);

namespace Igniter\Tests\System\EventSubscribers;

use Facades\Igniter\System\Helpers\CacheHelper;
use Igniter\Flame\Filesystem\Filesystem;
use Igniter\Flame\Support\Facades\Igniter;
use Igniter\System\Classes\PackageManifest;
use Igniter\System\EventSubscribers\ConsoleSubscriber;
use Illuminate\Console\Events\CommandFinished;
use Illuminate\Console\Events\CommandStarting;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Events\Dispatcher;

it('subscribes to console events correctly', function() {
    $subscriber = new ConsoleSubscriber;

    expect($subscriber->subscribe(new Dispatcher))
        ->toHaveKey('console.schedule', 'defineSchedule')
        ->toHaveKey(CommandStarting::class, 'handleCommandStarting')
        ->toHaveKey(CommandFinished::class, 'handleCommandFinished');
});

it('defines schedule correctly', function() {
    Igniter::shouldReceive('prunableModels')->andReturn([]);
    $schedule = mock(Schedule::class);
    $schedule->shouldReceive('command')->with('igniter:update', ['--check'])->andReturnSelf();
    $schedule->shouldReceive('name')->with('System Updates Checker')->once()->andReturnSelf();
    $schedule->shouldReceive('everyThreeHours')->andReturnSelf();
    $schedule->shouldReceive('evenInMaintenanceMode')->andReturnSelf();
    $schedule->shouldReceive('command')->withSomeOfArgs('model:prune')->once()->andReturnSelf();
    $schedule->shouldReceive('name')->with('Prunable Models Checker')->andReturnSelf();
    $schedule->shouldReceive('daily')->andReturnSelf();

    $subscriber = new ConsoleSubscriber;
    $subscriber->defineSchedule($schedule);
});

it('handles command starting event', function() {
    $event = mock(CommandStarting::class);
    $subscriber = new ConsoleSubscriber;

    expect($subscriber->handleCommandStarting($event))->toBeNull();
});

it('handles command finished event for package:discover', function() {
    $event = mock(CommandFinished::class);
    $event->command = 'package:discover';
    // Discover packages
    $packageManifest = mock(PackageManifest::class);
    app()->instance(PackageManifest::class, $packageManifest);
    $filesystem = mock(Filesystem::class);
    $packageManifest->files = $filesystem;
    $filesystem->shouldReceive('exists')->andReturn(true);
    $filesystem->shouldReceive('delete')->andReturnNull();
    $packageManifest->shouldReceive('build')->once()->andReturnSelf();
    $packageManifest->shouldReceive('packages')->andReturn([
        ['code' => 'igniter.demo', 'name' => 'Demo'],
        ['code' => 'igniter.blog', 'name' => 'Blog'],
    ]);

    $subscriber = new ConsoleSubscriber;
    $subscriber->handleCommandFinished($event);
});

it('handles command finished event for clear-compiled', function() {
    $event = mock(CommandFinished::class);
    $event->command = 'clear-compiled';
    CacheHelper::shouldReceive('clearCompiled')->once();

    $subscriber = new ConsoleSubscriber;
    $subscriber->handleCommandFinished($event);
});

it('handles command finished event for other commands', function() {
    $event = mock(CommandFinished::class);
    $event->command = 'other-command';

    $subscriber = new ConsoleSubscriber;
    expect($subscriber->handleCommandFinished($event))->toBeNull();
    // No assertions needed as the default case does nothing
});
