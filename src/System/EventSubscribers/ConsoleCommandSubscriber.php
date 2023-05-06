<?php

namespace Igniter\System\EventSubscribers;

use Illuminate\Console\Events\CommandFinished;
use Illuminate\Console\Events\CommandStarting;
use Illuminate\Events\Dispatcher;
use Illuminate\Support\Facades\Artisan;

class ConsoleCommandSubscriber
{
    public function handleCommandStarting(CommandStarting $event): void
    {
    }

    public function handleCommandFinished(CommandFinished $event): void
    {
        match ($event->command) {
            'package:discover' => $this->handleAfterPackageDiscover($event),
            default => null,
        };
    }

    public function subscribe(Dispatcher $events): array
    {
        return [
            CommandStarting::class => 'handleCommandStarting',
            CommandFinished::class => 'handleCommandFinished',
        ];
    }

    protected function handleAfterPackageDiscover(CommandFinished $event): int
    {
        return Artisan::call('igniter:package-discover', [], $event->output);
    }
}