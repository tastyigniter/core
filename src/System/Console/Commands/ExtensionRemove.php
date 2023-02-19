<?php

namespace Igniter\System\Console\Commands;

use Igniter\System\Classes\ExtensionManager;
use Illuminate\Console\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

class ExtensionRemove extends Command
{
    use \Illuminate\Console\ConfirmableTrait;

    /**
     * The console command name.
     * @var string
     */
    protected $name = 'extension:remove';

    /**
     * The console command description.
     * @var string
     */
    protected $description = 'Removes an existing extension.';

    /**
     * Execute the console command.
     * @return void
     */
    public function handle()
    {
        $forceDelete = $this->option('force');
        $extensionName = $this->argument('name');
        $extensionManager = resolve(ExtensionManager::class);

        $extensionName = $extensionManager->getIdentifier(strtolower($extensionName));
        if (!$extensionManager->hasExtension($extensionName)) {
            return $this->error(sprintf('Unable to find a registered extension called "%s"', $extensionName));
        }

        if (!$forceDelete && !$this->confirmToProceed(sprintf(
                'This will DELETE extension "%s" from the filesystem and database.',
                $extensionName
            ))) {
            return;
        }

        $extensionManager->deleteExtension($extensionName);
        $this->output->writeln(sprintf('<info>Deleted extension: %s</info>', $extensionName));
    }

    /**
     * Get the console command arguments.
     * @return array
     */
    protected function getArguments()
    {
        return [
            ['name', InputArgument::REQUIRED, 'The name of the extension. Eg: IgniterLab.Demo'],
        ];
    }

    /**
     * Get the console command options.
     * @return array
     */
    protected function getOptions()
    {
        return [
            ['force', null, InputOption::VALUE_NONE, 'Force remove.'],
        ];
    }

    /**
     * Get the default confirmation callback.
     * @return \Closure
     */
    protected function getDefaultConfirmCallback()
    {
        return function () {
            return true;
        };
    }
}
