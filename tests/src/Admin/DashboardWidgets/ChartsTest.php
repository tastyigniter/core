<?php

namespace Igniter\Tests\Admin\DashboardWidgets;

use Igniter\Admin\Classes\AdminController;
use Igniter\Admin\DashboardWidgets\Charts;
use Igniter\System\Facades\Assets;
use Illuminate\Support\Facades\Event;

beforeEach(function() {
    $this->controller = $this->createMock(AdminController::class);
    $this->charts = new Charts($this->controller, [
        'startDate' => now()->subDays(30),
        'endDate' => now(),
    ]);
});

it('tests initialize', function() {
    expect($this->charts->property('rangeFormat'))->toBe('MMMM D, YYYY');
});

it('tests defineProperties', function() {
    $properties = $this->charts->defineProperties();

    expect($properties)->toBeArray()->toHaveKey('dataset');
});

it('tests loadAssets', function() {
    Assets::shouldReceive('addCss')->once()->with('dashboardwidgets/charts.css', 'charts-css');
    Assets::shouldReceive('addJs')->once()->with('js/vendor.datetime.js', 'vendor-datetime-js');
    Assets::shouldReceive('addJs')->once()->with('js/vendor.chart.js', 'vendor-chart-js');
    Assets::shouldReceive('addJs')->once()->with('dashboardwidgets/charts.js', 'charts-js');

    // Call the loadAssets method
    $this->charts->loadAssets();
});

it('tests prepareVars', function() {
    $this->charts->render();

    expect($this->charts->vars['chartContext'])->toBe('reports')
        ->and($this->charts->vars['chartType'])->toBe('line')
        ->and($this->charts->vars['chartLabel'])->toBe('--')
        ->and($this->charts->vars['chartIcon'])->toBe('fa fa-bar-chart-o')
        ->and($this->charts->vars['chartData'])->toBeArray()->toHaveKey('datasets');
});

it('tests getActiveDataset', function() {
    $dataset = $this->charts->getActiveDataset();

    expect($dataset)->toBe('reports');
});

it('tests getData', function() {
    $data = $this->charts->getData();

    expect($data)->toBeArray()->toHaveKey('datasets');
});

it('tests getDatasetOptions', function() {
    Event::fake();

    $options = $this->charts->getDatasetOptions();

    expect($options)->toBeArray()->toHaveKey('reports');

    Event::assertDispatched('admin.charts.extendDatasets');
});
