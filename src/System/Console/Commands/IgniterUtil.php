<?php

declare(strict_types=1);

namespace Igniter\System\Console\Commands;

use Facades\Igniter\System\Helpers\CacheHelper;
use Igniter\Flame\Support\Facades\Igniter;
use Igniter\Main\Classes\ThemeManager;
use Igniter\Main\Models\Theme;
use Igniter\System\Classes\PackageManifest;
use Igniter\System\Classes\UpdateManager;
use Igniter\System\Facades\Assets;
use Igniter\System\Models\Extension;
use Illuminate\Console\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

class IgniterUtil extends Command
{
    /**
     * The console command name.
     * @var string
     */
    protected $name = 'igniter:util';

    /**
     * The console command description.
     * @var string
     */
    protected $description = 'TastyIgniter Utility commands.';

    /**
     * Execute the console command.
     * @return void
     */
    public function handle()
    {
        $command = implode(' ', (array)$this->argument('name'));
        $method = 'util'.studly_case($command);

        if (!method_exists($this, $method)) {
            $this->error(sprintf('Utility command "%s" does not exist!', $command));

            return;
        }

        $this->$method();
    }

    /**
     * Get the console command arguments.
     * @return array
     */
    protected function getArguments()
    {
        return [
            ['name', InputArgument::IS_ARRAY, 'The utility command to perform.'],
        ];
    }

    /**
     * Get the console command options.
     */
    protected function getOptions()
    {
        return [
            ['admin', null, InputOption::VALUE_NONE, 'Compile admin registered bundles.'],
            ['minify', null, InputOption::VALUE_REQUIRED, 'Whether to minify the assets or not, default is 1.'],
            ['carteKey', null, InputOption::VALUE_REQUIRED, 'Specify a carteKey for set carte.'],
            ['theme', null, InputOption::VALUE_REQUIRED, 'Specify a theme code to set as default.'],
            ['extensions', null, InputOption::VALUE_NONE, 'Set the version number of all extensions to the latest available.'],
        ];
    }

    protected function utilSetVersion()
    {
        $this->comment('Setting TastyIgniter version number...');

        if (!Igniter::hasDatabase()) {
            $this->comment('Skipping - No database detected.');

            return;
        }

        $this->comment('*** TastyIgniter latest version: '.Igniter::version());

        if ($this->option('extensions')) {
            $this->setItemsVersion();
        }
    }

    protected function utilCompileScss()
    {
        $this->comment('Compiling registered asset bundles...');

        $activeTheme = resolve(ThemeManager::class)->getActiveTheme();

        $notes = $activeTheme ? Assets::buildBundles($activeTheme) : [];

        if (!$notes) {
            $this->comment('Nothing to compile!');

            return;
        }

        foreach ($notes as $note) {
            $this->comment($note);
        }
    }

    protected function utilSetCarte()
    {
        $this->comment('Setting Carte Key...');

        $carteKey = $this->option('carteKey');
        if (!strlen($carteKey)) {
            $this->error('No carteKey defined, use --key=<key> to set a Carte');

            return;
        }

        resolve(UpdateManager::class)->applySiteDetail($carteKey);
    }

    protected function utilSetTheme()
    {
        $themeName = $this->option('theme');
        if (!strlen($themeName)) {
            $this->error('No theme defined, use --theme=<code> to set a theme');

            return;
        }

        if ($theme = Theme::activateTheme($themeName)) {
            CacheHelper::clearView();

            $this->output->writeln('Theme ['.$theme->name.'] set as default');
        }
    }

    protected function setItemsVersion()
    {
        $manifest = resolve(PackageManifest::class);
        $manifest->build();

        collect($manifest->packages())
            ->each(function($update) {
                if ($update['type'] === 'tastyigniter-extension') {
                    Extension::where('name', $update['code'])->update(['version' => $update['version']]);
                }

                if ($update['type'] === 'tastyigniter-theme') {
                    Theme::where('code', $update['code'])->update(['version' => $update['version']]);
                }

                $this->comment('*** '.$update['code'].' latest version: '.$update['version']);
            });
    }
}
