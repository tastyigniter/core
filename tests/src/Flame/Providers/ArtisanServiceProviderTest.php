<?php

declare(strict_types=1);

namespace Igniter\Tests\Flame\Providers;

use Igniter\Flame\Providers\ArtisanServiceProvider;
use Illuminate\Console\Events\ArtisanStarting;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Foundation\Console\Kernel;
use Illuminate\Support\Facades\Event;
use Illuminate\Console\Scheduling\Schedule;

it('dispatches console.subscribe event when starting artisan command', function(): void {
    $app = mock(Application::class);
    $app->shouldReceive('runningInConsole')->once()->andReturnTrue();
    $app->shouldReceive('runningUnitTests')->once()->andReturnFalse();
    $app->shouldReceive('make')->once()->andReturn(mock(Schedule::class));
    $serviceProvider = new ArtisanServiceProvider($app);
    $serviceProvider->boot();
    Event::fake(['console.schedule']);

    Event::dispatch(ArtisanStarting::class, [resolve(Kernel::class)]);

    Event::assertDispatched('console.schedule');
});
