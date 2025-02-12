<?php

namespace Igniter\Tests\Flame\Currency\Console;

use Igniter\Flame\Currency\Facades\Currency;

it('updates exchange rates successfully', function() {
    Currency::shouldReceive('updateRates')->with(true)->once();

    $this->artisan('currency:update')
        ->assertExitCode(0);
});
