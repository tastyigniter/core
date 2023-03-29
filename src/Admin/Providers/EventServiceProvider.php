<?php

namespace Igniter\Admin\Providers;

use Igniter\Admin\Classes\Allocator;
use Igniter\Admin\Classes\UserState;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\Event;

class EventServiceProvider extends \Illuminate\Foundation\Support\Providers\EventServiceProvider
{
    public function boot()
    {
        Event::listen('console.schedule', function (Schedule $schedule) {
            // Check for assignables to assign every minute
            if (Allocator::isEnabled()) {
                $schedule->call(function () {
                    Allocator::allocate();
                })->name('Assignables Allocator')->withoutOverlapping(5)->runInBackground()->everyMinute();
            }

            $schedule->call(function () {
                UserState::clearExpiredStatus();
            })->name('Clear user custom away status')->withoutOverlapping(5)->runInBackground()->everyMinute();
        });
    }
}