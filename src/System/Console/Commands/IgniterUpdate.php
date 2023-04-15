<?php

namespace Igniter\System\Console\Commands;

use Igniter\Flame\Exception\ComposerException;
use Igniter\System\Classes\UpdateManager;
use Illuminate\Console\Command;
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

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $forceUpdate = $this->option('force');

        // update system
        $updateManager = resolve(UpdateManager::class)->setLogsOutput($this->output);
        $this->output->writeln('<info>Updating TastyIgniter...</info>');

        $updates = $updateManager->requestUpdateList($forceUpdate);
        $itemsToUpdate = array_get($updates, 'items');

        if (!$itemsToUpdate) {
            $this->output->writeln('<info>No new updates found</info>');

            return;
        }

        $updatesCollection = collect($itemsToUpdate)->groupBy('type');

        try {
            if ($coreUpdate = optional($updatesCollection->pull('core'))->first()) {
                $this->output->writeln('<info>Updating core...</info>');
                $updateManager->install($coreUpdate);
            }

            $this->output->writeln('<info>Updating extensions/themes...</info>');
            $updateManager->install($updatesCollection->flatten(1)->all());

            // Run migrations
            $this->call('igniter:up');
        } catch (ComposerException $e) {
            $this->output->writeln($e->getMessage());
        }
    }

    /**
     * Get the console command options.
     */
    protected function getOptions()
    {
        return [
            ['force', null, InputOption::VALUE_NONE, 'Force updates.'],
            ['core', null, InputOption::VALUE_NONE, 'Update core application files only.'],
            ['addons', null, InputOption::VALUE_NONE, 'Update both extensions & themes files.'],
        ];
    }
}
