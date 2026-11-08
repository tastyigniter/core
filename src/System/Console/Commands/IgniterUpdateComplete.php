<?php

declare(strict_types=1);

namespace Igniter\System\Console\Commands;

use Igniter\System\Classes\PackageInfo;
use Igniter\System\Classes\UpdateManager;
use Illuminate\Console\Command;
use Override;
use Symfony\Component\Console\Input\InputOption;
use Throwable;

/**
 * Completes an install or update after composer has modified the vendor directory.
 * Runs in a fresh PHP process so that it boots against the updated vendor tree,
 * rather than the classes already loaded by the process that ran composer.
 */
class IgniterUpdateComplete extends Command
{
    /**
     * The console command name.
     */
    protected $name = 'igniter:update-complete';

    /**
     * The console command description.
     */
    protected $description = 'Completes the installation of updated packages and runs migrations.';

    /**
     * Indicates whether the command should be shown in the Artisan command list.
     */
    protected $hidden = true;

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        try {
            $packages = json_decode((string)$this->option('packages'), true, 512, JSON_THROW_ON_ERROR);

            $packages = collect(is_array($packages) ? $packages : [])
                ->map(fn(array $package): PackageInfo => PackageInfo::fromArray($package))
                ->all();

            $updateManager = resolve(UpdateManager::class)->setLogsOutput($this->output);
            $updateManager->completeInstall($packages);
            $updateManager->migrate();
        } catch (Throwable $throwable) {
            $this->output->writeln($throwable->getMessage());

            return self::FAILURE;
        }

        return self::SUCCESS;
    }

    /**
     * Get the console command options.
     */
    #[Override]
    protected function getOptions(): array
    {
        return [
            ['packages', null, InputOption::VALUE_REQUIRED, 'JSON encoded list of installed packages to complete.', '[]'],
        ];
    }
}
