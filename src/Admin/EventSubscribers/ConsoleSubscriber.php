<?php

namespace Igniter\Admin\EventSubscribers;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Events\Dispatcher;

class ConsoleSubscriber
{
    public function subscribe(Dispatcher $events): array
    {
        return [
            'console.schedule' => 'defineSchedule',
        ];
    }

    public function defineSchedule(Schedule $schedule)
    {
        $this->checkForAssignablesToAssignEveryMinute($schedule);
        $this->clearUserExpiredCustomAwayStatus($schedule);
    }

    protected function checkForAssignablesToAssignEveryMinute(Schedule $schedule): void
    {
        $schedule->command('igniter:allocate-assignables')
            ->name('Assignables Allocator')
            ->withoutOverlapping(5)
            ->runInBackground()
            ->everyMinute();
    }

    protected function clearUserExpiredCustomAwayStatus(Schedule $schedule): void
    {
        $schedule->command('igniter:clear-user-state')
            ->name('Clear user custom away status')
            ->withoutOverlapping(5)
            ->runInBackground()
            ->everyMinute();
    }
}