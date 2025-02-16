<?php

declare(strict_types=1);

namespace Igniter\Tests\Admin\Http\Controllers;

use Igniter\Admin\Http\Controllers\Dashboard;

it('loads dashboard page', function() {
    Dashboard::extend(function(Dashboard $controller) {
        $controller->extendDashboardContainer(function($widget) {
            return true;
        });
    });

    actingAsSuperUser()
        ->get(route('igniter.admin.dashboard'))
        ->assertOk();
});
