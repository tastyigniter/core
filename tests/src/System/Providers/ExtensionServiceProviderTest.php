<?php

declare(strict_types=1);

namespace Igniter\Tests\System\Providers;

use Igniter\System\Classes\BaseExtension;
use Igniter\System\Classes\ExtensionManager;
use Igniter\System\Providers\ExtensionServiceProvider;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Event;

it('registers all extensions', function() {
    $extension1 = mock(BaseExtension::class);
    $extension2 = mock(BaseExtension::class);
    $extensionManager = mock(ExtensionManager::class);
    app()->instance(ExtensionManager::class, $extensionManager);
    $extensionManager->shouldReceive('getExtensions')->andReturn([$extension1, $extension2]);

    $appMock = mock(Application::class);
    $appMock->shouldReceive('register')->with($extension1)->once();
    $appMock->shouldReceive('register')->with($extension2)->once();

    (new ExtensionServiceProvider($appMock))->register();
});

it('allows extensions to use the scheduler', function() {
    $extension1 = mock(BaseExtension::class);
    $extension2 = mock(BaseExtension::class);
    $extensionManager = mock(ExtensionManager::class);
    app()->instance(ExtensionManager::class, $extensionManager);
    $extensionManager->shouldReceive('getExtensions')->andReturn([$extension1, $extension2]);

    $appMock = mock(Application::class);
    $appMock->shouldReceive('register')->with($extension1)->once();
    $appMock->shouldReceive('register')->with($extension2)->once();

    $schedule = mock(Schedule::class);
    $extension1->shouldReceive('registerSchedule')->with($schedule)->once();
    $extension2->shouldReceive('registerSchedule')->with($schedule)->once();

    Event::shouldReceive('listen')->withArgs(function($event, $callback) use ($schedule): bool {
        $callback($schedule);

        return $event === 'console.schedule';
    });

    (new ExtensionServiceProvider($appMock))->register();
});
