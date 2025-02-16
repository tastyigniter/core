<?php

declare(strict_types=1);

namespace Igniter\Tests\Flame\Currency\Console;

use Igniter\Flame\Currency\Facades\Currency;

it('cleans and rebuilds currency cache', function() {
    Currency::shouldReceive('clearCache')->once();
    Currency::shouldReceive('getCurrencies')->once();

    $this->artisan('currency:cleanup')
        ->expectsOutput('Currency cache cleaned.')
        ->expectsOutput('Currency cache rebuilt.')
        ->assertExitCode(0);
});
