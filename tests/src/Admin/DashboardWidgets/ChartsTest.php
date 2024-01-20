<?php

namespace Tests\Admin\DashboardWidgets;

use Igniter\Admin\DashboardWidgets\Charts;
use Igniter\User\Models\User;
use Illuminate\Support\Facades\Event;
use Tests\Admin\Fixtures\Controllers\TestController;

it('fires admin.charts.extendDatasets event', function () {
    Event::fake();

    $controller = resolve(TestController::class);
    $widget = new Charts($controller, []);

    $widget->listContext();

    Event::assertDispatched('admin.charts.extendDatasets');
});

it('fetches chart data', function () {
    $user = User::factory()->create([
        'super_user' => true,
    ]);

    $this->actingAs($user, 'igniter-admin')
        ->post('/admin/dashboard', [
            '_handler' => 'charts::onFetchDatasets',
            'start' => '2021-01-01',
            'end' => '2021-01-31',
        ])
        ->assertJsonFragment(['label' => 'Customers'])
        ->assertJsonFragment(['label' => 'Orders']);
});