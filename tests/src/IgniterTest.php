<?php

namespace Igniter\Tests;

use Igniter\Flame\Support\Facades\Igniter;

it('checks for admin routes', function() {
    $adminUri = Igniter::adminUri();

    $this->get('/'.$adminUri);
    expect(Igniter::runningInAdmin())->toBeTrue();

    $this->get('/'.$adminUri.'-login');
    expect(Igniter::runningInAdmin())->toBeFalse();

    $this->get('/'.$adminUri.'/login');
    expect(Igniter::runningInAdmin())->toBeTrue();
});
