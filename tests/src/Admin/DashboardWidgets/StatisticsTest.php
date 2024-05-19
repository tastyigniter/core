<?php

namespace Tests\Admin\DashboardWidgets;

use Igniter\Admin\Classes\AdminController;
use Igniter\Admin\DashboardWidgets\Statistics;
use Igniter\System\Facades\Assets;

beforeEach(function() {
    $this->controller = $this->createMock(AdminController::class);
    $this->statistics = new Statistics($this->controller, ['card' => 'sale']);
});

it('tests defineProperties', function() {
    $properties = $this->statistics->defineProperties();

    expect($properties)->toBeArray()->toHaveKey('card');
});

it('tests getActiveCard', function() {
    $card = $this->statistics->getActiveCard();

    expect($card)->toBe('sale');
});

it('tests loadAssets', function() {
    Assets::shouldReceive('addCss')->once()->with('statistics.css', 'statistics-css');

    $this->statistics->assetPath = [];

    // Call the loadAssets method
    $this->statistics->loadAssets();
});

it('tests prepareVars', function() {
    $this->statistics->render();

    expect($this->statistics->vars['statsContext'])->toBe('sale')
        ->and($this->statistics->vars['statsLabel'])->toBe('lang:igniter::admin.dashboard.text_total_sale')
        ->and($this->statistics->vars['statsColor'])->toBe('success')
        ->and($this->statistics->vars['statsIcon'])->toBe(' text-success fa fa-4x fa-line-chart');
});

it('tests getValue', function() {
    $this->statistics->render();

    // The exact value will depend on the data in your database
    // Here we're just checking that we get a string
    expect($this->statistics->vars['statsCount'])->toBe('£0.00');
});

it('renders widget with no errors', function() {
    $widget = new Statistics($this->controller, ['card' => 'sale']);

    $widget->render();

    expect($widget->vars['statsContext'])->toEqual('sale')
        ->and($widget->vars['statsLabel'])->toBe('lang:igniter::admin.dashboard.text_total_sale')
        ->and($widget->vars['statsIcon'])->toBe(' text-success fa fa-4x fa-line-chart')
        ->and($widget->vars['statsCount'])->toBe('£0.00');
});