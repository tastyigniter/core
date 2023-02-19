<?php

namespace Igniter\System\Console\Commands;

use Igniter\Main\Classes\ThemeManager;
use Igniter\Main\Models\Theme;
use Illuminate\Console\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

class ThemeRemove extends Command
{
    use \Illuminate\Console\ConfirmableTrait;

    /**
     * The console command name.
     * @var string
     */
    protected $name = 'theme:remove';

    /**
     * The console command description.
     * @var string
     */
    protected $description = 'Removes an existing theme.';

    /**
     * Execute the console command.
     * @return void
     */
    public function handle()
    {
        $forceDelete = $this->option('force');
        $themeName = $this->argument('name');
        $themeManager = resolve(ThemeManager::class);

        $themeName = strtolower($themeName);
        if (!$themeManager->hasTheme($themeName)) {
            return $this->error(sprintf('Unable to find a registered theme called "%s"', $themeName));
        }

        if (!$forceDelete && !$this->confirmToProceed(sprintf(
                'This will DELETE theme "%s" from the filesystem and database.',
                $themeName
            ))) {
            return;
        }

        Theme::deleteTheme($themeName);
        $this->output->writeln(sprintf('<info>Deleted theme: %s</info>', $themeName));
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
