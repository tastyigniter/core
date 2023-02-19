<?php

namespace Igniter\System\Console\Commands;

use Igniter\Flame\Igniter;
use Igniter\Main\Models\Theme;
use Igniter\System\Classes\PackageManifest;
use Igniter\System\Classes\UpdateManager;
use Igniter\System\Facades\Assets;
use Igniter\System\Helpers\CacheHelper;
use Igniter\System\Helpers\SystemHelper;
use Igniter\System\Models\Extension;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
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

        resolve(UpdateManager::class)->setCoreVersion();

        $this->comment('*** TastyIgniter sets latest version: '.params('ti_version'));

        if ($this->option('extensions'))
            $this->setItemsVersion();

        $this->comment('-');
        sleep(1);
        $this->comment('Ping? Pong!');
        sleep(1);
        $this->comment('Ping? Pong!');
        sleep(1);
        $this->comment('Ping? Pong!');
        sleep(1);
        $this->comment('-');
    }

    protected function utilCompileJs()
    {
        $this->utilCompileAssets('js');
    }

    protected function utilCompileScss()
    {
        $this->utilCompileAssets('scss');
    }

    protected function utilCompileAssets($type = null)
    {
        $this->comment('Compiling registered asset bundles...');

        config()->set('system.enableAssetMinify', (bool)$this->option('minify', true));
        $appContext = $this->option('admin') ? 'admin' : 'main';
        $bundles = Assets::getBundles($type, $appContext);

        if (!$bundles) {
            $this->comment('Nothing to compile!');

            return;
        }

        foreach ($bundles as $destination => $assets) {
            $destination = File::symbolizePath($destination);
            $publicDestination = File::localToPublic(realpath(dirname($destination))).'/'.basename($destination);

            Assets::combineToFile($assets, $destination);
            $this->comment(implode(', ', array_map('basename', $assets)));
            $this->comment(sprintf(' -> %s', $publicDestination));
        }
    }

    protected function utilRemoveDuplicates()
    {
        $this->comment('Removing duplicate views...');

        $directoryToScan = new \RecursiveDirectoryIterator(base_path());
        $directoryIterator = new \RecursiveIteratorIterator($directoryToScan);
        $files = new \RegexIterator($directoryIterator, '#(?:\.blade\.php)$#Di');

        $removeCount = 0;
        foreach ($files as $file) {
            $pagicPath = str_replace('.blade.php', '.php', $file->getPathName());
            if (file_exists($pagicPath)) {
                unlink($pagicPath);
                $this->comment('Removed '.$pagicPath);
                $removeCount++;
            }
        }

        $this->comment('Removed '.$removeCount.' duplicate views...');
    }

    protected function utilSetCarte()
    {
        $carteKey = $this->option('key');
        if (!strlen($carteKey)) {
            $this->error('No carteKey defined, use --key=<key> to set a Carte');

            return;
        }

        SystemHelper::replaceInEnv('IGNITER_CARTE_KEY=', 'IGNITER_CARTE_KEY='.$carteKey);

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

            $this->output->writeln('Theme ['.$theme->name.'] set as default ');
        }
    }

    protected function setItemsVersion()
    {
        $manifest = resolve(PackageManifest::class);
        $manifest->build();

        collect($manifest->packages())
            ->each(function ($update) {
                if ($update['type'] === 'tastyigniter-extension')
                    Extension::where('name', $update['code'])->update(['version' => $update['version']]);

                if ($update['type'] === 'tastyigniter-theme')
                    Theme::where('code', $update['code'])->update(['version' => $update['version']]);

                $this->comment('*** '.$update['code'].' sets latest version: '.$update['version']);
            });
    }
}
