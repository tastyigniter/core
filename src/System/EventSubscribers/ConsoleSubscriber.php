<?php

namespace Igniter\System\EventSubscribers;

use Igniter\Flame\Igniter;
use Igniter\System\Helpers\CacheHelper;
use Illuminate\Console\Events\CommandFinished;
use Illuminate\Console\Events\CommandStarting;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Events\Dispatcher;
use Illuminate\Support\Facades\Artisan;

class ConsoleSubscriber
{
    public function subscribe(Dispatcher $events): array
    {
        return [
            'console.schedule' => 'defineSchedule',
            CommandStarting::class => 'handleCommandStarting',
            CommandFinished::class => 'handleCommandFinished',
        ];
    }

    protected function defineSchedule(Schedule $schedule)
    {
        // Every 12 hours check for system updates
        $schedule->command('igniter:update', ['--check' => true])
            ->name('System Updates Checker')
            ->everyThreeHours()
            ->evenInMaintenanceMode();

        // Daily check for model records to prune every day
        $schedule->command('model:prune', [
            '--model' => Igniter::prunableModels(),
        ])->name('Prunable Models Checker')->daily();
    }

    public function handleCommandStarting(CommandStarting $event) {}

    public function handleCommandFinished(CommandFinished $event)
    {
        match ($event->command) {
            'package:discover' => Artisan::call('igniter:package-discover', [], $event->output),
            'clear-compiled' => CacheHelper::clearCompiled(),
            default => null,
        };
    }
}
