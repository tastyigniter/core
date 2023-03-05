<?php

namespace Igniter\System\Console\Commands;

use Igniter\Flame\Filesystem\Filesystem;
use Igniter\System\Classes\UpdateManager;
use Illuminate\Console\Command;

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
        $this->output->writeln('<info>Migrating foundation...</info>');

        if (!$this->migrationFileExists('create_notifications_table')) {
            $this->call('notifications:table');
        }

        $this->call('migrate');

        $this->output->writeln('<info>Migrating application and extensions...</info>');

        $manager = resolve(UpdateManager::class);
        $manager->setLogsOutput($this->output);
        $manager->migrate();
    }

    protected function migrationFileExists($name): bool
    {
        $path = $this->laravel->databasePath().'/migrations';

        return collect($this->files->allFiles($path))->filter(function ($file) use ($name) {
            return str_contains($file, $name);
        })->isNotEmpty();
    }
}
