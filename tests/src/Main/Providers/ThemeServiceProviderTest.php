<?php

namespace Igniter\Tests\Main\Providers;

use Igniter\Flame\Support\Facades\Igniter;
use Igniter\Main\Providers\ThemeServiceProvider;

it('does not load theme file when database is not available', function() {
    Igniter::partialMock()->shouldReceive('hasDatabase')->andReturnFalse()->once();

    $provider = new ThemeServiceProvider($this->app);
    $provider->boot();
});
