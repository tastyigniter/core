<?php

namespace Igniter\Flame\Currency\Console;

use Igniter\Flame\Currency\Currency;
use Illuminate\Console\Command;

class Cleanup extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'currency:cleanup';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Cleanup currency cache';

    protected ?Currency $currency = null;

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle()
    {
        $this->currency = app('currency');

        // Clear cache
        $this->currency->clearCache();
        $this->comment('Currency cache cleaned.');

        // Force the system to rebuild cache
        $this->currency->getCurrencies();
        $this->comment('Currency cache rebuilt.');
    }
}
