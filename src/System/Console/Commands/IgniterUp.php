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

        resolve('migrator')->getRepository()->prepareMigrationTable();

        $this->renameConflictingFoundationTables();

        $this->call('migrate', ['--force' => true]);

        resolve(UpdateManager::class)
            ->setLogsOutput($this->output)
            ->migrate();
    }

    protected function renameConflictingFoundationTables()
    {
        if (Schema::hasColumn('users', 'staff_id')) {
            $this->output->write(Info::class, 'Renaming tastyigniter admin users table to admin_users');
            Schema::rename('users', 'admin_users');
        }

        if (Schema::hasColumn('admin_users', 'reset_code')) {
            return;
        }

        foreach ([
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

    protected function getOptions()
    {
        return [
            ['force', null, InputOption::VALUE_NONE, 'Force the operation to run when in production'],
        ];
    }
}
