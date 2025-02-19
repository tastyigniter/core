<?php

declare(strict_types=1);

namespace Igniter\System\Console\Commands;

use Igniter\System\Classes\ExtensionManager;
use Igniter\System\Classes\UpdateManager;
use Illuminate\Console\Command;
use Symfony\Component\Console\Input\InputArgument;
use Throwable;

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
            $this->output->writeln(sprintf('<info>Extension %s not found</info>', $extensionName));
            return null;
        }

        try {
            $this->output->writeln(sprintf('<info>Installing %s extension</info>', $extensionName));
            $updateManager->install($response->all());

            $extensionManager = resolve(ExtensionManager::class);
            $extensionManager->loadExtensions();
            $extensionManager->installExtension($packageInfo->code, $packageInfo->version);
        } catch (Throwable $throwable) {
            $this->output->writeln($throwable->getMessage());
        }

        return null;
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
