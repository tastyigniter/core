<?php

namespace Igniter\System\Console\Commands;

use Igniter\Flame\Exception\ComposerException;
use Igniter\System\Classes\UpdateManager;
use Igniter\System\Notifications\UpdateFoundNotification;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Event;
use Symfony\Component\Console\Input\InputOption;

/**
 * Console command to perform a system update.
 * This updates TastyIgniter and all extensions, database and files. It uses the
 * TastyIgniter gateway to receive the files via a package manager, then saves
 * the latest version number to the system.
 */
class IgniterUpdate extends Command
{
    /**
     * The console command name.
     */
    protected $name = 'igniter:update';

    /**
     * The console command description.
     */
    protected $description = 'Updates TastyIgniter and all extensions, database and files.';

    protected UpdateManager $updateManager;

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $forceUpdate = (bool)$this->option('force');

        if ($this->option('check')) {
            $this->notifyOnUpdatesFound();
        }

        $this->updateManager = resolve(UpdateManager::class)->setLogsOutput($this->output);
        $this->output->writeln('<info>Checking for updates...</info>');

        if (!$itemsToUpdate = $this->getItemsToUpdate($forceUpdate)) {
            $this->output->writeln('<info>No new updates found</info>');

            return;
        }

        $this->updateItems($itemsToUpdate);

        // Run migrations
        $this->call('igniter:up');
    }

    /**
     * Get the console command options.
     */
    protected function getOptions()
    {
        return [
            ['force', null, InputOption::VALUE_NONE, 'Force updates.'],
            ['check', null, InputOption::VALUE_NONE, 'Run update checks only.'],
            ['core', null, InputOption::VALUE_NONE, 'Update core application files only.'],
            ['addon', null, InputOption::VALUE_OPTIONAL | InputOption::VALUE_IS_ARRAY, 'Update specified extensions & themes files only.'],
        ];
    }

    protected function updateItems(array $itemsToUpdate): void
    {
        try {
            $updatesCollection = collect($itemsToUpdate)->groupBy('type');

            if (!$this->option('addons')) {
                $this->output->writeln('<info>Updating TastyIgniter...</info>');
                $this->updateCore($updatesCollection);
            }

            $updatesCollection = $updatesCollection->except('core')->flatten(1);
            if ($addons = (array)$this->option('addons')) {
                $updatesCollection = $updatesCollection->filter(function ($item) use ($addons) {
                    return in_array($item->code, $addons);
                });
            }

            if ($updatesCollection->count()) {
                $this->output->writeln('<info>Updating TastyIgniter extensions/themes...</info>');
                $this->updateManager->install($updatesCollection->all());
            }
        } catch (ComposerException $e) {
            $this->output->writeln($e->getMessage());
        }
    }

    protected function updateCore(Collection $updatesCollection): void
    {
        if ($coreUpdate = optional($updatesCollection->pull('core'))->first()) {
            $this->output->writeln('<info>Updating core...</info>');
            $this->updateManager->install($coreUpdate);
        }
    }

    protected function notifyOnUpdatesFound()
    {
        Event::listen('igniter.system.updatesFound', function ($result) {
            UpdateFoundNotification::make(array_only($result, ['count']))->broadcast();
        });
    }

    protected function getItemsToUpdate(bool $forceUpdate): array
    {
        $updates = $this->updateManager->requestUpdateList($forceUpdate);

        return array_get($updates, 'items');
    }
}
