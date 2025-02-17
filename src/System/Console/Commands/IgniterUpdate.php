<?php

declare(strict_types=1);

namespace Igniter\System\Console\Commands;

use Igniter\Flame\Composer\Manager;
use Igniter\System\Classes\UpdateManager;
use Igniter\System\Notifications\UpdateFoundNotification;
use Illuminate\Console\Command;
use Illuminate\Console\ConfirmableTrait;
use Illuminate\Support\Collection;
use Symfony\Component\Console\Input\InputOption;
use Throwable;

/**
 * Console command to perform a system update.
 * This updates TastyIgniter and all extensions, database and files. It uses the
 * TastyIgniter gateway to receive the files via a package manager, then saves
 * the latest version number to the system.
 */
class IgniterUpdate extends Command
{
    use ConfirmableTrait;

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
    public function handle(): void
    {
        $forceUpdate = (bool)$this->option('force');

        resolve(Manager::class)->assertSchema();

        $this->updateManager = resolve(UpdateManager::class)->setLogsOutput($this->output);
        $this->output->writeln('<info>Checking for updates...</info>');

        $updates = $this->updateManager->requestUpdateList($forceUpdate);
        if (!$itemsToUpdate = array_get($updates, 'items')) {
            $this->output->writeln('<info>No new updates found</info>');

            return;
        }

        if ($this->option('check')) {
            UpdateFoundNotification::make(array_only($updates, ['count']))->broadcast();

            return;
        }

        $this->output->writeln(sprintf('<info>%s updates found</info>', array_get($updates, 'count')));
        if (!$this->confirmToProceed()) {
            return;
        }

        try {
            $this->updateItems($itemsToUpdate);

            // Run migrations
            $this->call('igniter:up');
        } catch (Throwable $throwable) {
            $this->output->writeln($throwable->getMessage());
        }
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
            ['addons', null, InputOption::VALUE_OPTIONAL | InputOption::VALUE_IS_ARRAY, 'Update specified extensions & themes files only.'],
        ];
    }

    protected function updateItems(Collection $itemsToUpdate): void
    {
        $updatesCollection = $itemsToUpdate->groupBy('type');

        if (!$this->option('addons')) {
            $this->updateCore($updatesCollection);
        }

        if ($this->option('core')) {
            return;
        }

        $updatesCollection = $updatesCollection->except('core')->flatten(1);
        if ($addons = (array)$this->option('addons')) {
            $updatesCollection = $updatesCollection->filter(function($item) use ($addons): bool {
                return in_array($item->code, $addons);
            });
        }

        if ($updatesCollection->count()) {
            $this->output->writeln('<info>Updating extensions/themes...</info>');
            $this->updateManager->install($updatesCollection->all());
        }
    }

    protected function updateCore(Collection $updatesCollection): void
    {
        if ($coreUpdate = optional($updatesCollection->pull('core'))->first()) {
            $this->output->writeln('<info>Updating TastyIgniter...</info>');
            $this->updateManager->install([$coreUpdate]);
        }
    }
}
