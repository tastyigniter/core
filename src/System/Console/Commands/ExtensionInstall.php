<?php

namespace Igniter\System\Console\Commands;

use Igniter\Flame\Exception\ComposerException;
use Igniter\System\Classes\ExtensionManager;
use Igniter\System\Classes\UpdateManager;
use Illuminate\Console\Command;
use Symfony\Component\Console\Input\InputArgument;

class ExtensionInstall extends Command
{
    /**
     * The console command name.
     */
    protected $name = 'igniter:extension-install';

    /**
     * The console command description.
     */
    protected $description = 'Install an extension from the TastyIgniter marketplace.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $extensionName = $this->argument('name');
        $updateManager = resolve(UpdateManager::class)->setLogsOutput($this->output);

        $response = $updateManager->requestApplyItems([[
            'name' => $extensionName,
            'type' => 'extension',
        ]]);

        if (!$packageInfo = $response->first()) {
            return $this->output->writeln(sprintf('<info>Extension %s not found</info>', $extensionName));
        }

        try {
            $this->output->writeln(sprintf('<info>Installing %s extension</info>', $extensionName));
            $updateManager->install($response->all());

            resolve(ExtensionManager::class)->loadExtensions();
            resolve(ExtensionManager::class)->installExtension($packageInfo->code, $packageInfo->version);
        } catch (ComposerException $e) {
            $this->output->writeln($e->getMessage());
        }
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
}
