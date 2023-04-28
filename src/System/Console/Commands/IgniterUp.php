<?php

namespace Igniter\System\Console\Commands;

use Igniter\Flame\Filesystem\Filesystem;
use Igniter\System\Classes\UpdateManager;
use Illuminate\Console\Command;
use Illuminate\Console\ConfirmableTrait;
use Illuminate\Console\View\Components\Info;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\Console\Input\InputOption;

class IgniterUp extends Command
{
    use ConfirmableTrait;

    /**
     * The console command name.
     * @var string
     */
    protected $name = 'igniter:up';

    /**
     * The console command description.
     * @var string
     */
    protected $description = 'Builds database tables for TastyIgniter and all extensions.';

    protected Filesystem $files;

    public function __construct(Filesystem $files)
    {
        parent::__construct();

        $this->files = $files;
    }

    /**
     * Execute the console command.
     * @return void
     */
    public function handle()
    {
        if (!$this->confirmToProceed()) {
            return 1;
        }

        if (!$this->migrationFileExists('create_notifications_table')) {
            $this->call('notifications:table');
        }

        resolve('migrator')->getRepository()->prepareMigrationTable();

        $this->renameConflictingFoundationTables();

        $this->call('migrate', ['--force' => true]);

        resolve(UpdateManager::class)
            ->setLogsOutput($this->output)
            ->migrate();
    }

    protected function renameConflictingFoundationTables()
    {
        foreach ([
            'users' => 'admin_users',
            'cache' => 'cache_bck',
            'failed_jobs' => 'failed_jobs_bck',
            'jobs' => 'jobs_bck',
            'job_batches' => 'job_batches_bck',
            'sessions' => 'sessions_bck',
        ] as $from => $to) {
            if (Schema::hasTable($from) && !Schema::hasTable($to)) {
                $this->output->write(Info::class, sprintf('Renaming table %s to %s', $from, $to));
                Schema::rename($from, $to);
            }
        }
    }

    protected function migrationFileExists($name): bool
    {
        $path = $this->laravel->databasePath().'/migrations';

        return collect($this->files->allFiles($path))->filter(function ($file) use ($name) {
            return str_contains($file, $name);
        })->isNotEmpty();
    }

    protected function getOptions()
    {
        return [
            ['force', null, InputOption::VALUE_NONE, 'Force the operation to run when in production'],
        ];
    }
}
