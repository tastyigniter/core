<?php

namespace Igniter\System\Console\Commands;

use Igniter\Flame\Filesystem\Filesystem;
use Igniter\Flame\Igniter;
use Igniter\System\Classes\UpdateManager;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class IgniterUp extends Command
{
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
        if (Igniter::hasDatabase()) {
            $this->dropConflictingFoundationTables();
        }

        if (!$this->migrationFileExists('create_notifications_table')) {
            $this->call('notifications:table');
        }

        $this->output->writeln('<info>Migrating foundation...</info>');

        $this->call('migrate');

        $this->output->writeln('<info>Migrating application and extensions...</info>');

        $manager = resolve(UpdateManager::class);
        $manager->setLogsOutput($this->output);
        $manager->migrate();
    }

    protected function dropConflictingFoundationTables()
    {
        if (!DB::table('settings')->where('item', 'ti_version')->where('value', 'like', 'v3.%')->exists()) {
            return;
        }

        if (!Schema::hasTable('user_preferences')) {
            return;
        }

        $this->output->writeln('<info>Dropping default foundation tables...</info>');

        Schema::rename('user_groups', 'admin_user_groups');
        Schema::rename('user_preferences', 'admin_user_preferences');
        Schema::rename('user_roles', 'admin_user_roles');
        Schema::rename('users_groups', 'admin_users_groups');

        Schema::dropIfExists('cache');
        Schema::dropIfExists('failed_jobs');
        Schema::dropIfExists('jobs');
        Schema::dropIfExists('job_batches');
        Schema::dropIfExists('sessions');
    }

    protected function migrationFileExists($name): bool
    {
        $path = $this->laravel->databasePath().'/migrations';

        return collect($this->files->allFiles($path))->filter(function ($file) use ($name) {
            return str_contains($file, $name);
        })->isNotEmpty();
    }
}
