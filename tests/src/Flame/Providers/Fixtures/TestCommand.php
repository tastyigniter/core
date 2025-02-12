<?php

namespace Igniter\Tests\Flame\Providers\Fixtures;

use Illuminate\Console\Command;

class TestCommand extends Command
{
    /**
     * The console command name.
     * @var string
     */
    protected $name = 'igniter:test';

    /**
     * The console command description.
     * @var string
     */
    protected $description = 'Test command.';

    /**
     * Execute the console command.
     * @return void
     */
    public function handle() {}
}
