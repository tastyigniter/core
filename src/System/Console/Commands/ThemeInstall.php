<?php

declare(strict_types=1);

namespace Igniter\System\Console\Commands;

use Igniter\Main\Classes\ThemeManager;
use Igniter\System\Classes\UpdateManager;
use Illuminate\Console\Command;
use Symfony\Component\Console\Input\InputArgument;
use Throwable;

class ThemeInstall extends Command
{
    /**
     * The console command name.
     */
    protected $name = 'igniter:theme-install';

    /**
     * The console command description.
     */
    protected $description = 'Install an theme from the TastyIgniter marketplace.';

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        $themeName = $this->argument('name');
        $updateManager = resolve(UpdateManager::class)->setLogsOutput($this->output);

        $response = $updateManager->requestApplyItems([[
            'name' => $themeName,
            'type' => 'theme',
        ]]);

        if (!$packageInfo = $response->first()) {
            $this->output->writeln(sprintf('<info>Theme %s not found</info>', $themeName));

            return;
        }

        try {
            $this->output->writeln(sprintf('<info>Installing %s theme</info>', $themeName));
            $updateManager->install($response->all());

            resolve(ThemeManager::class)->loadThemes();
            resolve(ThemeManager::class)->installTheme($packageInfo->code, $packageInfo->version);
        } catch (Throwable $throwable) {
            $this->output->writeln($throwable->getMessage());
        }
    }

    /**
     * Get the console command arguments.
     * @return array
     */
    protected function getArguments()
    {
        return [
            ['name', InputArgument::REQUIRED, 'The name of the theme. Eg: demo'],
        ];
    }
}
