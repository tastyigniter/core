<?php

namespace Igniter\System\Console\Commands;

use Igniter\Main\Classes\Theme;
use Igniter\Main\Classes\ThemeManager;
use Illuminate\Foundation\Console\VendorPublishCommand;
use Symfony\Component\Console\Input\InputOption;

class ThemeVendorPublish extends VendorPublishCommand
{
    use \Illuminate\Console\ConfirmableTrait;

    /**
     * The console command name.
     * @var string
     */
    protected $name = 'igniter:theme-vendor-publish';

    /**
     * The console command description.
     * @var string
     */
    protected $description = 'Publish any publishable assets from themes';

    protected $signature;

    /**
     * The themes to publish.
     */
    protected array $themes = [];

    /**
     * Execute the console command.
     * @return void
     */
    public function handle()
    {
        $this->determineWhatShouldBePublished();

        foreach ($this->themes as $theme) {
            $this->publishTheme($theme);
        }

        $this->info('Publishing complete.');
    }

    protected function determineWhatShouldBePublished()
    {
        $themeManager = resolve(ThemeManager::class);

        $themeManager->loadThemes();

        if (!$this->option('theme')) {
            $this->themes = $themeManager->listThemes();
        }

        foreach ((array)$this->option('theme') as $theme) {
            $this->themes[$theme] = $themeManager->findTheme($theme);
        }
    }

    /**
     * Publishes the assets for a theme.
     */
    protected function publishTheme(Theme $theme)
    {
        $published = false;

        foreach ($theme->getPathsToPublish() as $from => $publishTo) {
            $this->publishItem($from, $publishTo);

            $published = true;
        }

        if ($published === false) {
            $this->comment('No publishable resources for theme ['.$theme->getName().'].');
        }
    }

    /**
     * Get the console command options.
     * @return array
     */
    protected function getOptions()
    {
        return [
            ['existing', null, InputOption::VALUE_NONE, 'Publish and overwrite only the files that have already been published'],
            ['all', null, InputOption::VALUE_NONE, 'Publish assets for all themes without prompt.'],
            ['theme', null, InputOption::VALUE_OPTIONAL, 'One or many theme that have assets you want to publish.'],
            ['force', null, InputOption::VALUE_NONE, 'Force publish.'],
        ];
    }
}
